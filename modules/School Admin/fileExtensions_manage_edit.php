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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/fileExtensions_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage File Extensions'), 'fileExtensions_manage.php')
        ->add(__('Edit File Extensions'));

    //Check if gibbonFileExtensionID specified
    $gibbonFileExtensionID = $_GET['gibbonFileExtensionID'] ?? '';
    if ($gibbonFileExtensionID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonFileExtensionID' => $gibbonFileExtensionID);
            $sql = 'SELECT * FROM gibbonFileExtension WHERE gibbonFileExtensionID=:gibbonFileExtensionID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('fileExtensions', $session->get('absoluteURL').'/modules/'.$session->get('module').'/fileExtensions_manage_editProcess.php?gibbonFileExtensionID='.$gibbonFileExtensionID);

            $form->addHiddenValue('address', $session->get('address'));

            $illegalTypes = FileUploader::getIllegalFileExtensions();

            $categories = array(
                'Document'        => __('Document'),
                'Spreadsheet'     => __('Spreadsheet'),
                'Presentation'    => __('Presentation'),
                'Graphics/Design' => __('Graphics/Design'),
                'Video'           => __('Video'),
                'Audio'           => __('Audio'),
                'Other'           => __('Other'),
            );

            $row = $form->addRow();
                $row->addLabel('extension', __('Extension'))->description(__('Must be unique.'));
                $ext = $row->addTextField('extension')->required()->maxLength(7)->setValue($values['extension']);

                $within = implode(',', array_map(function ($str) { return sprintf("'%s'", $str); }, $illegalTypes));
                $ext->addValidation('Validate.Exclusion', 'within: ['.$within.'], failureMessage: "'.__('Illegal file type!').'", partialMatch: true, caseSensitive: false');

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(50)->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addSelect('type')->fromArray($categories)->required()->placeholder()->selected($values['type']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
