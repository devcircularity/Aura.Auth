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
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\SettingGateway;

//Returns amount paid on an particular table/ID combo
function getAmountPaid($connection2, $guid, $foreignTable, $foreignTableID)
{
    $return = true;

    try {
        $data = array('foreignTable' => $foreignTable, 'foreignTableID' => $foreignTableID);
        $sql = 'SELECT gibbonPayment.* FROM gibbonPayment WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }
    if ($result) {
        $return = 0;
        while ($row = $result->fetch()) {
            $return += $row['amount'];
        }
    }

    return $return;
}

//Returns log associated with a particular expense
//If $gibbonPaymentID is not NULL, then only that ID's entry is included
function getPaymentLog($connection2, $guid, $foreignTable, $foreignTableID, $gibbonPaymentID = null, $feeTotal = null)
{
    global $session;

    $return = '';
    try {
        $data = array('foreignTable' => $foreignTable, 'foreignTableID' => $foreignTableID);
        $sql = 'SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment LEFT JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp, gibbonPaymentID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() < 1) {
        $return .= "<div class='error'>";
        $return .= __('There are no records to display.');
        $return .= '</div>';
    } else {
        $return .= "<table cellspacing='0' style='width: 100%'>";
        $return .= "<tr class='head'>";
        $return .= '<th>';
        $return .= __('Date');
        $return .= '</th>';
        $return .= '<th>';
        $return .= __('Payment Event');
        $return .= '</th>';
        $return .= '<th>';
        $return .= __('Amount').'<br/>';
        if ($session->get('currency') != '') {
            $return .= "<span style='font-style: italic; font-size: 85%'>".$session->get('currency').'</span>';
        }
        $return .= '</th>';
        $return .= '<th>';
        $return .= __('Type');
        $return .= '</th>';
        $return .= '<th>';
        $return .= __('Paid/Recorded By');
        $return .= '</th>';
        $return .= "<th style='width: 150px'>";
        $return .= __('Transaction ID');
        $return .= '</th>';
        $return .= '</tr>';

        $rowNum = 'odd';
        $count = 0;
        $paymentTotal = 0;

        $beforeCurrentDate = true;
        while ($row = $result->fetch()) {
            if ($row['gibbonPaymentID'] == $gibbonPaymentID or $gibbonPaymentID == null) {
                $beforeCurrentDate = false;
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                $return .= "<tr class=$rowNum>";
                $return .= '<td>';
                $return .= Format::date(substr($row['timestamp'], 0, 10));
                $return .= '</td>';
                $return .= '<td>';
                $return .= __($row['status']);
                $return .= '</td>';
                $return .= '<td>';
                $paymentTotal += $row['amount'];
                if (substr($session->get('currency'), 4) != '') {
                    $return .= substr($session->get('currency'), 4).' ';
                }
                $return .= number_format($row['amount'], 2, '.', ',');
                $return .= '</td>';
                $return .= '<td>';
                $return .= __($row['type']);
                $return .= '</td>';
                $return .= '<td>';
                $return .= Format::name('', $row['preferredName'], $row['surname'], 'Staff', false, true);
                $return .= '</td>';
                $return .= '<td>';
                $return .= $row['paymentTransactionID'];
                $return .= '</td>';
                $return .= '</tr>';
            } else {
                if ($beforeCurrentDate) {
                    $paymentTotal += $row['amount'];
                }
            }
        }
        $return .= "<tr style='height: 35px' class='current'>";
        $return .= "<td colspan=5 style='text-align: right'>";
        $return .= '<b>'.__('Total Payment On This Invoice:').'</b>';
        $return .= '</td>';
        $return .= '<td>';
        if (substr($session->get('currency'), 4) != '') {
            $return .= substr($session->get('currency'), 4).' ';
        }
        $return .= '<b>'.number_format($paymentTotal, 2, '.', ',').'</b>';
        $return .= '</td>';
        $return .= '</tr>';

        if (!empty($feeTotal) && $paymentTotal < $feeTotal ) {
            $return .= "<tr style='height: 35px' class='dull'>";
            $return .= "<td colspan=5 style='text-align: right'>";
            $return .= '<b>'.__('Outstanding Amount').':</b>';
            $return .= '</td>';
            $return .= '<td>';
            if (substr($session->get('currency'), 4) != '') {
                $return .= substr($session->get('currency'), 4).' ';
            }
            $return .= '<b>'.number_format($feeTotal - $paymentTotal, 2, '.', ',').'</b>';
            $return .= '</td>';
            $return .= '</tr>';
        }

        $return .= '</table>';
    }

    return $return;
}

//Create an entry in the payment log table, recording the details of a particular payment
function setPaymentLog($connection2, $guid, $foreignTable, $foreignTableID, $type, $status, $amount, $gateway = null, $onlineTransactionStatus = null, $paymentToken = null, $paymentPayerID = null, $paymentTransactionID = null, $paymentReceiptID = null, $timestamp = null)
{
    global $session;

    $return = true;

    if ($timestamp == null) {
        $timestamp = date('Y-m-d H:i:s');
    }
    $gibbonPersonID = $session->has('gibbonPersonID') ? $session->get('gibbonPersonID') : null;

    try {
        $data = array('foreignTable' => $foreignTable, 'foreignTableID' => $foreignTableID, 'gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'status' => $status, 'amount' => $amount, 'gateway' => $gateway, 'onlineTransactionStatus' => $onlineTransactionStatus, 'paymentToken' => $paymentToken, 'paymentPayerID' => $paymentPayerID, 'paymentTransactionID' => $paymentTransactionID, 'paymentReceiptID' => $paymentReceiptID, 'timestamp' => $timestamp);
        $sql = 'INSERT INTO gibbonPayment SET foreignTable=:foreignTable, foreignTableID=:foreignTableID, gibbonPersonID=:gibbonPersonID, type=:type, status=:status, amount=:amount, gateway=:gateway, onlineTransactionStatus=:onlineTransactionStatus, paymentToken=:paymentToken, paymentPayerID=:paymentPayerID, paymentTransactionID=:paymentTransactionID, paymentReceiptID=:paymentReceiptID, timestamp=:timestamp';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    $return = $connection2->lastInsertID();

    return $return;
}

//Checks log to see if approval is complete. Returns false (on error), none (if no completion), budget (if budget completion done or not required), school (if all complete)
function checkLogForApprovalComplete($guid, $gibbonFinanceExpenseID, $connection2)
{
    global $container;

    try {
        $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
        $sql = 'SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        return false;
    }

    if ($result->rowCount() != 1) {
        return false;
    } else {
        $row = $result->fetch();

        //Get settings for budget-level and school-level approval
        $settingGateway = $container->get(SettingGateway::class);
        $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');

        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            return false;
        } else {
            if ($row['status'] != 'Requested') { //Finished? Return
                return false;
            } else { //Not finished
                if ($row['statusApprovalBudgetCleared'] == 'N') { //Notify budget holders (e.g. access Full)
                    return 'none';
                } else { //School-level approval, what type is it?
                    if ($expenseApprovalType == 'One Of' or $expenseApprovalType == 'Two Of') { //One Of or Two Of, so alert all approvers
                        if ($expenseApprovalType == 'One Of') {
                            $expected = 1;
                        } else {
                            $expected = 2;
                        }
                        //Do we have correct number of approvals
                        try {
                            $dataTest = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sqlTest = "SELECT DISTINCT * FROM gibbonFinanceExpenseLog WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND action='Approval - Partial - School'";
                            $resultTest = $connection2->prepare($sqlTest);
                            $resultTest->execute($dataTest);
                        } catch (PDOException $e) {
                            return false;
                        }
                        if ($resultTest->rowCount() >= $expected) { //Yes - return "school"
                            return 'school';
                        } else { //No - return "budget"
                            return 'budget';
                        }
                    } elseif ($expenseApprovalType == 'Chain Of All') { //Chain of all
                        try {
                            $dataApprovers = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sqlApprovers = "SELECT gibbonPerson.gibbonPersonID AS g1, gibbonFinanceExpenseLog.gibbonPersonID AS g2 FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonFinanceExpenseApprover.gibbonPersonID AND gibbonFinanceExpenseLog.action='Approval - Partial - School' AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName";
                            $resultApprovers = $connection2->prepare($sqlApprovers);
                            $resultApprovers->execute($dataApprovers);
                        } catch (PDOException $e) {
                            return false;
                        }
                        $approvers = $resultApprovers->fetchAll();
                        $countTotal = $resultApprovers->rowCount();
                        $count = 0;
                        foreach ($approvers as $approver) {
                            if ($approver['g1'] == $approver['g2']) {
                                ++$count;
                            }
                        }

                        if ($count >= $countTotal) { //Yes - return "school"
                            return 'school';
                        } else { //No - return "budget"
                            return 'budget';
                        }
                    } else {
                        return false;
                    }
                }
            }
        }
    }



}

//Checks a certain expense request, and returns FALSE on error, TRUE if specified person can approve it.
function approvalRequired($guid, $gibbonPersonID, $gibbonFinanceExpenseID, $gibbonFinanceBudgetCycleID, $connection2, $locking = true)
{
    global $container;

    try {
        $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
        $sql = 'SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        return false;
    }

    if ($result->rowCount() != 1) {
        echo $result->rowCount();
        exit();

        return false;
    } else {
        $row = $result->fetch();

        //Get settings for budget-level and school-level approval
        $settingGateway = $container->get(SettingGateway::class);
        $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');

        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            return false;
        } else {
            if ($row['status'] != 'Requested') { //Finished? Return
                return false;
            } else { //Not finished
                if ($row['statusApprovalBudgetCleared'] == 'N') {
                    //Get Full budget people
                    try {
                        $dataBudget = array('gibbonFinanceBudgetID' => $row['gibbonFinanceBudgetID'], 'gibbonPersonID' => $gibbonPersonID);
                        $sqlBudget = "SELECT gibbonPersonID FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE access='Full' AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID";
                        $resultBudget = $connection2->prepare($sqlBudget);
                        $resultBudget->execute($dataBudget);
                    } catch (PDOException $e) {
                        return false;
                    }

                    if ($resultBudget->rowCount() != 1) {
                        return false;
                    } else {
                        return true;
                    }
                } else { //School-level approval, what type is it?
                    if ($expenseApprovalType == 'One Of' or $expenseApprovalType == 'Two Of') { //One Of or Two Of, so alert all approvers
                        try {
                            $dataApprovers = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlApprovers = "SELECT gibbonPerson.gibbonPersonID FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFinanceExpenseApprover.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                            $resultApprovers = $connection2->prepare($sqlApprovers);
                            $resultApprovers->execute($dataApprovers);
                        } catch (PDOException $e) {
                            return false;
                        }

                        if ($resultApprovers->rowCount() != 1) {
                            return false;
                        } else {
                            //Check of already approved at school-level
                            try {
                                $dataApproval = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $gibbonPersonID);
                                $sqlApproval = "SELECT * FROM gibbonFinanceExpenseLog WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonPersonID=:gibbonPersonID AND action='Approval - Partial - School'";
                                $resultApproval = $connection2->prepare($sqlApproval);
                                $resultApproval->execute($dataApproval);
                            } catch (PDOException $e) {
                                return false;
                            }
                            if ($resultApproval->rowCount() > 0) {
                                return false;
                            } else {
                                return true;
                            }
                        }
                    } elseif ($expenseApprovalType == 'Chain Of All') { //Chain of all
                        //Get notifiers in sequence
                        try {
                            $dataApprovers = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sqlApprovers = "SELECT gibbonPerson.gibbonPersonID AS g1, gibbonFinanceExpenseLog.gibbonPersonID AS g2 FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonFinanceExpenseApprover.gibbonPersonID AND gibbonFinanceExpenseLog.action='Approval - Partial - School' AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName";
                            $resultApprovers = $connection2->prepare($sqlApprovers);
                            $resultApprovers->execute($dataApprovers);
                        } catch (PDOException $e) {
                            return false;
                        }
                        if ($resultApprovers->rowCount() < 1) {
                            return false;
                        } else {
                            $approvers = $resultApprovers->fetchAll();
                            $gibbonPersonIDNext = null;
                            foreach ($approvers as $approver) {
                                if ($approver['g1'] != $approver['g2']) {
                                    if (is_null($gibbonPersonIDNext)) {
                                        $gibbonPersonIDNext = $approver['g1'];
                                    }
                                }
                            }

                            if (is_null($gibbonPersonIDNext)) {
                                return false;
                            } else {
                                if ($gibbonPersonIDNext != $gibbonPersonID) {
                                    return false;
                                } else {
                                    return true;
                                }
                            }
                        }
                    } else {
                        return false;
                    }
                }
            }
        }
    }

}

