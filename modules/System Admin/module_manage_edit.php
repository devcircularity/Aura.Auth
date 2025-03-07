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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\ModuleGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Modules'), 'module_manage.php')
        ->add(__('Edit Module'));

    // Check if module specified
    $gibbonModuleID = $_GET['gibbonModuleID'] ?? '';
    
    if (empty($gibbonModuleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $moduleGateway = $container->get(ModuleGateway::class);
        $module = $moduleGateway->getByID($gibbonModuleID);
        
        if (empty($module)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            // Let's go!
            $form = Form::create('moduleEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/module_manage_editProcess.php?gibbonModuleID='.$module['gibbonModuleID']);

            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->setValue(__($module['name']))->readonly();

            $row = $form->addRow();
                $row->addLabel('description', __('Description'));
                $row->addTextArea('description')->setValue(__($module['description']))->readonly()->setRows(3);

            $row = $form->addRow();
               $row->addLabel('category', __('Category'))->description(__('Determines menu structure'));
               $row->addTextField('category')->setValue($module['category'])->required()->maxLength(12);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->selected($module['active']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
