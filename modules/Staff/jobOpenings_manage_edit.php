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

if (isActionAccessible($guid, $connection2, '/modules/Staff/jobOpenings_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Job Openings'), 'jobOpenings_manage.php')
        ->add(__('Edit Job Opening'));

    //Check if gibbonStaffJobOpeningID specified
    $gibbonStaffJobOpeningID = $_GET['gibbonStaffJobOpeningID'] ?? '';
    if ($gibbonStaffJobOpeningID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID);
            $sql = 'SELECT * FROM gibbonStaffJobOpening WHERE gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/jobOpenings_manage_editProcess.php?gibbonStaffJobOpeningID=$gibbonStaffJobOpeningID");

            $form->addHiddenValue('address', $session->get('address'));

            $types = array('Teaching' => __('Teaching'), 'Support' => __('Support'));
            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addSelect('type')->fromArray($types)->placeholder()->required();

            $row = $form->addRow();
                $row->addLabel('jobTitle', __('Job Title'));
                $row->addTextField('jobTitle')->maxlength(100)->required();

            $row = $form->addRow();
                $row->addLabel('dateOpen', __('Opening Date'));
                $row->addDate('dateOpen')->required();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('description', __('Description'));
                $column->addEditor('description', $guid)->setRows(20)->showMedia()->required();

            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
