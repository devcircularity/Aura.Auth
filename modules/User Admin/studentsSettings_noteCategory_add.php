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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/studentsSettings_noteCategory_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Students Settings'), 'studentsSettings.php')
        ->add(__('Add Note Category'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/studentsSettings_noteCategory_edit.php&gibbonStudentNoteCategoryID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('noteCategory', $session->get('absoluteURL').'/modules/'.$session->get('module').'/studentsSettings_noteCategory_addProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('template', __('Template'))->description(__('HTML code to be inserted into blank note.'));
        $row->addTextArea('template')->setRows(8);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
