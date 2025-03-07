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

use Gibbon\Http\Url;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->return->addReturns(['error3' => __('The selected record does not exist, or you do not have access to it.')]);
    if ($highestAction == 'View by Student') {
        $student =  $container->get(StudentGateway::class)->selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID)->fetch();

        if (empty($student) && $allStudents == 'on') {
            $student = $container->get(UserGateway::class)->getByID($gibbonPersonID);
        }

        $page->breadcrumbs
            ->add(__('View by Student'), 'archive_byStudent.php')
            ->add(Format::name('', $student['preferredName'], $student['surname'], 'Student'));
    } else if ($highestAction == 'View Reports_myChildren') {
        $studentGateway = $container->get(StudentGateway::class);

        $children = $studentGateway
            ->selectAnyStudentsByFamilyAdult($gibbonSchoolYearID, $session->get('gibbonPersonID'))
            ->fetchGroupedUnique();

        if (!empty($children[$gibbonPersonID])) {
            $student = $container->get(UserGateway::class)->getByID($gibbonPersonID);

            $page->breadcrumbs
                ->add(__('View Reports'), 'archive_byFamily.php')
                ->add(Format::name('', $student['preferredName'], $student['surname'], 'Student'));
        }
    } else if ($highestAction == 'View Reports_mine') {
        $gibbonPersonID = $session->get('gibbonPersonID');
        $student =  $container->get(StudentGateway::class)->selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID)->fetch();

        $page->breadcrumbs->add(__('View Reports'));
    }

    if (empty($gibbonPersonID)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    if (empty($student)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (!empty($search) || !empty($gibbonYearGroupID) || !empty($gibbonFormGroupID) || !empty($allStudents)) {
        $params = [
            "search" => $search,
            "gibbonYearGroupID" => $gibbonYearGroupID,
            "gibbonFormGroupID" => $gibbonFormGroupID,
            "allStudents" => $allStudents,
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Reports', 'archive_byStudent.php')->withQueryParams($params));
    }

    $archiveInformation = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'archiveInformation');

    echo $page->fetchFromTemplate('ui/archiveStudentHeader.twig.html', ['student' => $student, 'archiveInformation' => $archiveInformation]);

    // CRITERIA
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    $criteria = $reportArchiveEntryGateway->newQueryCriteria()
        ->sortBy('sequenceNumber', 'DESC')
        ->sortBy(['timestampCreated'])
        ->fromPOST();

    // QUERY
    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Draft Reports');
    $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Past Reports');
    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

    $reports = $reportArchiveEntryGateway->queryArchiveByStudent($criteria, $gibbonPersonID, $roleCategory, $canViewDraftReports, $canViewPastReports);

    $reportsBySchoolYear = array_reduce($reports->toArray(), function ($group, $item) {
        $group[$item['schoolYear']][] = $item;
        return $group;
    }, []);

    if (empty($reportsBySchoolYear)) {
        $reportsBySchoolYear = [__('Reports') => []];
    }

    foreach ($reportsBySchoolYear as $schoolYear => $reports) {
        // DATA TABLE
        $table = DataTable::create('reportsView');
        $table->setTitle($schoolYear);

        $table->addColumn('reportName', __('Report'))
            ->width('30%')
            ->format(function ($report) {
                return !empty($report['reportName'])? $report['reportName'] : $report['reportIdentifier'];
            });

        $table->addColumn('yearGroup', __('Year Group'))->width('15%');
        $table->addColumn('formGroup', __('Form Group'))->width('15%');
        $table->addColumn('timestampModified', __('Date'))
            ->width('30%')
            ->format(function ($report) {
                $output = Format::dateReadable($report['timestampModified']);
                if ($report['status'] == 'Draft') {
                    $output .= '<span class="tag ml-2 dull">'.__($report['status']).'</span>';
                }

                if (!empty($report['timestampAccessed'])) {
                    $title = Format::name($report['parentTitle'], $report['parentPreferredName'], $report['parentSurname'], 'Parent', false).': '.Format::relativeTime($report['timestampAccessed'], false);
                    $output .= '<span class="tag ml-2 success" title="'.$title.'">'.__('Read').'</span>';
                }

                return $output;
            });

        $table->addActionColumn()
            ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->format(function ($report, $actions) {
                $actions->addAction('view', __('View'))
                    ->directLink()
                    ->addParam('action', 'view')
                    ->addParam('gibbonReportArchiveEntryID', $report['gibbonReportArchiveEntryID'] ?? '')
                    ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                    ->setURL('/modules/Reports/archive_byStudent_download.php');

                $actions->addAction('download', __('Download'))
                    ->setIcon('download')
                    ->directLink()
                    ->addParam('gibbonReportArchiveEntryID', $report['gibbonReportArchiveEntryID'] ?? '')
                    ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                    ->setURL('/modules/Reports/archive_byStudent_download.php');
            });

        echo $table->render(new DataSet($reports));
    }
}