//Issues correct notificaitons for give expense, depending on circumstances. Returns FALSE on error, TRUE if it did its job.
//Tries to avoid issue duplicate notifications
function setExpenseNotification($guid, $gibbonFinanceExpenseID, $gibbonFinanceBudgetCycleID, $connection2)
{
    global $container, $pdo, $session;
    
    $notificationSender = $container->get(NotificationSender::class);

    try {
        $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
        $sql = 'SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpense.gibbonFinanceExpenseID=:gibbonFinanceExpenseID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        return false;
    }

    if ($result->rowCount() != 1) {
        return false;
    } else {
        $row = $result->fetch();

        //Get settings for budget-level and school-level approval
        $settingGateway = $container->get(SettingGateway::class);
        $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');

        $personName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);

        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            return false;
        } else {
            if ($row['status'] != 'Requested') { //Finished? Return
                return true;
            } else { //Not finished
                $notificationText = __('{person} has requested expense approval for {title} in budget {budgetName}.', ['person' => $personName, 'title' => $row['title'], 'budgetName' => $row['budget']]);

                if ($row['statusApprovalBudgetCleared'] == 'N') { //Notify budget holders (e.g. access Full)
                    //Get Full budget people, and notify them
                    try {
                        $dataBudget = array('gibbonFinanceBudgetID' => $row['gibbonFinanceBudgetID']);
                        $sqlBudget = "SELECT gibbonPersonID FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE access='Full' AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID";
                        $resultBudget = $connection2->prepare($sqlBudget);
                        $resultBudget->execute($dataBudget);
                    } catch (PDOException $e) {
                        return false;
                    }
                    if ($resultBudget->rowCount() < 1) {
                        return false;
                    } else {
                        while ($rowBudget = $resultBudget->fetch()) {
                            $notificationSender->addNotification($rowBudget['gibbonPersonID'], $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenses_manage_approve.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=".$row['gibbonFinanceBudgetID']);
                            
                        }
                        $notificationSender->sendNotifications();
                        return true;
                    }
                } else { //School-level approval, what type is it?
                    if ($expenseApprovalType == 'One Of' or $expenseApprovalType == 'Two Of') { //One Of or Two Of, so alert all approvers
                        try {
                            $dataApprovers = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sqlApprovers = "SELECT gibbonPerson.gibbonPersonID, gibbonFinanceExpenseLog.gibbonFinanceExpenseLogID FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName";
                            $resultApprovers = $connection2->prepare($sqlApprovers);
                            $resultApprovers->execute($dataApprovers);
                        } catch (PDOException $e) {
                            return false;
                        }
                        if ($resultApprovers->rowCount() < 1) {
                            return false;
                        } else {
                            while ($rowApprovers = $resultApprovers->fetch()) {
                                if ($rowApprovers['gibbonFinanceExpenseLogID'] == '') {
                                    $notificationSender->addNotification($rowApprovers['gibbonPersonID'], $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenses_manage_approve.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=".$row['gibbonFinanceBudgetID']);
                                }
                            }

                            $notificationSender->sendNotifications();
                            return true;
                        }
                    } elseif ($expenseApprovalType == 'Chain Of All') { //Chain of all
                        //Get notifiers in sequence
                        try {
                            $dataApprovers = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sqlApprovers = "SELECT gibbonPerson.gibbonPersonID AS g1, gibbonFinanceExpenseLog.gibbonPersonID AS g2 FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFinanceExpenseLog ON (gibbonFinanceExpenseLog.gibbonPersonID=gibbonFinanceExpenseApprover.gibbonPersonID AND gibbonFinanceExpenseLog.action='Approval - Partial - School' AND gibbonFinanceExpenseLog.gibbonFinanceExpenseID=:gibbonFinanceExpenseID) WHERE gibbonPerson.status='Full' ORDER BY sequenceNumber, surname, preferredName";
                            $resultApprovers = $connection2->prepare($sqlApprovers);
                            $resultApprovers->execute($dataApprovers);
                        } catch (PDOException $e) {
                            return false;
                        }
                        if ($resultApprovers->rowCount() < 1) {
                            return false;
                        } else {
                            $approvers = $resultApprovers->fetchAll();
                            $gibbonPersonIDNext = null;
                            foreach ($approvers as $approver) {
                                if ($approver['g1'] != $approver['g2']) {
                                    if (is_null($gibbonPersonIDNext)) {
                                        $gibbonPersonIDNext = $approver['g1'];
                                    }
                                }
                            }

                            if (is_null($gibbonPersonIDNext)) {
                                return false;
                            } else {
                                $notificationSender->addNotification($gibbonPersonIDNext, $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenses_manage_approve.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=&gibbonFinanceBudgetID2=".$row['gibbonFinanceBudgetID']);
                                $notificationSender->sendNotifications();

                                return true;
                            }
                        }
                    } else {
                        return false;
                    }
                }
            }
        }
    }


}

//Returns all budgets a person is linked to, as well as their access rights to that budget
function getBudgetsByPerson($connection2, $gibbonPersonID, $gibbonFinanceBudgetID = '')
{
    $return = false;

    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        if ($gibbonFinanceBudgetID == '') {
            $sql = "SELECT * FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonPersonID=:gibbonPersonID AND active='Y' ORDER BY name";
        } else {
            $data['gibbonFinanceBudgetID'] = $gibbonFinanceBudgetID;
            $sql = "SELECT * FROM gibbonFinanceBudget JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND active='Y' ORDER BY name";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    $count = 0;
    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$count][0] = $row['gibbonFinanceBudgetID'];
            $return[$count][1] = $row['name'];
            $return[$count][2] = $row['access'];
            ++$count;
        }
    }

    return $return;
}

