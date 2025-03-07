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

use Gibbon\Module\Reports\Domain\ReportingScopeGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportingScopeID = $_POST['gibbonReportingScopeID'] ?? '';
$gibbonReportingCycleID = $_POST['gibbonReportingCycleID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_scopes_manage_edit.php&gibbonReportingCycleID='.$gibbonReportingCycleID.'&gibbonReportingScopeID='.$gibbonReportingScopeID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_scopes_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingScopeGateway = $container->get(ReportingScopeGateway::class);

    $data = [
        'gibbonReportingCycleID' => $gibbonReportingCycleID,
        'name'                   => $_POST['name'] ?? '',
    ];

    // Validate the required values are present
    if (empty($gibbonReportingScopeID) || empty($gibbonReportingCycleID) || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$reportingScopeGateway->exists($gibbonReportingScopeID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportingScopeGateway->unique($data, ['name', 'gibbonReportingCycleID'], $gibbonReportingScopeID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $reportingScopeGateway->update($gibbonReportingScopeID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
