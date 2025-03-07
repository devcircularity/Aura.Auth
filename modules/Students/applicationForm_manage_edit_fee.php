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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\ApplicationFormGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    // Access denied
    echo Format::alert(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonApplicationFormID = $_GET['gibbonApplicationFormID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $search = $_GET['search'] ?? '';

    $urlParams = compact('gibbonApplicationFormID', 'gibbonSchoolYearID', 'search');

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applicationForm_manage.php', $urlParams)
        ->add(__('Edit Form'), 'applicationForm_manage_edit.php', $urlParams)
        ->add(__('Send Payment Request'));

    if ($gibbonApplicationFormID == '' or $gibbonSchoolYearID == '') {
        echo Format::alert(__('You have not specified one or more required parameters.'));
        return;
    }

    $application = $container->get(ApplicationFormGateway::class)->getByID($gibbonApplicationFormID);
    if (empty($application)) {
        echo Format::alert(__('The specified record does not exist.'));
        return;
    }

    if (!empty($application['gibbonPaymentID2']) || $application['paymentMade2'] != 'N') {
        echo Format::alert(__('A payment has already been made for this application form.'), 'success');
        return;
    }

    $settingGateway = $container->get(SettingGateway::class);
    $enablePayments = $settingGateway->getSettingByScope('System', 'enablePayments');
    $paymentAPIUsername = $settingGateway->getSettingByScope('System', 'paymentAPIUsername');
    $paymentAPIPassword = $settingGateway->getSettingByScope('System', 'paymentAPIPassword');
    $paymentAPISignature = $settingGateway->getSettingByScope('System', 'paymentAPISignature');

    if ($enablePayments != 'Y') {
        echo Format::alert(__('Online payment options are not available at this time.'));
        return;
    }

    $applicationProcessFee = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFee');
    $applicationProcessFeeText = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFeeText');

    $form = Form::create('applicationFormFee', $session->get('absoluteURL').'/modules/Students/applicationForm_manage_edit_feeProcess.php?search='.$search);

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonApplicationFormID', $application['gibbonApplicationFormID']);

    $row = $form->addRow();
        $row->addLabel('email', __('Parent 1 Email'));
        $row->addTextField('email')
            ->setValue($application['parent1email'])
            ->readOnly();

    $row = $form->addRow();
        $row->addLabel('applicationProcessFee', __('Application Processing Fee'));
        $row->addCurrency('applicationProcessFee')
            ->setValue($applicationProcessFee)
            ->readOnly();

    $col = $form->addRow()->addColumn();
        $col->addLabel('applicationProcessFeeText', __('Application Processing Fee Text'));
        $col->addTextArea('applicationProcessFeeText')
            ->setValue($applicationProcessFeeText)
            ->required();
   
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
