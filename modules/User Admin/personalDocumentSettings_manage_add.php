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
use Gibbon\Forms\PersonalDocumentHandler;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/personalDocumentSettings_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Personal Document'), 'personalDocumentSettings.php')
        ->add(__('Add Personal Document Type'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/personalDocumentSettings_manage_edit.php&gibbonPersonalDocumentTypeID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $personalDocumentHandler = $container->get(PersonalDocumentHandler::class);

    $form = Form::create('personalDocumentType', $session->get('absoluteURL').'/modules/User Admin/personalDocumentSettings_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Basic Details', __('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __('Document Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('required', __('Required'));
        $row->addYesNo('required')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'));
        $row->addSequenceNumber('sequenceNumber', 'gibbonPersonalDocumentType')->maxLength(3);

    $form->addRow()->addHeading('Configure', __('Configure'));

    $row = $form->addRow();
        $row->addLabel('document', __('Type'));
        $row->addSelect('document')->fromArray($personalDocumentHandler->getDocuments())->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('fields', __('Fields'));
        $row->addCheckbox('fields')->fromArray($personalDocumentHandler->getFields());

    $form->addRow()->addHeading('Visibility', __('Visibility'));

    $activePersonOptions = array(
        'activePersonStudent' => __('Student'),
        'activePersonStaff'   => __('Staff'),
        'activePersonParent'  => __('Parent'),
        'activePersonOther'   => __('Other'),
    );

    $row = $form->addRow();
        $row->addLabel('roleCategories', __('Role Categories'));
        $row->addCheckbox('roleCategories')->fromArray($activePersonOptions)->checked(['activePersonStudent']);

    $row = $form->addRow();
        $row->addLabel('activeDataUpdater', __('Include In Data Updater?'));
        $row->addSelect('activeDataUpdater')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();

    $row = $form->addRow();
        $row->addLabel('activeApplicationForm', __('Include In Application Form?'));
        $row->addSelect('activeApplicationForm')->fromArray(array('1' => __('Yes'), '0' => __('No')))->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
