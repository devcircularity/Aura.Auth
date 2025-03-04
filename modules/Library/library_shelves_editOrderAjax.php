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

use Gibbon\Domain\Library\LibraryShelfGateway;

$_POST['address'] = '/modules/Library/library_manage_shelves.php';

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_shelves.php') == false) {
    exit;
} else {
    // Proceed!
    $order = $_POST['order'] ?? [];

    if (empty($order)) {
        exit;
    } else {
        $shelfGateway = $container->get(LibraryShelfGateway::class);

        $count = 1;
        foreach ($order as $gibbonLibraryShelfID) {
            $updated = $shelfGateway->update($gibbonLibraryShelfID, ['sequenceNumber' => $count]);
            $count++;
        }
    }
}
