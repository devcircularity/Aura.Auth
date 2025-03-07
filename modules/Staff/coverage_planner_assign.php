<?php
/*
Olaji SMS – The Smart School Management System
Olaji SMS – Empowering schools with efficient learning and administration tools.
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\DataSet;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Staff\View\StaffCard;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\Tables\CoverageMiniCalendar;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\DaysOfWeekGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonStaffCoverageDateID = $_REQUEST['gibbonStaffCoverageDateID'] ?? '';

    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    $subGateway = $container->get(SubstituteGateway::class);
    $courseGateway = $container->get(CourseGateway::class);

    if (empty($gibbonStaffCoverageDateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $coverage = $staffCoverageDateGateway->getCoverageDateDetailsByID($gibbonStaffCoverageDateID);

    if (empty($coverage)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $settingGateway = $container->get(SettingGateway::class);
    $urgencyThreshold = $settingGateway->getSettingByScope('Staff', 'urgencyThreshold');
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    // ABSENCE DETAILS
    if (!empty($coverage['gibbonPersonID'])) {
        $staffCard = $container->get(StaffCard::class);
        $staffCard->setPerson($coverage['gibbonPersonID'])->compose($page);
    }

    $dateObject = new \DateTimeImmutable($coverage['date']);
    $startOfWeek = $dateObject->modify('last Sunday')->format('Y-m-d');
    $endOfWeek = $dateObject->modify('next Sunday')->format('Y-m-d');

    $times = $staffCoverageDateGateway->getCoverageTimesByForeignTable($coverage['foreignTable'], $coverage['foreignTableID'], $coverage['date']);

    $dayOfWeek = $container->get(DaysOfWeekGateway::class)->getDayOfWeekByDate($coverage['date']);

    // DETAILS
    $table = DataTable::createDetails('coverage');

    $table->addColumn('date', __('Date'))->format(Format::using('dateReadable', ['date', Format::FULL]));
    $table->addColumn('period', __('Period'));
    $table->addColumn('time', __('Time'))->format(Format::using('timeRange', ['timeStart', 'timeEnd']));

    if ($coverage['foreignTable'] == 'gibbonTTDayRowClass') {
        $details = $courseGateway->getCourseClassByID($times['gibbonCourseClassID']);

        $table->addColumn('class', __('Class'))
            ->format(function($coverage) {
                if (empty($coverage['gibbonCourseID'])) return '';

                $url = './index.php?q=/modules/Departments/department_course_class.php&gibbonDepartmentID='.$coverage['gibbonDepartmentID'].'&gibbonCourseID='.$coverage['gibbonCourseID'].'&gibbonCourseClassID='.$coverage['gibbonCourseClassID'];
                return Format::link($url, Format::courseClassName($coverage['courseNameShort'], $coverage['nameShort']), ['target' => '_blank']);
            });

        $table->addColumn('studentsTotal', __('Students'));

        $table->addColumn('spaceName', __('Room'))
            ->format(function($coverage) {
                if (empty($coverage['gibbonSpaceID'])) return '';

                $url = './index.php?q=/modules/Timetable/tt_space_view.php&gibbonSpaceID='.$coverage['gibbonSpaceID'].'&ttDate='.Format::date($coverage['date']);
                return Format::link($url, $coverage['spaceName'] ?? '', ['target' => '_blank']);
            });

        if (!empty($coverage['reason'])) {
            $table->addColumn('reason', __('Notes'))->setClass('grid-cols-1 md:grid-cols-3');
        }
    } else {
        $details = [];
    }

    echo $table->render([array_merge($coverage, $details, $times)]);

    // CRITERIA
    $criteria = $subGateway->newQueryCriteria()
        ->sortBy('available', 'DESC')
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('allStaff', $internalCoverage == 'Y')
        ->filterBy('showUnavailable', 'Y')
        ->fromPOST();

    // FORM
    $form = Form::createBlank('staffCoverage', $session->get('absoluteURL').'/modules/Staff/coverage_planner_assignProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('bulkActionForm');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffCoverageID', $coverage['gibbonStaffCoverageID']);
    $form->addHiddenValue('gibbonStaffCoverageDateID', $gibbonStaffCoverageDateID);
    $form->addHiddenValue('date', $coverage['date']);

    $statuses = [
        'Required' => __('Cover Required'),
        'Not Required' => __('Not Required'),
    ];

    $row = $form->addRow()->setClass('border bg-gray-100 rounded mt-6 p-2 flex justify-between items-center');
        $row->addLabel('coverageStatus', __('Status'))->setClass('text-sm font-bold');
        $row->addSelect('coverageStatus')
            ->fromArray($statuses)
            ->setClass('w-48')
            ->selected($coverage['status'] == 'Not Required' ? 'Not Required' : 'Required' );

    $form->toggleVisibilityByClass('subsList')->onSelect('coverageStatus')->when('Required');

    $subs = $subGateway->queryAvailableSubsByDate($criteria, $coverage['date'], $coverage['timeStart'], $coverage['timeEnd']);
    $availability = $subGateway->selectUnavailableDatesByDateRange($coverage['date'], $coverage['date'])->fetchGrouped();

    // Check for special days for these classes
    $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
    $specialDay = $specialDayGateway->getSpecialDayByDate($coverage['date']);
    if (!empty($specialDay)) {
        foreach ($availability as $gibbonPersonID => $dates)
        $availability[$gibbonPersonID] = array_filter($dates, function ($item) use (&$specialDay, &$specialDayGateway, &$session) {
            if ($item['status'] != 'Teaching') return true;
            return !$specialDayGateway->getIsClassOffTimetableByDate($session->get('gibbonSchoolYearID'), $item['contextID'], $item['date']);
        });
    }

    $people = $subs->getColumn('gibbonPersonID');
    $coverageCounts = $staffCoverageGateway->selectCoverageCountsByPerson($people, $coverage['date'])->fetchGroupedUnique();
    $subs->joinColumn('gibbonPersonID', 'coverageCounts', $coverageCounts);
    $timetableCounts = $staffCoverageGateway->selectTimetableCountsByPerson($people, $startOfWeek, $endOfWeek)->fetchGroupedUnique();
    $subs->joinColumn('gibbonPersonID', 'timetableCounts', $timetableCounts);

    // Collect together the sub availability data
    $subs->transform(function (&$sub) use (&$availability) {
        $sub['dates'] = $availability[intval($sub['gibbonPersonID'])] ?? [];
        $sub['availability'] = count($sub['dates']);
        $sub['workload'] = array_sum(array_map(function ($item) {
            return $item['status'] == 'Teaching' || $item['status'] == 'Covering' || $item['status'] == 'Staff Duty'
                ? $item['mins']
                : 0;
        }, $sub['dates']));
        $sub['absences'] = count(array_filter($sub['dates'], function ($item) {
            return $item['status'] == 'Absent' && $item['allDay'] == 'Y';
        }));

    });

    // Get all the maximum values for coverage counts
    $counts = ['workload' => 0, 'timetable' => 0, 'absences' => 0, 'weeklyCoverage' => 0, 'yearlyCoverage' => 0];
    $subs->transform(function (&$sub) use (&$counts) {
        if ($sub['coveragePriority'] > 5) return;
        $counts['workload'] = max($sub['workload'] ?? 0, $counts['workload']);
        $counts['absences'] = max($sub['absences'] ?? 0, $counts['absences']);
        $counts['timetable'] = max($sub['timetableCounts']['totalMinutes'] ?? 0, $counts['timetable']);
        $counts['weeklyCoverage'] = max($sub['coverageCounts']['weeklyCoverageMins'] ?? 0, $counts['weeklyCoverage']);
        $counts['yearlyCoverage'] = max($sub['coverageCounts']['yearlyCoverage'] ?? 0, $counts['yearlyCoverage']);
    });

    // Create a weighing for sorting coverage availability
    $subs->transform(function (&$sub) use (&$counts) {
        $coveragePriority = ($sub['coveragePriority'] ?? 0) / 9.0;

        // Total workload in minutes on that day
        $workloadByDay = 1.0 - min($sub['workload'] ?? 0, $counts['workload']) / max(1, $counts['workload']);

        // Total teaching minutes in the week
        $weeklyClasses = 1.0 - min($sub['timetableCounts']['totalMinutes'] ?? 0, $counts['timetable']) / max(1, $counts['timetable']);

        // Normalized coverage for that week/year
        $weeklyCoverage = min($sub['coverageCounts']['weeklyCoverageMins'] ?? 0, $counts['weeklyCoverage']) / max(1, $counts['weeklyCoverage']);
        $yearlyCoverage = min($sub['coverageCounts']['yearlyCoverage'] ?? 0, $counts['yearlyCoverage']) / max(1, $counts['yearlyCoverage']);

        $sub['coverageWeight'] = ($coveragePriority * 4.5);
        $sub['coverageWeight'] += ($workloadByDay * 1.0);
        $sub['coverageWeight'] += ($weeklyClasses * 0.35);
        $sub['coverageWeight'] += ($weeklyCoverage * -0.15);
        $sub['coverageWeight'] += ($yearlyCoverage * -0.04);

        $sub['timetableLoad'] = round(min($sub['timetableCounts']['totalMinutes'] ?? 0, $counts['timetable']) / max(1, $counts['timetable']) * 100);

    });

    // Sort by highest availability to lowest availability
    $subList = $subs->toArray();
    usort($subList, function ($a, $b) {
        return $b['available'] <=> $a['available'] ?: $b['coverageWeight'] <=> $a['coverageWeight'];
    });

    // Sort the current selected sub to the top of the list
    if (!empty($coverage['gibbonPersonIDCoverage'])) {
        usort($subList, function ($a, $b) use (&$coverage) {
            if ($a['gibbonPersonID'] == $coverage['gibbonPersonIDCoverage']) {
                return -1;
            }
            if ($b['gibbonPersonID'] == $coverage['gibbonPersonIDCoverage']) {
                return 1;
            }

            return 0;
        });
    }

    $subs = new DataSet($subList);

    $subs->transform(function (&$sub) use (&$coverage) {
        if ($sub['available']) {
            $isCovering = $sub['gibbonPersonID'] == $coverage['gibbonPersonIDCoverage'];
            $sub['dates'][] = [
                'date'      => $coverage['date'],
                'status'    => $isCovering ? 'Covering' : 'Available',
                'allDay'    => 'N',
                'timeStart' => $coverage['timeStart'],
                'timeEnd'   => $coverage['timeEnd'],
            ];
        }
    });

    // DATA TABLE
    $row = $form->addRow()->addClass('subsList');
    $table = $row->addDataTable('subsManage')->withData($subs);

    $table->setTitle(__('Substitute Availability'));

    $table->addMetaData('hidePagination', true);

    $table->modifyRows(function ($values, $row) use (&$coverage) {
        if ($values['gibbonPersonID'] == $coverage['gibbonPersonIDCoverage']) return $row->addClass('selected');
        if (!$values['available']) $row->addClass('error unavailableSub');
        return $row;
    });

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'sm']));

    $canManageCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');
    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->description(__('Priority'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) use ($canManageCoverage) {
            $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            if (!empty($person['gibbonStaffID'])) {
                $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
            } else {
                $url = '';
            }

            return Format::link($url, $name, ['target' => '_blank']).'<br/>'.Format::small($person['jobTitle'] ?? $person['type']);
        });

    $table->addColumn('load', __('Timetable'))
        ->format(function ($person) {
            $class = 'dull';
            if ($person['timetableLoad'] >= 75) $class = 'warning';
            if ($person['timetableLoad'] >= 90) $class = 'error';
            return Format::tag($person['timetableLoad'].'%', $class);
        });

    $table->addColumn('details', __('Details'))
        ->format(function ($person) {
            return Format::listDetails([
                __('Week') => $person['coverageCounts']['weeklyCoverage'] ?? 0,
                __('Year') => $person['coverageCounts']['yearlyCoverage'] ?? 0,
            ], 'ul', 'list-none text-xs text-right p-0 m-0', 'w-2/3 whitespace-nowrap');
        });

    $table->addColumn('availability', __('Availability'))
        ->context('primary')
        ->notSortable()
        ->format(function ($person) use ($dateObject, $dayOfWeek) {
            return CoverageMiniCalendar::renderTimeRange($dayOfWeek, $person['dates'] ?? [], $dateObject);
        });

    $table->addRadioColumn('gibbonPersonIDCoverage', 'gibbonPersonID')->checked($coverage['gibbonPersonIDCoverage'] ?? null);

    $row = $form->addRow()->addClass('subsList mb-4');
        $row->addCheckbox('showUnavailable')->setValue('Y')->checked(false)->description(__('Show Unavailable Staff?'));

    $form->toggleVisibilityByClass('unavailableSub')->onCheckbox('showUnavailable')->when('Y');

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
<script>

$('#subsManage tr').removeClass('odd').removeClass('even');

$(document).on('click', 'input[id^="gibbonPersonID"]', function(event) {
    $('#subsManage tr').removeClass('selected');
    $(event.target).parents('tr').addClass('selected');
});

</script>
