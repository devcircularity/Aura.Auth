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

use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$gibbonAdmissionsAccountID = $_GET['gibbonAdmissionsAccountID'] ?? '';
$search = $_REQUEST['search'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Admissions/admissions_manage.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Admissions/admissions_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonAdmissionsAccountID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $values = $admissionsAccountGateway->getByID($gibbonAdmissionsAccountID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $deleted = $admissionsAccountGateway->delete($gibbonAdmissionsAccountID);

    $URL .= !$deleted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
