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

use Gibbon\Domain\User\PersonalDocumentTypeGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonalDocumentTypeID = $_POST['gibbonPersonalDocumentTypeID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/personalDocumentSettings_manage_edit.php&gibbonPersonalDocumentTypeID='.$gibbonPersonalDocumentTypeID;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/personalDocumentSettings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $personalDocumentTypeGateway = $container->get(PersonalDocumentTypeGateway::class);

    $data = [
        'name'                  => $_POST['name'] ?? '',
        'description'           => $_POST['description'] ?? '',
        'active'                => $_POST['active'] ?? 'Y',
        'required'              => $_POST['required'] ?? 'N',
        'fields'                => $_POST['fields'] ?? [],
        'activePersonStudent'   => $_POST['activePersonStudent'] ?? 0,
        'activePersonStaff'     => $_POST['activePersonStaff'] ?? 0,
        'activePersonParent'    => $_POST['activePersonParent'] ?? 0,
        'activePersonOther'     => $_POST['activePersonOther'] ?? 0,
        'activeApplicationForm' => $_POST['activeApplicationForm'] ?? 0,
        'activeDataUpdater'     => $_POST['activeDataUpdater'] ?? 0,
    ];

    $data['fields'] = is_array($data['fields']) ? json_encode($data['fields']) : null;

    // Handle role category checkboxes
    $roleCategories = $_POST['roleCategories'] ?? [];
    $data['activePersonStudent'] = in_array('activePersonStudent', $roleCategories);
    $data['activePersonStaff'] = in_array('activePersonStaff', $roleCategories);
    $data['activePersonParent'] = in_array('activePersonParent', $roleCategories);
    $data['activePersonOther'] = in_array('activePersonOther', $roleCategories);

    if (empty($data['name']) || empty($data['active'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if (!$personalDocumentTypeGateway->exists($gibbonPersonalDocumentTypeID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if (!$personalDocumentTypeGateway->unique($data, ['name'], $gibbonPersonalDocumentTypeID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    $updated = $personalDocumentTypeGateway->update($gibbonPersonalDocumentTypeID, $data);

    $URL .= !$updated
        ? "&return=error1"
        : "&return=success0";
    header("Location: {$URL}");
}