//Returns all active budgets
function getBudgets($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = "SELECT * FROM gibbonFinanceBudget WHERE active='Y' ORDER BY name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    $count = 0;
    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$count][0] = $row['gibbonFinanceBudgetID'];
            $return[$count][1] = $row['name'];
            ++$count;
        }
    }

    return $return;
}

//Take a budget cycle, and return the previous one, or false if none
function getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2)
{
    $output = false;


        $dataCycle = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
        $sqlCycle = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
        $resultCycle = $connection2->prepare($sqlCycle);
        $resultCycle->execute($dataCycle);
    if ($resultCycle->rowCount() == 1) {
        $rowCycle = $resultCycle->fetch();
        $output = $rowCycle['name'];
    }

    return $output;
}

//Take a budget cycle, and return the previous one, or false if none
function getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2)
{
    $output = false;


        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
        $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    if ($result->rowcount() == 1) {
        $row = $result->fetch();

            $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
            $sqlPrevious = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE sequenceNumber<:sequenceNumber ORDER BY sequenceNumber DESC';
            $resultPrevious = $connection2->prepare($sqlPrevious);
            $resultPrevious->execute($dataPrevious);
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonFinanceBudgetCycleID'];
        }
    }

    return $output;
}

//Take a budget cycle, and return the previous one, or false if none
function getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2)
{
    $output = false;


        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
        $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    if ($result->rowcount() == 1) {
        $row = $result->fetch();

            $dataPrevious = array('sequenceNumber' => $row['sequenceNumber']);
            $sqlPrevious = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE sequenceNumber>:sequenceNumber ORDER BY sequenceNumber ASC';
            $resultPrevious = $connection2->prepare($sqlPrevious);
            $resultPrevious->execute($dataPrevious);
        if ($resultPrevious->rowCount() >= 1) {
            $rowPrevious = $resultPrevious->fetch();
            $output = $rowPrevious['gibbonFinanceBudgetCycleID'];
        }
    }

    return $output;
}

function invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $currency = '', $email = false, $preview = false)
{
    global $session, $container;

    $settingGateway = $container->get(SettingGateway::class);

    $return = '';

    //Get currency
    $currency = $settingGateway->getSettingByScope('System', 'currency');
    $invoiceeNameStyle = $settingGateway->getSettingByScope('Finance', 'invoiceeNameStyle');

    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID2' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
        $sql = 'SELECT gibbonPerson.gibbonPersonID, studentID, officialName, surname, preferredName, gibbonFinanceInvoice.*, companyContact, companyEmail, companyName, companyAddress, gibbonFormGroup.name AS formGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    if ($result->rowCount() == 1) {
        //Let's go!
        $row = $result->fetch();

        //Invoice Text
        $invoiceText = $settingGateway->getSettingByScope('Finance', 'invoiceText');
        if ($invoiceText != '') {
            $return .= '<p>';
            $return .= $invoiceText;
            $return .= '</p>';
        }

        $style = '';
        $style2 = '';
        $style3 = '';
        $style4 = '';
        if ($email == true) {
            $style = 'border-top: 1px solid #333; ';
            $style2 = 'border-bottom: 1px solid #333; ';
            $style3 = 'background-color: #f0f0f0; ';
            $style4 = 'background-color: #f6f6f6; ';
        }
        //Invoice Details
        $return .= "<table cellspacing='0' style='width: 100%; font-size: 12px;'>";
        $return .= '<tr>';
        $return .= "<td style='padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style3' colspan=3>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Invoice To').' ('.__($row['invoiceTo']).')</span><br/>';
        if ($row['invoiceTo'] == 'Company') {
            $invoiceTo = '';
            if ($row['companyContact'] != '') {
                $invoiceTo .= '<b>'.$row['companyContact'].'</b>, ';
            }
            if ($row['companyEmail'] != '') {
                $invoiceTo .= $row['companyEmail'].', ';
            }
            if ($row['companyName'] != '') {
                $invoiceTo .= $row['companyName'].', ';
            }
            if ($row['companyAddress'] != '') {
                $invoiceTo .= $row['companyAddress'].', ';
            }
            $return .= substr($invoiceTo, 0, -2);
        } else {
            try {
                $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) AND parent.status='Full' ORDER BY contactPriority, surname, preferredName";
                $resultParents = $connection2->prepare($sqlParents);
                $resultParents->execute($dataParents);
            } catch (PDOException $e) {
            }
            if ($resultParents->rowCount() < 1) {
                $return .= "<div class='warning'>".__('There are no family members available to send this receipt to.').'</div>';
            } else {
                $return .= "<ul style='margin-top: 3px; margin-bottom: 3px'>";
                while ($rowParents = $resultParents->fetch()) {
                    $return .= '<li>';
                    $invoiceTo = '';
                    $invoiceTo .= '<b>'.Format::name(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false).'</b>, ';
                    if ($rowParents['email'] != '') {
                        $invoiceTo .= $rowParents['email'].', ';
                    }
                    if ($rowParents['address1'] != '') {
                        $invoiceTo .= $rowParents['address1'].', ';
                        if ($rowParents['address1District'] != '') {
                            $invoiceTo .= $rowParents['address1District'].', ';
                        }
                        if ($rowParents['address1Country'] != '') {
                            $invoiceTo .= $rowParents['address1Country'].', ';
                        }
                    } else {
                        $invoiceTo .= $rowParents['homeAddress'].', ';
                        if ($rowParents['homeAddressDistrict'] != '') {
                            $invoiceTo .= $rowParents['homeAddressDistrict'].', ';
                        }
                        if ($rowParents['homeAddressCountry'] != '') {
                            $invoiceTo .= $rowParents['homeAddressCountry'].', ';
                        }
                    }
                    $return .= substr($invoiceTo, 0, -2);
                    $return .= '</li>';
                }
                $return .= '</ul>';
            }
        }
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= "<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style4'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Fees For').'</span><br/>';
        if ($invoiceeNameStyle =='Official Name') {
            $return .= htmlPrep($row['officialName'])."<br/><span style='font-style: italic; font-size: 85%'>".__('Form Group').': '.$row['formGroup'].'</span><br/>';
        }
        else {
            $return .= Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true)."<br/><span style='font-style: italic; font-size: 85%'>".__('Form Group').': '.$row['formGroup'].'</span><br/>';
        }
        if ($row['studentID'] != '') {
            $return .= "<div style='font-size: 115%; font-weight: bold; margin-top: 10px'>".__('Student ID')."</div>";
            $return .= "<span style='font-style: italic; font-size: 85%'>".$row['studentID']."</span>";
        }
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Status').'</span><br/>';
        $return .= __($row['status']);
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Schedule').'</span><br/>';
        if ($row['billingScheduleType'] == 'Ad Hoc') {
            $return .= __('Ad Hoc');
        } else {
            try {
                $dataSched = array('gibbonFinanceBillingScheduleID' => $row['gibbonFinanceBillingScheduleID']);
                $sqlSched = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
                $resultSched = $connection2->prepare($sqlSched);
                $resultSched->execute($dataSched);
            } catch (PDOException $e) {
            }
            if ($resultSched->rowCount() == 1) {
                $rowSched = $resultSched->fetch();
                $return .= $rowSched['name'];
            }
        }
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= "<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style2 $style3'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Invoice Issue Date').'</span><br/>';
        $return .= Format::date($row['invoiceIssueDate']);
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Due Date').'</span><br/>';
        $return .= Format::date($row['invoiceDueDate']);
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Invoice Number').'</span><br/>';
        $invoiceNumber = $settingGateway->getSettingByScope('Finance', 'invoiceNumber');
        if ($invoiceNumber == 'Person ID + Invoice ID') {
            $return .= ltrim($row['gibbonPersonID'], '0').'-'.ltrim($gibbonFinanceInvoiceID, '0');
        } elseif ($invoiceNumber == 'Student ID + Invoice ID') {
            $return .= ltrim($row['studentID'], '0').'-'.ltrim($gibbonFinanceInvoiceID, '0');
        } else {
            $return .= ltrim($gibbonFinanceInvoiceID, '0');
        }
        $return .= '</td>';
        $return .= '</tr>';
        if($row['notes']) {
            $return .= '<tr>';
            $return .= "<td colspan=3 style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style2 $style3'>";
            $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Notes').'</span><br/>';
            $return .= $row['notes'];
            $return .= '</td>';
            $return .= '</tr>';
        }
        $return .= '</table>';

        try {
            $dataFees['gibbonFinanceInvoiceID1'] = $row['gibbonFinanceInvoiceID'];

            if ($preview) { //Get fees from gibbonFinanceFee
                //Standard
                $sqlFees = "(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID1 AND feeType='Standard')";
            } else { //Get fees from gibbonFinanceInvoiceFee
                //Standard
                $sqlFees = "(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID1 AND feeType='Standard')";
            }
            //Ad Hoc
            $sqlFees .= ' UNION ';
            $dataFees['gibbonFinanceInvoiceID2'] = $row['gibbonFinanceInvoiceID'];
            $sqlFees .= "(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID2 AND feeType='Ad Hoc')";
            $sqlFees .= ' ORDER BY sequenceNumber';
            $resultFees = $connection2->prepare($sqlFees);
            $resultFees->execute($dataFees);
        } catch (PDOException $e) {
        }
        if ($resultFees->rowCount() < 1) {
            $return .= "<div class='error'>";
            $return .= __('There are no records to display.');
            $return .= '</div>';
        } else {
            $feeTotal = 0;

            //Fee table
            $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
            $return .= __('Fee Table');
            $return .= '</h3>';

            $return .= "<table cellspacing='0' style='width: 100%; font-size: 12px; $style4'>";
            $return .= "<tr class='head'>";
            $return .= "<th style='text-align: left; padding-left: 10px'>";
            $return .= __('Name');
            $return .= '</th>';
            $return .= "<th style='text-align: left'>";
            $return .= __('Category');
            $return .= '</th>';
            $return .= "<th style='text-align: left'>";
            $return .= __('Description');
            $return .= '</th>';
            $return .= "<th style='text-align: left'>";
            $return .= __('Fee').'<br/>';
            if ($currency != '') {
                $return .= "<span style='font-style: italic; font-size: 85%'>".$currency.'</span>';
            }
            $return .= '</th>';
            $return .= '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($rowFees = $resultFees->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                $return .= "<tr style='height: 25px' class=$rowNum>";
                $return .= "<td style='padding-left: 10px'>";
                $return .= $rowFees['name'];
                $return .= '</td>';
                $return .= '<td>';
                $return .= $rowFees['category'];
                $return .= '</td>';
                $return .= '<td>';
                $return .= $rowFees['description'];
                $return .= '</td>';
                $return .= '<td>';
                if (substr($currency, 4) != '') {
                    $return .= substr($currency, 4).' ';
                }
                $return .= number_format($rowFees['fee'], 2, '.', ',');
                $feeTotal += $rowFees['fee'];
                $return .= '</td>';
                $return .= '</tr>';
            }
            $return .= "<tr style='height: 35px' class='current'>";
            $return .= "<td colspan=3 style='text-align: right; $style2'>";
            $return .= '<b>'.__('Invoice Total:').'</b>';
            $return .= '</td>';
            $return .= "<td style='$style2'>";
            if (substr($currency, 4) != '') {
                $return .= substr($currency, 4).' ';
            }
            $return .= '<b>'.number_format($feeTotal, 2, '.', ',').'</b>';
            $return .= '</td>';
            $return .= '</tr>';
            if ($row['status'] == 'Paid - Partial') {
                $return .= "<tr style='height: 35px' class='warning'>";
                $return .= "<td colspan=3 style='text-align: right; $style2'>";
                $return .= '<b>'.__('Amount Outstanding:').'</b>';
                $return .= '</td>';
                $return .= "<td style='$style2'>";
                if (substr($currency, 4) != '') {
                    $return .= substr($currency, 4).' ';
                }
                $return .= '<b>'.number_format(($feeTotal-$row['paidAmount']), 2, '.', ',').'</b>';
                $return .= '</td>';
                $return .= '</tr>';
            }
            $return .= '</table>';
        }

        //Online payment
        $enablePayments = $settingGateway->getSettingByScope('System', 'enablePayments');
        $paymentGateway = $settingGateway->getSettingByScope('System', 'paymentGateway');

        if (!$preview && $enablePayments == 'Y' and $row['status'] != 'Paid' and $row['status'] != 'Cancelled' and $row['status'] != 'Refunded') {
            $financeOnlinePaymentEnabled = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentEnabled');
            $financeOnlinePaymentThreshold = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentThreshold');
            if ($financeOnlinePaymentEnabled == 'Y') {
                $return .= "<h3 style='margin-top: 40px'>";
                $return .= __('Online Payment');
                $return .= '</h3>';
                $return .= '<p>';
                if ($financeOnlinePaymentThreshold == '' or $financeOnlinePaymentThreshold >= $feeTotal) {
                    $return .= sprintf(__('Payment can be made by credit card, using our secure %2$s payment gateway. When you press Pay Now below, you will be directed to a %1$s page from where you can use %2$s in order to make payment. You can continue with payment through %1$s whether you are logged in or not. During this process we do not see or store your credit card details.'), $session->get('systemName'), $paymentGateway).' ';
                    $return .= "<a style='font-weight: bold' href='".$session->get('absoluteURL')."/index.php?q=/modules/Finance/invoices_payOnline.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=".$row['key']."'>".__('Pay Now').'.</a>';
                } else {
                    $return .= "<div class='warning'>".__('Payment is not permitted for this invoice, as the total amount is greater than the permitted online payment threshold.').'</div>';
                }
                $return .= '</p>';
            }
        }

        //Invoice Notes
        $invoiceNotes = $settingGateway->getSettingByScope('Finance', 'invoiceNotes');
        if ($invoiceNotes != '') {
            $return .= "<h3 style='margin-top: 40px'>";
            $return .= __('Notes');
            $return .= '</h3>';
            $return .= '<p>';
            $return .= $invoiceNotes;
            $return .= '</p>';
        }

        return $return;
    }
}

