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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINDescriptorID = $_GET['gibbonINDescriptorID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/inSettings_edit.php&gibbonINDescriptorID=$gibbonINDescriptorID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/inSettings_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonINDescriptorID specified
    if ($gibbonINDescriptorID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonINDescriptorID' => $gibbonINDescriptorID);
            $sql = 'SELECT * FROM gibbonINDescriptor WHERE gibbonINDescriptorID=:gibbonINDescriptorID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            $name = $_POST['name'] ?? '';
            $nameShort = $_POST['nameShort'] ?? '';
            $sequenceNumber = $_POST['sequenceNumber'] ?? '';
            $description = $_POST['description'] ?? '';

            //Validate Inputs
            if ($name == '' or $nameShort == '' or $sequenceNumber == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'sequenceNumber' => $sequenceNumber, 'gibbonINDescriptorID' => $gibbonINDescriptorID);
                    $sql = 'SELECT * FROM gibbonINDescriptor WHERE (name=:name OR nameShort=:nameShort OR sequenceNumber=:sequenceNumber) AND NOT gibbonINDescriptorID=:gibbonINDescriptorID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('name' => $name, 'nameShort' => $nameShort, 'sequenceNumber' => $sequenceNumber, 'description' => $description, 'gibbonINDescriptorID' => $gibbonINDescriptorID);
                        $sql = 'UPDATE gibbonINDescriptor SET name=:name, nameShort=:nameShort, sequenceNumber=:sequenceNumber, description=:description WHERE gibbonINDescriptorID=:gibbonINDescriptorID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
