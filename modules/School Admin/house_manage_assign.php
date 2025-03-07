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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage_assign.php') == false) {
	//Acess denied
	$page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Houses'), 'house_manage.php')
        ->add(__('Assign Houses'));

    $form = Form::create('houseAssignProcess', $session->get('absoluteURL').'/modules/School Admin/house_manage_assignProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple()->required();

    $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonHouseIDList', __('Houses'));
        $row->addSelect('gibbonHouseIDList')->fromQuery($pdo, $sql)->required()->selectMultiple()->selectAll();

    $row = $form->addRow();
        $row->addLabel('balanceGender', __('Balance by Gender?'));
        $row->addYesNo('balanceGender');

    $row = $form->addRow();
        $row->addLabel('balanceYearGroup', __('Balance by Year Group?'))->description(__('Attempts to keep houses be as balanced as possible per year group, otherwise balances the house numbers school-wide.'));
        $row->addYesNo('balanceYearGroup');

    $row = $form->addRow();
        $row->addLabel('overwrite', __('Overwrite'))->description(__("Replace a student's existing house assignment? If no, it will only assign houses to students who do not already have one."));
        $row->addYesNo('overwrite')->selected('N');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