/**
 * Get HTML receipt contents for emailing
 *
 * $receiptNumber is the numerical position (counting from 0) of the payment within a series of payments.
 * NULL $receipt Number means it is an old receipt, prior to multiple payments (e.g. before v11)
 *
 * @param string $guid
 * @param Connection $connection2
 * @param string $gibbonFinanceInvoiceID
 * @param string $gibbonSchoolYearID
 * @param string $currency
 * @param bool $email
 * @param int $receiptNumber
 * @return void
 */
function receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $currency = '', $email = false, $receiptNumber = null)
{
    global $container;

    $settingGateway = $container->get(SettingGateway::class);

    $return = '';

    //Get currency
    $currency = $settingGateway->getSettingByScope('System', 'currency');
    $invoiceeNameStyle = $settingGateway->getSettingByScope('Finance', 'invoiceeNameStyle');

    try {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID2' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
        $sql = 'SELECT gibbonPerson.gibbonPersonID, studentID, officialName, surname, preferredName, gibbonFinanceInvoice.*, companyContact, companyEmail, companyName, companyAddress, gibbonFormGroup.name AS formGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $return = false;
    }

    if ($result->rowCount() == 1) {
        //Let's go!
        $row = $result->fetch();

        //Receipt Text
        $receiptText = $settingGateway->getSettingByScope('Finance', 'receiptText');
        if ($receiptText != '') {
            $return .= '<p>';
            $return .= $receiptText;
            $return .= '</p>';
        }

        $style = '';
        $style2 = '';
        $style3 = '';
        $style4 = '';
        if ($email == true) {
            $style = 'border-top: 1px solid #333; ';
            $style2 = 'border-bottom: 1px solid #333; ';
            $style3 = 'background-color: #f0f0f0; ';
            $style4 = 'background-color: #f6f6f6; ';
        }
        //Receipt Details
        $return .= "<table cellspacing='0' style='width: 100%; font-size: 12px;'>";
        $return .= '<tr>';
        $return .= "<td style='padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style3' colspan=3>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Receipt To').' ('._($row['invoiceTo']).')</span><br/>';
        if ($row['invoiceTo'] == 'Company') {
            $invoiceTo = '';
            if ($row['companyContact'] != '') {
                $invoiceTo .= '<b>'.$row['companyContact'].'</b>, ';
            }
            if ($row['companyEmail'] != '') {
                $invoiceTo .= $row['companyEmail'].', ';
            }
            if ($row['companyName'] != '') {
                $invoiceTo .= $row['companyName'].', ';
            }
            if ($row['companyAddress'] != '') {
                $invoiceTo .= $row['companyAddress'].', ';
            }
            $return .= substr($invoiceTo, 0, -2);
        } else {
            try {
                $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) AND parent.status='Full' ORDER BY contactPriority, surname, preferredName";
                $resultParents = $connection2->prepare($sqlParents);
                $resultParents->execute($dataParents);
            } catch (PDOException $e) {
            }
            if ($resultParents->rowCount() < 1) {
                $return .= "<div class='warning'>".__('There are no family members available to send this receipt to.').'</div>';
            } else {
                $return .= "<ul style='margin-top: 3px; margin-bottom: 3px'>";
                while ($rowParents = $resultParents->fetch()) {
                    $return .= '<li>';
                    $invoiceTo = '';
                    $invoiceTo .= '<b>'.Format::name(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false).'</b>, ';
                    if ($rowParents['email'] != '') {
                        $invoiceTo .= $rowParents['email'].', ';
                    }
                    if ($rowParents['address1'] != '') {
                        $invoiceTo .= $rowParents['address1'].', ';
                        if ($rowParents['address1District'] != '') {
                            $invoiceTo .= $rowParents['address1District'].', ';
                        }
                        if ($rowParents['address1Country'] != '') {
                            $invoiceTo .= $rowParents['address1Country'].', ';
                        }
                    } else {
                        $invoiceTo .= $rowParents['homeAddress'].', ';
                        if ($rowParents['homeAddressDistrict'] != '') {
                            $invoiceTo .= $rowParents['homeAddressDistrict'].', ';
                        }
                        if ($rowParents['homeAddressCountry'] != '') {
                            $invoiceTo .= $rowParents['homeAddressCountry'].', ';
                        }
                    }
                    $return .= substr($invoiceTo, 0, -2);
                    $return .= '</li>';
                }
                $return .= '</ul>';
            }
        }
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= "<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style4'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Fees For').'</span><br/>';
        if ($invoiceeNameStyle =='Official Name') {
            $return .= htmlPrep($row['officialName'])."<br/><span style='font-style: italic; font-size: 85%'>".__('Form Group').': '.$row['formGroup'].'</span><br/>';
        }
        else {
            $return .= Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true)."<br/><span style='font-style: italic; font-size: 85%'>".__('Form Group').': '.$row['formGroup'].'</span><br/>';
        }
        if ($row['studentID'] != '') {
            $return .= "<div style='font-size: 115%; font-weight: bold; margin-top: 10px'>".__('Student ID')."</div>";
            $return .= "<span style='font-style: italic; font-size: 85%'>".$row['studentID']."</span>";
        }
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Status').'</span><br/>';
        if ($receiptNumber === null) { //Old style receipt, before multiple payments
            $return .= __($row['status']);
        } else {
            $paymentFail = false;
            if (is_numeric($receiptNumber) == false || $receiptNumber < 0) {
                $paymentFail = true;
            } else {
                try {
                    $dataPayment = array('foreignTable' => 'gibbonFinanceInvoice', 'foreignTableID' => $gibbonFinanceInvoiceID);
                    $sqlPayment = "SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment LEFT JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID LIMIT $receiptNumber, 1";
                    $resultPayment = $connection2->prepare($sqlPayment);
                    $resultPayment->execute($dataPayment);
                } catch (PDOException $e) {
                    $paymentFail = true;
                }

                if ($resultPayment->rowCount() != 1) {
                    $paymentFail = true;
                } else {
                    $rowPayment = $resultPayment->fetch();
                    $return .= __($row['status']);
                    if ($row['status'] == 'Paid') {
                        $return .= ' ('.__($rowPayment['status']).')';
                    }
                }
            }
        }
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style4'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Schedule').'</span><br/>';
        if ($row['billingScheduleType'] == 'Ad Hoc') {
            $return .= __('Ad Hoc');
        } else {
            try {
                $dataSched = array('gibbonFinanceBillingScheduleID' => $row['gibbonFinanceBillingScheduleID']);
                $sqlSched = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
                $resultSched = $connection2->prepare($sqlSched);
                $resultSched->execute($dataSched);
            } catch (PDOException $e) {
            }
            if ($resultSched->rowCount() == 1) {
                $rowSched = $resultSched->fetch();
                $return .= $rowSched['name'];
            }
        }
        $return .= '</td>';
        $return .= '</tr>';
        $return .= '<tr>';
        $return .= "<td style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style2 $style3'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Due Date').'</span><br/>';
        $return .= Format::date($row['invoiceDueDate']);
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Date Paid').'</span><br/>';
        $return .= Format::date($row['paidDate']);
        $return .= '</td>';
        $return .= "<td style='width: 33%; padding-top: 15px; vertical-align: top; $style $style2 $style3'>";
        $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Invoice Number').'</span><br/>';
        $invoiceNumber = $settingGateway->getSettingByScope('Finance', 'invoiceNumber');
        if ($invoiceNumber == 'Person ID + Invoice ID') {
            $return .= ltrim($row['gibbonPersonID'], '0').'-'.ltrim($gibbonFinanceInvoiceID, '0');
        } elseif ($invoiceNumber == 'Student ID + Invoice ID') {
            $return .= ltrim($row['studentID'], '0').'-'.ltrim($gibbonFinanceInvoiceID, '0');
        } else {
            $return .= ltrim($gibbonFinanceInvoiceID, '0');
        }
        if ($receiptNumber !== null) {
            $return .= '<br/>';
            $return .= "<div style='font-size: 115%; font-weight: bold; margin-top: 10px'>".__('Receipt Number (on this invoice)')."</div>";
            $return .= ($receiptNumber + 1);
        }
        $return .= '</td>';
        if($row['notes']) {
            $return .= '<tr>';
            $return .= "<td colspan=3 style='width: 33%; padding-top: 15px; padding-left: 10px; vertical-align: top; $style $style2 $style3'>";
            $return .= "<span style='font-size: 115%; font-weight: bold'>".__('Notes').'</span><br/>';
            $return .= $row['notes'];
            $return .= '</td>';
            $return .= '</tr>';
        }
        $return .= '</tr>';
        $return .= '</table>';

        //Check itemisation status
        $hideItemisation = $settingGateway->getSettingByScope('Finance', 'hideItemisation');

        try {
            $dataFees['gibbonFinanceInvoiceID'] = $row['gibbonFinanceInvoiceID'];
            $sqlFees = 'SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber';
            $resultFees = $connection2->prepare($sqlFees);
            $resultFees->execute($dataFees);
        } catch (PDOException $e) {
        }
        if ($resultFees->rowCount() < 1) {
            $return .= "<div class='error'>";
            $return .= __('There are no records to display.');
            $return .= '</div>';
        } else {
            $feeTotal = 0;

            if ($hideItemisation != 'Y') { //Do not hide itemisation
                //Fee table
                $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                $return .= __('Fee Table');
                $return .= '</h3>';

                $return .= "<table cellspacing='0' style='width: 100%; font-size: 12px; $style4'>";
                $return .= "<tr class='head'>";
                $return .= "<th style='text-align: left; padding-left: 10px'>";
                $return .= __('Name');
                $return .= '</th>';
                $return .= "<th style='text-align: left'>";
                $return .= __('Category');
                $return .= '</th>';
                $return .= "<th style='text-align: left'>";
                $return .= __('Description');
                $return .= '</th>';
                $return .= "<th style='text-align: left; width: 150px'>";
                $return .= __('Fee').'<br/>';
                if ($currency != '') {
                    $return .= "<span style='font-style: italic; font-size: 85%'>".$currency.'</span>';
                }
                $return .= '</th>';
                $return .= '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($rowFees = $resultFees->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    $return .= "<tr style='height: 25px' class=$rowNum>";
                    $return .= "<td style='padding-left: 10px'>";
                    $return .= $rowFees['name'];
                    $return .= '</td>';
                    $return .= '<td>';
                    $return .= $rowFees['category'];
                    $return .= '</td>';
                    $return .= '<td>';
                    $return .= $rowFees['description'];
                    $return .= '</td>';
                    $return .= '<td>';
                    if (substr($currency, 4) != '') {
                        $return .= substr($currency, 4).' ';
                    }
                    $return .= number_format($rowFees['fee'], 2, '.', ',');
                    $feeTotal += $rowFees['fee'];
                    $return .= '</td>';
                    $return .= '</tr>';
                }
                $return .= "<tr style='height: 35px' class='current'>";
                $return .= "<td colspan=3 style='text-align: right; $style2'>";
                $return .= '<b>'.__('Invoice Total:').'</b>';
                $return .= '</td>';
                $return .= "<td style='$style2'>";
                if (substr($currency, 4) != '') {
                    $return .= substr($currency, 4).' ';
                }
                $return .= '<b>'.number_format($feeTotal, 2, '.', ',').'</b>';
                $return .= '</td>';
                $return .= '</tr>';
                $return .= '</table>';
            } else {
                $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                $return .= __('Amount Due');
                $return .= '</h3>';
                while ($rowFees = $resultFees->fetch()) {
                    $feeTotal += $rowFees['fee'];
                }
                $return .= "<p style='margin-top: 10px; text-align: right'>";
                $return .= __('Invoice Total').': ';
                if (substr($currency, 4) != '') {
                    $return .= substr($currency, 4).' ';
                }
                $return .= '<b>'.number_format($feeTotal, 2, '.', ',').'</b>';
                $return .= '</p>';
            }
        }

        //Payment details
        if ($receiptNumber === null) { //Old style receipt, before multiple payments
            $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
            $return .= __('Payment Details');
            $return .= '</h3>';
            $return .= "<p style='margin-top: 10px; text-align: right; $style4'>";
            $return .= __('Payment Total').': ';
            if (substr($currency, 4) != '') {
                $return .= substr($currency, 4).' ';
            }
            $return .= '<b>'.number_format($row['paidAmount'], 2, '.', ',').'</b>';
            $return .= '</p>';
        } else { //New style receipt, post multiple payments
            if ($hideItemisation != 'Y') { //Do not hide itemisation
                $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                $return .= __('Payment Details');
                $return .= '</h3>';
                if ($paymentFail) {
                    $return .= "<div class='error'>";
                    $return .= __('There are no records to display.');
                    $return .= '</div>';
                } else {
                    $return .= "<div style='font-size: 12px; $style4'>";
                    $return .= getPaymentLog($connection2, $guid, 'gibbonFinanceInvoice', $gibbonFinanceInvoiceID, $rowPayment['gibbonPaymentID']);
                    $return .= '</div>';
                }
            } else {
                $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                $return .= __('Amount Paid');
                $return .= '</h3>';
                $return .= "<div style='font-size: 12px; $style4'>";
                if ($paymentFail) {
                    $return .= "<div class='error'>";
                    $return .= __('There are no records to display.');
                    $return .= '</div>';
                } else {
                    $return .= "<p style='margin-top: 10px; text-align: right'>";
                    $return .= __('Payment Total').': ';
                    if (substr($currency, 4) != '') {
                        $return .= substr($currency, 4).' ';
                    }
                    $return .= '<b>'.number_format($rowPayment['amount'], 2, '.', ',').'</b>';
                    $return .= '</p>';
                }
                $return .= '</div>';
            }
        }

        //Display balance
        if ($row['status'] == 'Paid' or $row['status'] == 'Paid - Partial' or $row['status'] == 'Refunded') {
            if (@$rowPayment['status'] == 'Partial') {
                if ($receiptNumber !== null) { //New style receipt, with multiple payments
                    $balanceFail = false;
                    $amountPaid = 0;
                    //Get amount paid until this point
                    try {
                        $dataPayment2 = array('foreignTable' => 'gibbonFinanceInvoice', 'foreignTableID' => $gibbonFinanceInvoiceID);
                        $sqlPayment2 = 'SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment LEFT JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp LIMIT 0, '.($receiptNumber + 1);
                        $resultPayment2 = $connection2->prepare($sqlPayment2);
                        $resultPayment2->execute($dataPayment2);
                    } catch (PDOException $e) {
                        $balanceFail = true;
                    }

                    if ($resultPayment2->rowCount() < 1) {
                        $paymentFail = true;
                    } else {
                        while ($rowPayment2 = $resultPayment2->fetch()) {
                            $amountPaid += $rowPayment2['amount'];
                        }
                    }

                    if ($row['status'] == 'Refunded') {
                        $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                        $return .= __('Refund Issued');
                        $return .= '</h3>';
                        $return .= '<table cellspacing="0" style="width: 100%; $style4">';
                        $return .= "<tr style='height: 35px' class='current error'>";
                        $return .= "<td style='text-align: right; $style2'>";
                        $return .= '<b>'.__('Refund Total:').'</b>';
                        $return .= '</td>';
                        $return .= "<td style='width: 135px; $style2'>";
                        if (substr($currency, 4) != '') {
                            $return .= substr($currency, 4).' ';
                        }
                        $return .= '<b>'.number_format($amountPaid, 2, '.', ',').'</b>';
                        $return .= '</td>';
                        $return .= '</tr>';
                        $return .= '</table>';
                    } else if ($balanceFail == false) {
                        $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                        $return .= __('Outstanding Balance');
                        $return .= '</h3>';
                        if ($hideItemisation != 'Y') { //Do not hide itemisation
                            $return .= "<table cellspacing='0' style='width: 100%; $style4'>";
                            $return .= "<tr style='height: 35px' class='current'>";
                            $return .= "<td style='text-align: right; $style2'>";
                            $return .= '<b>'.__('Outstanding Balance:').'</b>';
                            $return .= '</td>';
                            $return .= "<td style='width: 135px; $style2'>";
                            if (substr($currency, 4) != '') {
                                $return .= substr($currency, 4).' ';
                            }
                            $return .= '<b>'.number_format(($feeTotal - $amountPaid), 2, '.', ',').'</b>';
                            $return .= '</td>';
                            $return .= '</tr>';
                            $return .= '</table>';
                        } else { //Hide itemisation
                            $return .= "<p style='margin-top: 10px; text-align: right'>";
                            $return .= __('Payment Total').': ';
                            if (substr($currency, 4) != '') {
                                $return .= substr($currency, 4).' ';
                            }
                            $return .= '<b>'.number_format(($feeTotal - $amountPaid), 2, '.', ',').'</b>';
                            $return .= '</p>';
                        }
                    }
                }
            } else if (@$rowPayment['status'] == 'Complete') {
                if ($row['status'] == 'Refunded') {
                    $return .= "<h3 style='padding-top: 40px; padding-left: 10px; margin: 0px; $style4'>";
                    $return .= __('Refund Issued');
                    $return .= '</h3>';
                    $return .= '<table cellspacing="0" style="width: 100%; $style4">';
                    $return .= "<tr style='height: 35px' class='current error'>";
                    $return .= "<td style='text-align: right; $style2'>";
                    $return .= '<b>'.__('Refund Total:').'</b>';
                    $return .= '</td>';
                    $return .= "<td style='width: 135px; $style2'>";
                    if (substr($currency, 4) != '') {
                        $return .= substr($currency, 4).' ';
                    }
                    $return .= '<b>'.number_format($rowPayment['amount'], 2, '.', ',').'</b>';
                    $return .= '</td>';
                    $return .= '</tr>';
                    $return .= '</table>';
                }
            }
        }

        //Receipts Notes
        $receiptNotes = $settingGateway->getSettingByScope('Finance', 'receiptNotes');
        if ($receiptNotes != '') {
            $return .= "<h3 style='margin-top: 40px'>";
            $return .= __('Notes');
            $return .= '</h3>';
            $return .= '<p>';
            $return .= $receiptNotes;
            $return .= '</p>';
        }

        return $return;
    }
}

