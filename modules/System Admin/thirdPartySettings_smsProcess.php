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

use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/thirdPartySettings.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    // Access denied
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $name = $session->get('preferredName').' '.$session->get('surname');
    $phoneNumber = $_GET['phoneNumber'] ?? '';
    
    $body = __('{name} sent you a test SMS via {system}', ['name' => $name, 'system' => $session->get('systemName')]);

    $smsSender = $container->get(SMS::class);

    $result = $smsSender
        ->content($body)
        ->send([$phoneNumber]);

    if ($errors = $smsSender->getErrors()) {
        $session->set('testSMSErrors', $errors);
        $session->set('testSMSRecipient', $phoneNumber);
    }

    $URL .= empty($result) 
        ? '&return=error11'
        : '&return=success0';
    header("Location: " . $URL);
}
