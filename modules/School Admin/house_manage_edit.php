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
use Gibbon\FileUploader;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Houses'), 'house_manage.php')
        ->add(__('Edit House'));

    //Check if gibbonHouseID specified
    $gibbonHouseID = $_GET['gibbonHouseID'] ?? '';
    if ($gibbonHouseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonHouseID' => $gibbonHouseID);
            $sql = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('houses', $session->get('absoluteURL').'/modules/'.$session->get('module').'/house_manage_editProcess.php?gibbonHouseID='.$gibbonHouseID);

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('attachment1', $values['logo']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->required()->maxLength(30)->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->required()->maxLength(10)->setValue($values['nameShort']);

            $fileUploader = new FileUploader($pdo, $session);

            $row = $form->addRow();
                $row->addLabel('file1', __('Logo'));
                $file = $row->addFileUpload('file1')
                    ->accepts($fileUploader->getFileExtensions('Graphics/Design'))
                    ->setAttachment('logo', $session->get('absoluteURL'), $values['logo']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
