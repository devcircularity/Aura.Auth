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

use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffCoverageID = $_POST['gibbonStaffCoverageID'] ?? '';
$gibbonStaffCoverageDateID = $_POST['gibbonStaffCoverageDateID'] ?? '';
$gibbonPersonIDCoverage = $_POST['gibbonPersonIDCoverage'] ?? '';
$coverageStatus = $_POST['coverageStatus'] ?? '';
$date = $_POST['date'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_planner.php&sidebar=true&date='.$date;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonStaffCoverageID) && $coverageStatus != 'Not Required') {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $coverage = $staffCoverageGateway->getByID($gibbonStaffCoverageID);

    if ($coverageStatus != 'Not Required' && (empty($coverage) || empty($gibbonPersonIDCoverage))) {
        $data = [
            'gibbonPersonIDCoverage' => null,
            'gibbonPersonIDStatus'   => $session->get('gibbonPersonID'),
            'requestType'            => 'Individual',
            'status'                 => 'Requested',
        ];
    } elseif ($coverageStatus == 'Not Required') {
        $data = [
            'gibbonPersonIDCoverage' => null,
            'gibbonPersonIDStatus'   => $session->get('gibbonPersonID'),
            'requestType'            => 'Assigned',
            'status'                 => 'Not Required',
        ];
    } else {
        $data = [
            'gibbonPersonIDCoverage' => $gibbonPersonIDCoverage,
            'gibbonPersonIDStatus'   => $session->get('gibbonPersonID'),
            'requestType'            => 'Assigned',
            'status'                 => 'Accepted',
            'notificationSent'       => 'N',
        ];
    }

    // Update the coverage
    $updated = $staffCoverageGateway->update($gibbonStaffCoverageID, $data);

    $URL .= !$updated
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
