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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_edit.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Attendance Settings'), 'attendanceSettings.php')
        ->add(__('Edit Attendance Code'));

    $gibbonAttendanceCodeID = (isset($_GET['gibbonAttendanceCodeID']))? $_GET['gibbonAttendanceCodeID'] : NULL;

    if (empty($gibbonAttendanceCodeID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
	    
	        $data = array('gibbonAttendanceCodeID' => $gibbonAttendanceCodeID);
	        $sql = 'SELECT * FROM gibbonAttendanceCode WHERE gibbonAttendanceCodeID=:gibbonAttendanceCodeID';
	        $result = $connection2->prepare($sql);
	        $result->execute($data);

	    if ($result->rowCount() != 1) {
	        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
	    } else {
	        //Let's go!
            $values = $result->fetch();

            $form = Form::create('attendanceCode', $session->get('absoluteURL').'/modules/'.$session->get('module').'/attendanceSettings_manage_editProcess.php?gibbonAttendanceCodeID='.$gibbonAttendanceCodeID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('name', $values['name']);

            $row = $form->addRow();
                $row->addLabel('nameText', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('nameText')->readonly()->required()->setValue(__($values['name']));

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->readonly()->required()->maxLength(4);

            $directions = array(
                'In'     => __('In Class'),
                'Out' => __('Out of Class'),
            );
            $row = $form->addRow();
                $row->addLabel('direction', __('Direction'));
                $row->addSelect('direction')->required()->fromArray($directions);

            $scopes = array(
                'Onsite'         => __('Onsite'),
                'Onsite - Late'  => __('Onsite - Late'),
                'Offsite'        => __('Offsite'),
                'Offsite - Left' => __('Offsite - Left'),
                'Offsite - Late' => __('Offsite - Late'),
            );
            $row = $form->addRow();
                $row->addLabel('scope', __('Scope'));
                $row->addSelect('scope')->required()->fromArray($scopes);

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'));
                $row->addSequenceNumber('sequenceNumber', 'gibbonAttendanceCode', $values['sequenceNumber'])->required()->maxLength(3);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('reportable', __('Reportable'));
                $row->addYesNo('reportable')->required();

            $row = $form->addRow();
                $row->addLabel('prefill', __('Prefillable'))->description(__('Can this code prefill when taking attendance?'));
                $row->addYesNo('prefill')->required();

            $row = $form->addRow();
                $row->addLabel('future', __('Allow Future Use'))->description(__('Can this code be used in Set Future Absence?'));
                $row->addYesNo('future')->required();

            $row = $form->addRow();
                $row->addLabel('gibbonRoleIDAll', __('Available to Roles'))->description(__('Controls who can use this code.'));
                $row->addSelectRole('gibbonRoleIDAll')->selectMultiple()->loadFromCSV($values);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
		}
	}
}
