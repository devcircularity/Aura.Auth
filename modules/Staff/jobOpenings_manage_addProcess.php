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
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['description' => 'HTML']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/jobOpenings_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/jobOpenings_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $type = $_POST['type'] ?? '';
    $jobTitle = $_POST['jobTitle'] ?? '';
    $dateOpen = !empty($_POST['dateOpen']) ? Format::dateConvert($_POST['dateOpen']) : null;
    $active = $_POST['active'] ?? '';
    $description = $_POST['description'] ?? '';

    if ($type == '' or $jobTitle == '' or $dateOpen == '' or $active == '' or $description == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Write to database
        try {
            $data = array('type' => $type, 'jobTitle' => $jobTitle, 'dateOpen' => $dateOpen, 'active' => $active, 'description' => $description, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'));
            $sql = 'INSERT INTO gibbonStaffJobOpening SET type=:type, jobTitle=:jobTitle, dateOpen=:dateOpen, active=:active, description=:description, gibbonPersonIDCreator=:gibbonPersonIDCreator';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Last insert ID
        $AI = str_pad($connection2->lastInsertID(), 10, '0', STR_PAD_LEFT);

        //Success 0
        $URL .= "&return=success0&editID=$AI";
        header("Location: {$URL}");
    }
}
