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

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/activitySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $dateType = $_POST['dateType'] ?? '';
    if ($dateType == 'Term') {
        $maxPerTerm = $_POST['maxPerTerm'] ?? '';
    } else {
        $maxPerTerm = 0;
    }
    $access = $_POST['access'] ?? '';
    $payment = $_POST['payment'] ?? '';
    $disableExternalProviderSignup = $_POST['disableExternalProviderSignup'] ?? '';
    $hideExternalProviderCost = $_POST['hideExternalProviderCost'] ?? '';

    //Validate Inputs
    if ($dateType == '' or $access == '' or $payment == '' or $disableExternalProviderSignup == '' or $hideExternalProviderCost == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('value' => $dateType);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='dateType'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $maxPerTerm);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='maxPerTerm'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $access);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='access'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $payment);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='payment'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $disableExternalProviderSignup);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='disableExternalProviderSignup'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $hideExternalProviderCost);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Activities' AND name='hideExternalProviderCost'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        if ($fail == true) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            getSystemSettings($guid, $connection2);
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
