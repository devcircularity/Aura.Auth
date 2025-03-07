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

use Gibbon\Services\Format;
use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendance.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';

        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
        $sql = "SELECT name, programStart, programEnd, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    $row = $result->fetch();

    if ($gibbonActivityID != '') {
        $output = '';

        $date = '';
        if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
            if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).')';
            } else {
                $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programStart'], 0, 4).')';
            }
        } else {
            $date = ' ('.date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4).')';
        }

        echo '<h2>';
        echo __('Participants for').' '.$row['name'].$date;
        echo '</h2>';

        if ($result->rowCount() < 1) {
            echo $page->getBlankSlate();
        } else {
            $form = Form::createBlank('buttons');
            $form->addHeaderAction('print', __('Print'))
                ->setURL('#')
                ->onClick('javascript:window.print(); return false;');
            echo $form->getOutput();

            $lastPerson = '';

            echo "<table class='mini' cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __('Student');
            echo '</th>';
            echo '<th colspan=15>';
            echo __('Attendance');
            echo '</th>';
            echo '</tr>';
            echo "<tr style='height: 75px' class='odd'>";
            echo "<td style='vertical-align:top; width: 120px'>Date</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>1</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>2</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>3</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>4</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>5</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>6</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>7</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>8</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>9</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>10</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>11</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>12</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>13</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>14</td>";
            echo "<td style='color: #bbb; vertical-align:top; width: 15px'>15</td>";
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';

                $data = array('gibbonSchoolYearID' => $session->get('SchoolYearID'), 'gibbonSchoolYearID2' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
                $sql = "SELECT name, programStart, programEnd, gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFormGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            while ($row = $result->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $count.'. '.Format::name('', $row['preferredName'], $row['surname'], 'Student', true);
                echo '</td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '<td></td>';
                echo '</tr>';

                $lastPerson = $row['gibbonPersonID'];
            }
            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=16>';
                echo __('There are no records to display.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