function getBudgetAllocation($pdo, $gibbonFinanceBudgetCycleID, $gibbonFinanceBudgetID)
{
    $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetID);
    $sql = "SELECT value FROM gibbonFinanceBudgetCycleAllocation WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID";
    $result = $pdo->executeQuery($data, $sql);

    return ($result->rowCount() == 1)? $result->fetchColumn(0) : __('N/A');
}

function getBudgetAllocated($pdo, $gibbonFinanceBudgetCycleID, $gibbonFinanceBudgetID)
{
    $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetID);
    $sql = "(SELECT cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Approved', 'Order'))
        UNION
        (SELECT paymentAmount AS cost FROM gibbonFinanceExpense WHERE countAgainstBudget='Y' AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND FIELD(status, 'Paid'))";
    $result = $pdo->executeQuery($data, $sql);

    $budgetAllocated = 0;
    if ($result->rowCount() > 0) {
        $budgetAllocated = array_reduce($result->fetchAll(), function($sum, $item) {
            $sum += $item['cost'];
            return $sum;
        }, 0);
    }
    return $budgetAllocated;
}

function getInvoiceTotalFee($pdo, $gibbonFinanceInvoiceID, $status)
{
    try {
        $dataTotal = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
        if ($status == 'Pending') {
            $sqlTotal = 'SELECT gibbonFinanceInvoiceFee.fee AS fee, gibbonFinanceFee.fee AS fee2 FROM gibbonFinanceInvoiceFee LEFT JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
        } else {
            $sqlTotal = 'SELECT gibbonFinanceInvoiceFee.fee AS fee, NULL AS fee2 FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
        }
        $resultTotal = $pdo->executeQuery($dataTotal, $sqlTotal);
    } catch (PDOException $e) {
        return null;
    }

    $totalFee = 0;

    while ($rowTotal = $resultTotal->fetch()) {
        $totalFee += is_numeric($rowTotal['fee2'])? $rowTotal['fee2'] : $rowTotal['fee'];
    }

    return $totalFee;
}
