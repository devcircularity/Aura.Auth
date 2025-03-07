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
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
$gibbonTTID = $_GET['gibbonTTID'] ?? '';
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
$gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';

$gibbonSpaceID = !empty($_POST['gibbonSpaceID']) ? $_POST['gibbonSpaceID'] : null;

if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/tt_edit_day_edit_class_add.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID";

    if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if gibbonTTDayID specified
        if ($gibbonTTDayID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {

                $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
                $sql = 'SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonTTDayID' => $gibbonTTDayID, 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonSpaceID' => $gibbonSpaceID);
                    $sql = 'INSERT INTO gibbonTTDayRowClass SET gibbonTTColumnRowID=:gibbonTTColumnRowID, gibbonTTDayID=:gibbonTTDayID, gibbonCourseClassID=:gibbonCourseClassID, gibbonSpaceID=:gibbonSpaceID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                $URL .= "&return=success0&editID=$AI&gibbonCourseClassID=gibbonCourseClassID";
                header("Location: {$URL}");
            }
        }
    }
}
