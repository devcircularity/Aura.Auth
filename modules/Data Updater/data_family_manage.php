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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\DataUpdater\FamilyUpdateGateway;
use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Family Data Updates'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_GET['search'] ?? '';

    // School Year Picker
    if (!empty($gibbonSchoolYearID)) {
        $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);
    }

    $gateway = $container->get(FamilyUpdateGateway::class);

    // QUERY
    $criteria = $gateway->newQueryCriteria(true)
        ->searchBy($gateway->getSearchableColumns(), $search)
        ->sortBy('status')
        ->sortBy('timestamp', 'DESC')
        ->fromPOST();

    // SEARCH
    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('q', '/modules/Data Updater/data_family_manage.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(_('Family Name'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $form->addRow()->addSearchSubmit($session, __('Clear Search'), ['gibbonSchoolYearID']);
    echo $form->getOutput();


    $dataUpdates = $gateway->queryDataUpdates($criteria, $gibbonSchoolYearID);

    // DATA TABLE
    $table = DataTable::createPaginated('familyUpdateManage', $criteria);

    $table->modifyRows(function ($update, $row) {
        if ($update['status'] != 'Pending') $row->addClass('current');
        return $row;
    });

    // COLUMNS
    $table->addColumn('familyName', __('Target Family'));
    $table->addColumn('updater', __('Requesting User'))
        ->sortable(['updater.surname', 'updater.preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonIDUpdater', 'updaterTitle', 'updaterPreferredName', 'updaterSurname', 'Parent']));

    $table->addColumn('timestamp', __('Date & Time'))->format(Format::using('dateTime', 'timestamp'));
    $table->addColumn('status', __('Status'))->translatable()->width('12%');

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonFamilyUpdateID')
        ->format(function ($update, $actions) {
            if ($update['status'] == 'Pending') {
                $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Data Updater/data_family_manage_edit.php');

                $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Data Updater/data_family_manage_delete.php');
            }
        });

    echo $table->render($dataUpdates);
}
