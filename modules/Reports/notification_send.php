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
use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Module\Reports\Domain\ReportingProofGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/notification_send.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Send Notifications'));

    $step = $_POST['step'] ?? 1;
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);
    $reportingCycles = $container->get(ReportingCycleGateway::class)->selectReportingCyclesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();

    if (empty($reportingCycles)) {
        $page->addMessage(__('There are no active reporting cycles.'));
        return;
    }

    if ($step == 1) {
        // STEP 1
        $form = Form::create('notificationSend', $session->get('absoluteURL').'/index.php?q=/modules/Reports/notification_send.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('step', 2);

        $form->addRow()->addHeading('Step 1', __('Step 1'));
        $types = [
            __('Staff') => [
                'proofReadingEdits' => __('Proof Reading Edits'),
            ],
        ];
        $row = $form->addRow();
            $row->addLabel('type', __('Notification'));
            $row->addSelect('type')
                ->fromArray($types)
                ->isRequired()
                ->placeholder()
                ->selected($_POST['type'] ?? '');


        $row = $form->addRow();
            $row->addLabel('gibbonReportingCycleIDList', __('Reporting Cycle'));
            $row->addSelect('gibbonReportingCycleIDList')
                ->fromArray($reportingCycles)
                ->selectMultiple()
                ->isRequired();

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();
    } else {
        // STEP 2
        $type = $_POST['type'] ?? '';
        $gibbonReportingCycleIDList = $_POST['gibbonReportingCycleIDList'] ?? [];

        if (empty($type) || empty($gibbonReportingCycleIDList)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $notificationCount = 0;
        $notificationText = '';
        $notificationList = '';

        if ($type == 'proofReadingEdits') {
            $edits = $container->get(ReportingProofGateway::class)->selectPendingProofReadingEdits($gibbonReportingCycleIDList)->fetchGroupedUnique();

            $notificationCount = count($edits);
            $notificationList = $notificationCount > 0 ? '<details><ul><li>'.Format::nameList($edits, 'Staff', false, true, '</li><li>').'</li></ul></details>' : '';
            $notificationText = __('There are {count} pending edits for your reports. Please visit the Proof Read page to view these and complete your reporting comments.');
        } elseif ($type == 'reportsAvailable') {
            $parents = $container->get(ReportArchiveEntryGateway::class)->selectParentArchiveAccessByReportingCycle($gibbonReportingCycleIDList)->fetchAll();
            $notificationCount = count($parents);
            $notificationText = __('Report Cards are now available online.');
        }

        // FORM
        $form = Form::create('notificationSend', $session->get('absoluteURL').'/modules/Reports/notification_sendProcess.php');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('type', $type);
        $form->addHiddenValue('gibbonReportingCycleIDList', implode(',', $gibbonReportingCycleIDList));

        $form->addRow()->addHeading('Step 2', __('Step 2'));

        $form->addRow()->addAlert(__('This action will send the following notification to {count} users.', ['count' => '<b>'.$notificationCount.'</b>']).$notificationList, 'message');

        $col = $form->addRow()->addColumn();
            $col->addLabel('notificationText', __('Notification'));
            $col->addTextArea('notificationText')->setValue($notificationText);

        $row = $form->addRow();
            $row->addSubmit(__('Send'));

        echo $form->getOutput();
    }
}
