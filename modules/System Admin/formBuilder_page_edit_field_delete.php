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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Forms\FormFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $urlParams = [
        'gibbonFormID'      => $_GET['gibbonFormID'] ?? '',
        'gibbonFormPageID'  => $_GET['gibbonFormPageID'] ?? '',
        'gibbonFormFieldID' => $_GET['gibbonFormFieldID'] ?? '',
        'fieldGroup'        => $_GET['fieldGroup'] ?? '',
    ];
    
    if (empty($urlParams['gibbonFormID']) || empty($urlParams['gibbonFormPageID']) || empty($urlParams['gibbonFormFieldID'])) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(FormFieldGateway::class)->getByID($urlParams['gibbonFormFieldID']);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/System Admin/formBuilder_page_edit_field_deleteProcess.php?'.http_build_query($urlParams));
    echo $form->getOutput();
}
