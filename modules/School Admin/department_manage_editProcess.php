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
use Gibbon\Forms\CustomFieldHandler;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['blurb' => 'HTML']);

$gibbonDepartmentID = $_GET['gibbonDepartmentID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/department_manage_edit.php&gibbonDepartmentID=$gibbonDepartmentID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/department_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit();
} else {
    //Proceed!
    //Check if gibbonDepartmentID specified
    if ($gibbonDepartmentID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit();
    } else {
        try {
            $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
            $sql = 'SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID';
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
            exit();
        } else {
            $row = $result->fetch();
            //Validate Inputs
            $name = $_POST['name'] ?? '';
            $nameShort = $_POST['nameShort'] ?? '';
            $subjectListing = $_POST['subjectListing'] ?? '';
            $blurb = $_POST['blurb'] ?? '';

            $customRequireFail = false;
            $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Department', [], $customRequireFail);

            if ($customRequireFail) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }

            if ($name == '' or $nameShort == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
                exit();
            } else {
                $partialFail = false;

                //Move attached file, if there is one
                if (!empty($_FILES['file']['tmp_name'])) {
                    $fileUploader = new Gibbon\FileUploader($pdo, $session);

                    $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                    // Upload the file, return the /uploads relative path
                    $attachment = $fileUploader->uploadFromPost($file, $name);

                    if (empty($attachment)) {
                        $partialFail = true;
                    }
                } else {
                    // Remove the attachment if it has been deleted, otherwise retain the original value
                    $attachment = empty($_POST['logo']) ? '' : $row['logo'];
                }

                //Scan through staff
                $staff = array();
                if (isset($_POST['staff'])) {
                    $staff = $_POST['staff'] ?? [];
                }
                $role = $_POST['role'] ?? '';
                if ($role == '') {
                    $role = 'Other';
                }
                if (count($staff) > 0) {
                    foreach ($staff as $t) {
                        //Check to see if person is already registered in this activity
                        try {
                            $dataGuest = array('gibbonPersonID' => $t, 'gibbonDepartmentID' => $gibbonDepartmentID);
                            $sqlGuest = 'SELECT * FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND gibbonDepartmentID=:gibbonDepartmentID';
                            $resultGuest = $connection2->prepare($sqlGuest);
                            $resultGuest->execute($dataGuest);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        if ($resultGuest->rowCount() == 0) {
                            try {
                                $data = array('gibbonPersonID' => $t, 'gibbonDepartmentID' => $gibbonDepartmentID, 'role' => $role);
                                $sql = 'INSERT INTO gibbonDepartmentStaff SET gibbonPersonID=:gibbonPersonID, gibbonDepartmentID=:gibbonDepartmentID, role=:role';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }

                //Write to database
                try {
                    $data = array('name' => $name, 'nameShort' => $nameShort, 'subjectListing' => $subjectListing, 'blurb' => $blurb, 'logo' => $attachment, 'fields' => $fields, 'gibbonDepartmentID' => $gibbonDepartmentID);
                    $sql = 'UPDATE gibbonDepartment SET name=:name, nameShort=:nameShort, subjectListing=:subjectListing, blurb=:blurb, logo=:logo, fields=:fields WHERE gibbonDepartmentID=:gibbonDepartmentID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($partialFail == true) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
