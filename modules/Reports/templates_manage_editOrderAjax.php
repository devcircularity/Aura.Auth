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

use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;

$_POST['address'] = '/modules/Reports/templates_manage_edit.php';

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_edit.php') == false) {
    exit;
} else {
    // Proceed!
    $gibbonReportTemplateID = $_POST['gibbonReportTemplateID'] ?? '';
    $order = $_POST['order'];

    if (empty($order) || empty($gibbonReportTemplateID)) {
        exit;
    } else {
        $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);

        $count = 1;
        foreach ($order as $gibbonReportTemplateSectionID) {
            if (empty($gibbonReportTemplateSectionID)) continue;

            $updated = $templateSectionGateway->update($gibbonReportTemplateSectionID, ['sequenceNumber' => $count]);
            $count++;
        }
    }
}
