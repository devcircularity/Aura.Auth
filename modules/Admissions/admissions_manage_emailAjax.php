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

//Gibbon system-wide include
include '../../gibbon.php';

if (empty($session->get('gibbonPersonID')) || empty($session->get('gibbonRoleIDPrimary'))) {
    die(__('Your request failed because you do not have access to this action.'));
} else {
    $gibbonAdmissionsAccountID = isset($_POST['gibbonAdmissionsAccountID'])? $_POST['gibbonAdmissionsAccountID'] : '';
    $email = isset($_POST['email'])? $_POST['email'] : (isset($_POST['value'])? $_POST['value'] : '');
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

    $data = array('gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID, 'email' => $email);
    $sql = "SELECT COUNT(*) FROM gibbonAdmissionsAccount WHERE email=:email AND gibbonAdmissionsAccountID<>:gibbonAdmissionsAccountID";
    $result = $pdo->executeQuery($data, $sql);

    echo ($result && $result->rowCount() == 1)? $result->fetchColumn(0) : -1;
}
