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

use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Services\Format;

require getcwd().'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    echo __('This script cannot be run from a browser, only via CLI.');
} else {

    require_once __DIR__ . '/../modules/Attendance/moduleFunctions.php';
    require_once __DIR__ . '/../modules/Attendance/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo, $settingGateway);

    $countClassAsSchool = $settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');
    $firstDayOfTheWeek = $session->get('firstDayOfTheWeek');
    $dateFormat = $session->get('i18n')['dateFormat'];

    $dateEnd = new DateTime();
    $dateStart = new DateTime();
    $dateStart->modify("$firstDayOfTheWeek this week");

    $data = array(
        'dateStart' => $dateStart->format('Y-m-d'),
        'dateEnd' => $dateEnd->format('Y-m-d'),
        'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID')
    );
    $sql = "SELECT gibbonFormGroup.nameShort as formGroupName, gibbonYearGroup.gibbonYearGroupID, gibbonAttendanceLogPerson.*, gibbonPerson.surname, gibbonPerson.preferredName, gibbonCourse.nameShort as courseName, gibbonCourseClass.nameShort as className, gibbonCourseClass.gibbonCourseClassID
            FROM gibbonAttendanceLogPerson
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonAttendanceLogPerson.gibbonPersonID)
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
            JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
            JOIN gibbonAttendanceCode ON (gibbonAttendanceCode.gibbonAttendanceCodeID=gibbonAttendanceLogPerson.gibbonAttendanceCodeID)
            LEFT JOIN gibbonCourseClass ON (gibbonAttendanceLogPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            WHERE gibbonAttendanceLogPerson.date BETWEEN :dateStart AND :dateEnd
            AND gibbonPerson.status='Full' ";

    if ($countClassAsSchool == 'N') {
        $sql .= "AND NOT gibbonAttendanceLogPerson.context='Class' ";
    }

    $sql .= "AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            ORDER BY gibbonYearGroup.sequenceNumber, gibbonFormGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName, gibbonAttendanceLogPerson.date, gibbonAttendanceLogPerson.timestampTaken
    ";

    $reportByYearGroup = array();
    $results = $pdo->executeQuery($data, $sql);

    if ($results && $results->rowCount() > 0) {
        $attendanceLogs = $results->fetchAll(\PDO::FETCH_GROUP);
        foreach ($attendanceLogs as $formGroupName => $formGroupLogs) {
            $gibbonYearGroupID = current($formGroupLogs)['gibbonYearGroupID'];

            // Fields to group per-day for attendance logs
            $fields = array('context', 'date', 'type', 'reason', 'comment', 'timestampTaken', 'courseName', 'className', 'gibbonCourseClassID');
            $fields = array_flip($fields);

            // Build an attendance log set of days for each student
            $logsByStudent = array_reduce($formGroupLogs, function ($carry, &$item) use (&$fields) {
                $id = $item['gibbonPersonID'];
                $carry[$id]['preferredName'] = $item['preferredName'];
                $carry[$id]['surname'] = $item['surname'];
                $carry[$id]['days'][$item['date']][] = array_intersect_key($item, $fields);

                return $carry;
            }, array());

            // Filter down to just the relevant logs
            foreach ($logsByStudent as $key => &$item) {
                $item['days'] = array_map(function($logs) use (&$attendance) {
                    $endOfDay = end($logs);

                    // Grab the end-of-class and end-of-day statuses for each set of logs
                    $filtered = array_reduce($logs, function($carry, $log) use ($endOfDay) {
                        if ($log['context'] == 'Class' || $log === $endOfDay) {
                            $carry[$log['gibbonCourseClassID']] = $log;
                        }
                        return $carry;
                    }, array());

                    // Remove all logs that aren't absent or late
                    $filtered = array_filter($filtered, function($log) use (&$attendance)  {
                        return $attendance->isTypeAbsent($log['type']) || $attendance->isTypeLate($log['type']);
                    });

                    return $filtered;
                }, $item['days']);

                // Keep only days with logs left
                $item['days'] = array_filter($item['days'], function($logs) {
                    return !empty($logs);
                });
            }

            // Keep only students who have absent days
            $logsByStudent = array_filter($logsByStudent, function ($item) {
                return !empty($item['days']);
            });

            // Skip reports for empty data sets
            if (count($logsByStudent) == 0) continue;

            $report = '<h4>'.$formGroupName.'  &nbsp;<small>(Total '.count($logsByStudent).')</small></h4>';
            $report .= '<ul>';

            foreach ($logsByStudent as $gibbonPersonID => $student) {
                $report .= '<li>';
                $report .= '<a href="'.$session->get('absoluteURL').'/index.php?q=/modules/Attendance/report_studentHistory.php&gibbonPersonID='.$gibbonPersonID.'" target="_blank">';
                $report .= Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true);
                $report .= '</a>';

                foreach ($student['days'] as $date => $logs) {
                    $report .= '<br/><span style="display:inline-block; width:45px;margin-left:30px;">'.date('D', strtotime($date)) .'</span>';
                    $report .= '<span style="display:inline-block; width:65px;">'.date('M j', strtotime($date)) .'</span>';

                    // Display frequencies of each absence type
                    $types = array_count_values(array_column($logs, 'type'));
                    $types = array_map(function($type, $count) {
                        return ($count > 1)? $type.' ('.$count.')' : $type;
                    }, array_keys($types), $types);

                    $report .= implode(', ', $types);
                }
                $report .= '<br/><br/></li>';
            }
            $report .= '</ul>';

            $reportByYearGroup[$gibbonYearGroupID][$formGroupName] = $report;
        }
    }

    if (!empty($reportByYearGroup)) {
        // Initialize the notification sender & gateway objects
        $notificationGateway = $container->get(NotificationGateway::class);
        $notificationSender = $container->get(NotificationSender::class);

        $reportHeading = '<h3>'.__('Weekly Attendance Summary').': '.$dateStart->format('M j').' - '.$dateEnd->format('M j').'</h3>';

        foreach ($reportByYearGroup as $gibbonYearGroupID => $reportByFormGroup) {
            // Raise a new notification event
            $event = new NotificationEvent('Attendance', 'Weekly Attendance Summary');

            $event->addScope('gibbonYearGroupID', $gibbonYearGroupID);
            $event->setNotificationText(__('An Attendance CLI script has run.').'<br/><br/>'.$reportHeading . implode(' ', $reportByFormGroup));
            $event->setActionLink('/index.php?q=/modules/Attendance/report_summary_byDate.php&dateStart='.$dateStart->format($dateFormat).'dateEnd='.$dateEnd->format($dateFormat).'&group=all&sort=formGroup');

            // Push the event to the notification sender
            $event->pushNotifications($notificationGateway, $notificationSender);
        }

        // Send all notifications
        $sendReport = $notificationSender->sendNotifications();

        // Output the result to terminal
        echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
    }
}
