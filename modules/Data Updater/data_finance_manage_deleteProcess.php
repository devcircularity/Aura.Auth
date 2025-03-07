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

include '../../gibbon.php';

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonFinanceInvoiceeUpdateID = $_POST['gibbonFinanceInvoiceeUpdateID'] ?? '';
$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/data_finance_manage_delete.php&gibbonFinanceInvoiceeUpdateID=$gibbonFinanceInvoiceeUpdateID&gibbonSchoolYearID=$gibbonSchoolYearID";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address).'/data_finance_manage.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if ($gibbonFinanceInvoiceeUpdateID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = 'SELECT * FROM gibbonFinanceInvoiceeUpdate WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID';
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
            //Write to database
            try {
                $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
                $sql = 'DELETE FROM gibbonFinanceInvoiceeUpdate WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID';
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
