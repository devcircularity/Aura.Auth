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
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/studentsSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/studentsSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $partialFail = false;

    $settingGateway = $container->get(SettingGateway::class);

    $settingsToUpdate = [
        'Students' => [
            'enableStudentNotes',
            'noteCreationNotification',
            'emergencyFollowUpGroup',
            'academicAlertLowThreshold',
            'academicAlertMediumThreshold',
            'academicAlertHighThreshold',
            'behaviourAlertLowThreshold',
            'behaviourAlertMediumThreshold',
            'behaviourAlertHighThreshold',
            'firstAidDescriptionTemplate',
        ],
        'School Admin' => [
            'studentAgreementOptions',
        ],
        'User Admin' => [
            'dayTypeOptions',
            'dayTypeText',
        ]
    ];

    foreach ($settingsToUpdate as $scope => $settings) {
        foreach ($settings as $name) {
            $value = $_POST[$name] ?? '';

            if ($name == 'studentAgreementOptions') {
                $value = implode(',', array_filter(array_map('trim', explode(',', $value))));
            }

            $updated = $settingGateway->updateSettingByScope($scope, $name, $value);
            $partialFail &= !$updated;
        }
    }

    $URL .= $partialFail
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
