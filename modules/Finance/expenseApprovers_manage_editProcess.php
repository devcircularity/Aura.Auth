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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonFinanceExpenseApproverID = $_GET['gibbonFinanceExpenseApproverID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address).'/expenseApprovers_manage_edit.php&gibbonFinanceExpenseApproverID='.$gibbonFinanceExpenseApproverID;

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseApprovers_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonFinanceExpenseApproverID specified
    if ($gibbonFinanceExpenseApproverID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID);
            $sql = 'SELECT * FROM gibbonFinanceExpenseApprover WHERE gibbonFinanceExpenseApproverID=:gibbonFinanceExpenseApproverID';
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
            $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
            $expenseApprovalType = $container->get(SettingGateway::class)->getSettingByScope('Finance', 'expenseApprovalType');
            $sequenceNumber = null;
            if ($expenseApprovalType == 'Chain Of All') {
                $sequenceNumber = abs($_POST['sequenceNumber']);
            }

            if ($gibbonPersonID == '' or ($expenseApprovalType == 'Y' and $sequenceNumber == '')) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    if ($expenseApprovalType == 'Chain Of All') {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'sequenceNumber' => $sequenceNumber, 'gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID);
                        $sql = 'SELECT * FROM gibbonFinanceExpenseApprover WHERE (gibbonPersonID=:gibbonPersonID OR sequenceNumber=:sequenceNumber) AND NOT gibbonFinanceExpenseApproverID=:gibbonFinanceExpenseApproverID';
                    } else {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID);
                        $sql = 'SELECT * FROM gibbonFinanceExpenseApprover WHERE gibbonPersonID=:gibbonPersonID AND NOT gibbonFinanceExpenseApproverID=:gibbonFinanceExpenseApproverID';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error7';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'sequenceNumber' => $sequenceNumber, 'gibbonPersonIDUpdate' => $session->get('gibbonPersonID'), 'timestampUpdate' => date('Y-m-d H:i:s', time()), 'gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID);
                        $sql = 'UPDATE gibbonFinanceExpenseApprover SET gibbonPersonID=:gibbonPersonID, sequenceNumber=:sequenceNumber, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate=:timestampUpdate WHERE gibbonFinanceExpenseApproverID=:gibbonFinanceExpenseApproverID';
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
