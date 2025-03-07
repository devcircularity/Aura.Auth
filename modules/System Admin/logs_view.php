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
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\LogGateway;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/logs_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('View Logs'));

    $ip = isset($_GET['ip'])? $_GET['ip'] : '';
    $title = isset($_GET['title'])? $_GET['title'] : '';
    $gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';

    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setTitle(__('Filters'));
    $form->setClass('noIntBorder w-full');
    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/logs_view.php');

    $sql = "SELECT DISTINCT title AS value, title AS name FROM gibbonLog ORDER BY title";
    $row = $form->addRow();
        $row->addLabel('title', __('Title'));
        $row->addSelect('title')->fromQuery($pdo, $sql)->selected($title)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('User'));
        $row->addSelectUsers('gibbonPersonID')->selected($gibbonPersonID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('ip', __('IP Address'));
        $row->addTextField('ip')->setValue($ip);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();


    // QUERY
    $logGateway = $container->get(LogGateway::class);
    $criteria = $logGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->filterBy('ip', $ip)
        ->filterBy('title', $title)
        ->filterBy('gibbonPersonID', $gibbonPersonID)
        ->fromPOST();

    $logs = $logGateway->queryLogs($criteria, $session->get('gibbonSchoolYearID'));

    $table = DataTable::createPaginated('logView', $criteria);
    $table->setTitle(__('Data'));

    $table->addHeaderAction('purge', __('Purge Logs'))
        ->setIcon('garbage')
        ->setURL('/modules/System Admin/logs_view_purge.php')
        ->displayLabel();

    $table->addExpandableColumn('comment')
        ->format(function($log) {
            $detailsArray = !empty($log['serialisedArray']) ? @unserialize($log['serialisedArray']) : [];

            $details = '';
            if (is_array($detailsArray) && count($detailsArray) > 0) {
                $details = "<table class='smallIntBorder' style='width:100%;'>";
                foreach ($detailsArray as $fieldName => $fieldValue) {
                    if ($fieldName == 'data') continue;

                    $fieldValue = is_array($fieldValue)
                        ? htmlentities(json_encode($fieldValue))
                        : htmlentities($fieldValue ?? '');
                    $details .= sprintf('<tr><td><b>%1$s</b></td><td style="line-break: anywhere; width: 645px;">%2$s</td></tr>', $fieldName, (substr($fieldValue, 0, 2) == 'a:') ? __("Contains serialised data.") : $fieldValue);
                }
                $details .= "</table>";
            } else {
                $details = $log['serialisedArray'];
            }

            return $details;
        });
    $table->addColumn('gibbonLogID', __('Log ID'));
    $table->addColumn('timestamp', __('Timestamp'))
        ->format(function ($log) {
          return Format::dateTime($log['timestamp']);
        });
    $table->addColumn('title', __('Title'));
    $table->addColumn('type', __('Type'))
        ->format(function ($log) {
          return (empty($log['module'])) ? __('System') : __($log['module']) ;
        });
    $table->addColumn('username', __('User'))
        ->format(function ($log) {
          return Format::name('', $log['preferredName'], $log['surname'], 'Student', false, true)."</br>". Format::small($log['username']) ;
        });
    $table->addColumn('ip', __('IP Address'));


    echo $table->render($logs);

}
