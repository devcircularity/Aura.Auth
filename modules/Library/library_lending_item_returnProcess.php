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

$gibbonLibraryItemEventID = $_GET['gibbonLibraryItemEventID'] ?? '';
$gibbonLibraryItemID = $_GET['gibbonLibraryItemID'] ?? '';
$name = $_GET['name'] ?? '';
$gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$status = $_GET['status'] ?? '';

if ($gibbonLibraryItemID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/library_lending_item_return.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status";
    $URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status";

    if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item_return.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if event specified
        if ($gibbonLibraryItemEventID == '' or $gibbonLibraryItemID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonLibraryItemEventID' => $gibbonLibraryItemEventID, 'gibbonLibraryItemID' => $gibbonLibraryItemID);
                $sql = 'SELECT * FROM gibbonLibraryItemEvent JOIN gibbonLibraryItem ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID AND gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID';
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
                //Validate Inputs
                $returnAction = $_POST['returnAction'] ?? '';
                $status = '';
                if ($returnAction == 'Reserve') {
                    $status = 'Reserved';
                } elseif ($returnAction == 'Decommission') {
                    $status = 'Decommissioned';
                } elseif ($returnAction == 'Repair') {
                    $status = 'Repair';
                }
                $gibbonPersonIDReturnAction = $_POST['gibbonPersonIDReturnAction'] ?? null;


                //Write to database
                try {
                    $data = array('timestampReturn' => date('Y-m-d H:i:s', time()), 'gibbonLibraryItemEventID' => $gibbonLibraryItemEventID, 'gibbonPersonIDIn' => $session->get('gibbonPersonID'));
                    $sql = "UPDATE gibbonLibraryItemEvent SET status='Returned', timestampReturn=:timestampReturn, gibbonPersonIDIn=:gibbonPersonIDIn WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //No return action, so just mark the item
                if ($returnAction == '') {
                    try {
                        $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'gibbonPersonIDStatusRecorder' => $session->get('gibbonPersonID'), 'timestampStatus' => date('Y-m-d H:i:s', time()));
                        $sql = "UPDATE gibbonLibraryItem SET status='Available', gibbonPersonIDStatusResponsible=NULL, gibbonPersonIDStatusRecorder=:gibbonPersonIDStatusRecorder, timestampStatus=:timestampStatus, returnExpected=NULL, returnAction='', gibbonPersonIDReturnAction=NULL WHERE gibbonLibraryItemID=:gibbonLibraryItemID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                }
                //Return action, so mark the item, and create a new event
                else {
                    try {
                        $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'status' => $status, 'gibbonPersonIDStatusResponsible' => $gibbonPersonIDReturnAction, 'gibbonPersonIDOut' => $session->get('gibbonPersonID'), 'timestampOut' => date('Y-m-d H:i:s', time()));
                        $sql = "INSERT INTO gibbonLibraryItemEvent SET gibbonLibraryItemID=:gibbonLibraryItemID, status=:status, gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible, gibbonPersonIDOut=:gibbonPersonIDOut, timestampOut=:timestampOut, returnExpected=NULL, returnAction='', gibbonPersonIDReturnAction=NULL";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    try {
                        $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'status' => $status, 'gibbonPersonIDStatusResponsible' => $gibbonPersonIDReturnAction, 'gibbonPersonIDStatusRecorder' => $session->get('gibbonPersonID'), 'timestampStatus' => date('Y-m-d H:i:s', time()));
                        $sql = "UPDATE gibbonLibraryItem SET status=:status, gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible, gibbonPersonIDStatusRecorder=:gibbonPersonIDStatusRecorder, timestampStatus=:timestampStatus, returnExpected=NULL, returnAction='', gibbonPersonIDReturnAction=NULL WHERE gibbonLibraryItemID=:gibbonLibraryItemID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                }

                $URL = $URLSuccess.'&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
