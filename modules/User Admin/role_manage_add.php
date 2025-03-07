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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/role_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Roles'),'role_manage.php')
        ->add(__('Add Role'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/role_manage_edit.php&gibbonRoleID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('addRole', $session->get('absoluteURL').'/modules/'.$session->get('module').'/role_manage_addProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $categories = array(
        'Staff'   => __('Staff'),
        'Student' => __('Student'),
        'Parent'  => __('Parent'),
        'Other'   => __('Other'),
    );

    $restrictions = array(
        'None'       => __('None'),
        'Same Role'  => __('Users with the same role'),
        'Admin Only' => __('Administrators only'),
    );

    $row = $form->addRow();
        $row->addLabel('category', __('Category'));
        $row->addSelect('category')->fromArray($categories)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'));
        $row->addTextField('nameShort')->required()->maxLength(4);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addTextField('type')->required()->readonly()->setValue('Additional');

    $row = $form->addRow();
        $row->addLabel('canLoginRole', __('Can Login?'))->description(__('Are users with this primary role able to login?'));
        $row->addYesNo('canLoginRole')->required()->selected('Y');

    $form->toggleVisibilityByClass('loginOptions')->onSelect('canLoginRole')->when('Y');
    $row = $form->addRow()->addClass('loginOptions');
        $row->addLabel('pastYearsLogin', __('Login To Past Years'));
        $row->addYesNo('pastYearsLogin')->required();

    $row = $form->addRow()->addClass('loginOptions');
        $row->addLabel('futureYearsLogin', __('Login To Future Years'));
        $row->addYesNo('futureYearsLogin')->required();

    $row = $form->addRow();
        $row->addLabel('restriction', __('Restriction'))->description(__('Determines who can grant or remove this role in Manage Users.'));
        $row->addSelect('restriction')->fromArray($restrictions)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
