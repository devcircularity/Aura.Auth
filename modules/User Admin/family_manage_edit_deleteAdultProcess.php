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

$gibbonFamilyID = $_GET['gibbonFamilyID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonFamilyID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/family_manage_edit_deleteAdult.php&gibbonFamilyID=$gibbonFamilyID&gibbonPersonID=$gibbonPersonID&search=$search";
    $URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/family_manage_edit.php&gibbonFamilyID=$gibbonFamilyID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit_deleteAdult.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if gibbonPersonID specified
        if ($gibbonPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT gibbonPerson.gibbonPersonID, gibbonFamilyAdult.contactPriority FROM gibbonPerson, gibbonFamily, gibbonFamilyAdult WHERE gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID';
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
                $row = $result->fetch();

                // If we're deleting the first contact priority, move the second one to first
                if ($row['contactPriority'] == 1) {

                        $dataCP = array('gibbonPersonID' => $gibbonPersonID, 'gibbonFamilyID' => $gibbonFamilyID);
                        $sqlCP = 'UPDATE gibbonFamilyAdult SET contactPriority=1 WHERE contactPriority=2 AND gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPersonID=:gibbonPersonID LIMIT 1';
                        $resultCP = $connection2->prepare($sqlCP);
                        $resultCP->execute($dataCP);
                }

                //Write to database
                try {
                    $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = 'DELETE FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND gibbonFamilyID=:gibbonFamilyID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
