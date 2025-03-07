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

$gibbonModuleID = $_POST['gibbonModuleID'] ?? '';
$gibbonRoleID = $_POST['gibbonRoleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/permission_manage.php&gibbonModuleID='.$gibbonModuleID.'&gibbonRoleID='.$gibbonRoleID;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/permission_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $permissions = $_POST['permission'] ?? [];
    $totalCount = $_POST['totalCount'] ?? [];
    $maxInputVars = ini_get('max_input_vars');

    if (empty($totalCount)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else if (is_null($maxInputVars) != false && $maxInputVars <= count($_POST, COUNT_RECURSIVE)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } else {
        $data = array();

        if (empty($gibbonModuleID) && empty($gibbonRoleID)) {
            $sql = "TRUNCATE TABLE gibbonPermission";
        } else {
            $where = array();

            if (!empty($gibbonModuleID)) {
                $data['gibbonModuleID'] = $gibbonModuleID;
                $where[] = "gibbonAction.gibbonModuleID=:gibbonModuleID";
            }

            if (!empty($gibbonRoleID)) {
                $data['gibbonRoleID'] = $gibbonRoleID;
                $where[] = "gibbonPermission.gibbonRoleID=:gibbonRoleID";
            }

            $sql = "DELETE gibbonPermission
                    FROM gibbonPermission
                    JOIN gibbonAction ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                    WHERE ".implode(' AND ', $where);
        }

        try {
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $insertFail = false;
        foreach ($permissions as $gibbonActionID => $roles) {
            if (empty($roles)) continue;

            foreach ($roles as $gibbonRoleID => $checked) {
                if ($checked != 'on') continue;

                try {
                    $data = array('gibbonActionID' => $gibbonActionID, 'gibbonRoleID' => $gibbonRoleID);
                    $sql = 'INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=:gibbonRoleID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $insertFail = true;
                }
            }
        }

        if ($insertFail == true) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $session->set('pageLoads', null);

            //Success0
            $URL .= '&return=success0';
            header("Location: {$URL}");
            exit;
        }
    }
}
