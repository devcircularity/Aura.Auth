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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Activities\ActivityTypeGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Activity Settings'));

    echo Format::alert("Please note that the old competitive activity sign-up system has been replaced with a much more balanced and feature-rich sign-up system. This gives students better backup choices, and reduces the stress of sign-up by removing the time pressure, which is also better for server load. The settings on this page control the old sign-up system, which is still accessible through the Activities > View Activities page.<br/></br>The new system can be found in Activities > Manage Categories, where each category can have it's own settings, and works in conjunction with the new Explore Activities page. If you do not plan to use the new system right away, please disable Explore Activities in User Admin > Manage Permissions. <br/></br><b>The old competitive sign-up system will be deprecated and removed in Gibbon v30.0.00</b>", 'message');

    $form = Form::create('activitySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activitySettingsProcess.php');

    $settingGateway = $container->get(SettingGateway::class);
    $activityTypeGateway = $container->get(ActivityTypeGateway::class);

    // QUERY
    $criteria = $activityTypeGateway->newQueryCriteria()
        ->sortBy(['name'])
        ->fromArray($_POST);

    $activityTypes = $activityTypeGateway->queryActivityTypes($criteria);

    // DATA TABLE
    $table = DataTable::createPaginated('activityTypes', $criteria);
    $table->setTitle(__('Activity Types'));
    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/School Admin/activitySettings_type_add.php')
        ->displayLabel();

    $table->addColumn('name', __('Name'));
    $table->addColumn('access', __('Access'));
    $table->addColumn('enrolmentType', __('Enrolment Type'));
    $table->addColumn('maxPerStudent', __('Max per Student'))->width('10%');
    $table->addColumn('waitingList', __('Waiting List'))->width('10%');
    $table->addColumn('backupChoice', __('Backup Choice'))->width('10%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonActivityTypeID')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/School Admin/activitySettings_type_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/School Admin/activitySettings_type_delete.php');
        });

    echo $table->render($activityTypes);


    $form = Form::create('activitySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activitySettingsProcess.php');
    $form->setTitle(__('Settings'));
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('activityTypes', '');

    $accessTypes = array(
        'None' => __('None'),
        'View' => __('View'),
        'Register' =>  __('Register')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'access', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($accessTypes)->selected($setting['value'])->required();

    $dateTypes = array(
        'Date' => __('Date'),
        'Term' =>  __('Term')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'dateType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($dateTypes)->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('perTerm')->onSelect($setting['name'])->when('Term');

    $setting = $settingGateway->getSettingByScope('Activities', 'maxPerTerm', true);
    $row = $form->addRow()->addClass('perTerm');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('0,1,2,3,4,5')->selected($setting['value'])->required();

    $paymentTypes = array(
        'None' => __('None'),
        'Single' => __('Single'),
        'Per Activity' =>  __('Per Activity'),
        'Single + Per Activity' =>  __('Single + Per Activity')
    );
    $setting = $settingGateway->getSettingByScope('Activities', 'payment', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($paymentTypes)->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Activities', 'disableExternalProviderSignup', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Activities', 'hideExternalProviderCost', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
