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

$page->breadcrumbs
    ->add(__('Manage Mailing List Recipients'), 'mailingListRecipients_manage.php')
    ->add(__('Add Recipient'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/mailingListRecipients_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Messenger/mailingListRecipients_manage_edit.php&gibbonMessengerMailingListRecipientID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);
	
	$form = Form::create('mailingList', $session->get('absoluteURL').'/modules/'.$session->get('module').'/mailingListRecipients_manage_addProcess.php');
                
	$form->addHiddenValue('address', $session->get('address'));

	$row = $form->addRow();
		$row->addLabel('surname', __('Surname'));
		$row->addTextField('surname')->required()->maxLength(60);

	$row = $form->addRow();
		$row->addLabel('preferredName', __('Preferred Name'));
		$row->addTextField('preferredName')->required()->maxLength(60);

	$row = $form->addRow();
		$row->addLabel('email', __('Email'))->description(__('Must be unique.'));
		$row->addEmail('email')->required()->maxLength(75);
	
	$row = $form->addRow();
		$row->addLabel('organisation', __('Organisation'));
		$row->addTextField('organisation')->maxLength(60);
	
	$sql = "SELECT gibbonMessengerMailingListID as value, name FROM gibbonMessengerMailingList WHERE active='Y' ORDER BY name";
	$lists = $pdo->select($sql)->fetchKeyPair();
	if (count($lists) > 0) {
		$row = $form->addRow();
			$row->addLabel('gibbonMessengerMailingListIDList', __('Mailing Lists'));
			$row->addCheckbox('gibbonMessengerMailingListIDList')->fromArray($lists);
	}

	$row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();
}
