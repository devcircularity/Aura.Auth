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

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/librarySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/librarySettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $defaultLoanLength = $_POST['defaultLoanLength'] ?? '';
    $browseBGColor = $_POST['browseBGColor'] ?? '';
    $browseBGImage = $_POST['browseBGImage'] ?? '';

    // Filter valid colour values
    $browseBGColor = preg_replace('/[^a-fA-F0-9\#]/', '', mb_substr($browseBGColor, 0, 7));

    //Validate Inputs
    if ($defaultLoanLength == '') {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        //Write to database
        $fail = false;

        try {
            $data = array('value' => $defaultLoanLength);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Library' AND name='defaultLoanLength'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $browseBGColor);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Library' AND name='browseBGColor'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $fail = true;
        }

        try {
            $data = array('value' => $browseBGImage);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Library' AND name='browseBGImage'";
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
