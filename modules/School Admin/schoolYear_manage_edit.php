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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYear_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage School Years'), 'schoolYear_manage.php')
        ->add(__('Edit School Year'));

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    if ($gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('schoolYear', $session->get('absoluteURL').'/modules/'.$session->get('module').'/schoolYear_manage_editProcess.php?gibbonSchoolYearID='.$gibbonSchoolYearID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            $statuses = array(
                'Past'     => __('Past'),
                'Current'  => __('Current'),
                'Upcoming' => __('Upcoming'),
            );

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->required()->maxLength(9)->setValue($values['name']);

            if ($values['status'] == 'Current') {
                $form->addHiddenValue('status', $values['status']);
                $row = $form->addRow();
                    $row->addLabel('statusText', __('Status'));
                    $row->addTextField('statusText')->readOnly()->setValue(__($values['status']));
            } else {
                $row = $form->addRow();
                    $row->addLabel('status', __('Status'));
                    $row->addSelect('status')->fromArray($statuses)->required()->selected($values['status']);

                    $form->toggleVisibilityByClass('statusChange')->onSelect('status')->when('Current');
                    $direction = ($values['sequenceNumber'] < $session->get('gibbonSchoolYearSequenceNumberCurrent'))? __('Upcoming') : __('Past');

                    // Display an alert to warn users that changing this will have an impact on their system.
                    $row = $form->addRow()->setClass('statusChange');
                    $row->addAlert(sprintf(__('Setting the status of this school year to Current will change the current school year %1$s to %2$s. Adjustments to the Academic Year can affect the visibility of vital data in your system. It\'s recommended to use the Rollover tool in User Admin to advance school years rather than changing them here. PROCEED WITH CAUTION!'), $session->get('gibbonSchoolYearNameCurrent'), $direction) );
            }

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
                $row->addSequenceNumber('sequenceNumber', 'gibbonSchoolYear', $values['sequenceNumber'])->required()->maxLength(3)->setValue($values['sequenceNumber']);

            $row = $form->addRow();
                $row->addLabel('firstDay', __('First Day'));
                $row->addDate('firstDay')->required()->setValue(Format::date($values['firstDay']));

            $row = $form->addRow();
                $row->addLabel('lastDay', __('Last Day'));
                $row->addDate('lastDay')->required()->setValue(Format::date($values['lastDay']));

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
