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

//Gibbon system-wide includes
include '../../gibbon.php';

$output = '';

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php')) {
    if ($session->exists('username')) {
        if (isset($_GET['gibbonMessengerCannedResponseID'])) {
            $gibbonMessengerCannedResponseID = $_GET['gibbonMessengerCannedResponseID'] ?? '';
                $data = array('gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID);
                $sql = 'SELECT body FROM gibbonMessengerCannedResponse WHERE gibbonMessengerCannedResponseID=:gibbonMessengerCannedResponseID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            if ($result->rowCount() == 1) {
                $row = $result->fetch();
                $output .= $row['body'];
            }
        }
    }
}

echo $output;
