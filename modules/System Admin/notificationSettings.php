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
use Gibbon\Services\Format;
use Gibbon\Domain\System\NotificationGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/notificationSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Notification Settings'));

    echo '<h3>';
    echo __('Notification Events');
    echo '</h3>';

    echo '<p>';
    echo __('This section allows you to manage system-wide notifications. When a notification event occurs, any users subscribed to that event will receive a notification. Each event below can optionally be turned off to prevent all notifications of that type.');
    echo '</p>';

    $gateway = new NotificationGateway($pdo);
    $result = $gateway->selectAllNotificationEvents();

    $nameFormat = function ($row) use ($session) {
        $output = __($row['event']);
        if ($row['type'] == 'CLI') {
            $output .=  Format::tooltip(icon('solid', 'config', 'size-5 ml-2 fill-current text-gray-400 float-right'), __('This is a CLI notification event. It will only run if the corresponding CLI script has been setup on the server.'));
        }
        return $output;
    };

    $table = DataTable::create('notificationEvents');

    $table->modifyRows(function($notification, $row) {
        if ($notification['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addColumn('moduleName', __('Module'))->translatable();
    $table->addColumn('event', __('Name'))->format($nameFormat);
    $table->addColumn('listenerCount', __('Subscribers'));
    $table->addColumn('active', __('Active'))->format(Format::using('yesNo', 'active'));

    $actions = $table->addActionColumn()->addParam('gibbonNotificationEventID');
    $actions->addAction('edit', __('Edit'))
            ->setURL('/modules/System Admin/notificationSettings_manage_edit.php');

    echo $table->render($result->toDataSet());
}
