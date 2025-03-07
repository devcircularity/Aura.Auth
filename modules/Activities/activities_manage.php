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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Set returnTo point for upcoming pages
    $page->breadcrumbs->add(__('Manage Activities'));

    /** @var SettingGateway        $settingGateway */
    $settingGateway = $container->get(SettingGateway::class);
    /** @var SchoolYearTermGateway $schoolYearTermGateway */
    $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);

    $search = $_GET['search'] ?? '';
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $gibbonActivityCategoryID = $_GET['gibbonActivityCategoryID'] ?? '';
    $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
    $enrolmentType = $settingGateway->getSettingByScope('Activities', 'enrolmentType');
    $schoolTerms = $schoolYearTermGateway->selectTermsBySchoolYear((int) $session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $yearGroups = getYearGroups($connection2);

    $activityGateway = $container->get(ActivityGateway::class);

    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria(true)
        ->searchBy($activityGateway->getSearchableColumns(), $search)
        ->filterBy('term', $gibbonSchoolYearTermID)
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->filterBy('category', $gibbonActivityCategoryID)
        ->sortBy($dateType != 'Date' ? 'gibbonSchoolYearTermIDList' : 'programStart', $dateType != 'Date' ? 'ASC' : 'DESC')
        ->sortBy('name');

    $criteria->fromPOST();

    echo '<h2>';
    echo __('Search & Filter');
    echo '</h2>';

    $paymentOn = $settingGateway->getSettingByScope('Activities', 'payment') != 'None' and $settingGateway->getSettingByScope('Activities', 'payment') != 'Single';

    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', "/modules/".$session->get('module')."/activities_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search'))->description(__('Activity name.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    if ($dateType != 'Date') {
        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sql = "SELECT gibbonSchoolYearTermID as value, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearTermID', __('Term'));
            $row->addSelect('gibbonSchoolYearTermID')->fromQuery($pdo, $sql, $data)->selected($gibbonSchoolYearTermID)->placeholder();
    }

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

    $categories = $container->get(ActivityCategoryGateway::class)->selectCategoriesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();
    $row = $form->addRow();
        $row->addLabel('gibbonActivityCategoryID', __('Category'));
        $row->addSelect('gibbonActivityCategoryID')->fromArray($categories)->placeholder()->selected($gibbonActivityCategoryID);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __('Activities');
    echo '</h2>';

    $activities = $activityGateway->queryActivitiesBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activities_manageProcessBulk.php');
    $form->addHiddenValue('search', $search);

    $bulkActions = array(
        'Duplicate' => __('Duplicate'),
        'DuplicateParticipants' => __('Duplicate With Participants'),
        'Delete' => __('Delete'),
    );
    $sql = "SELECT gibbonSchoolYearID as value, gibbonSchoolYear.name FROM gibbonSchoolYear WHERE (status='Upcoming' OR status='Current') ORDER BY sequenceNumber LIMIT 0, 2";

    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSelect('gibbonSchoolYearIDCopyTo')
            ->fromQuery($pdo, $sql)
            ->setClass('shortWidth schoolYear');
        $col->addSubmit(__('Go'));

    $form->toggleVisibilityByClass('schoolYear')->onSelect('action')->when(array('Duplicate', 'DuplicateParticipants'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('activities', $criteria)->withData($activities);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Activities/activities_manage_add.php')
        ->addParam('search', $search)
        ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
        ->displayLabel();

    $table->modifyRows(function ($activity, $row) {
        if ($activity['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'active:Y'          => __('Active').': '.__('Yes'),
        'active:N'          => __('Active').': '.__('No'),
        'registration:Y'    => __('Registration').': '.__('Yes'),
        'registration:N'    => __('Registration').': '.__('No'),
        'enrolment:less'    => __('Enrolment').': &lt; '.__('Full'),
        'enrolment:full'    => __('Enrolment').': '.__('Full'),
        'enrolment:greater' => __('Enrolment').': &gt; '.__('Full'),
    ]);

    if ($enrolmentType == 'Competitive') {
        $table->addMetaData('filterOptions', ['status:waiting' => __('Waiting List')]);
    } else {
        $table->addMetaData('filterOptions', ['status:pending' => __('Pending')]);
    }

    $table->addMetaData('bulkActions', $col);

    // COLUMNS
    $table->addColumn('name', __('Activity'))
        ->format(function($activity) {
            return $activity['name'].'<br/><span class="text-xs italic">'.$activity['type'].'</span>';
        });

    $table->addColumn('days', __('Days'))
        ->notSortable()
        ->format(function($activity) use ($activityGateway) {
            return implode(', ', array_map('__', $activityGateway->selectWeekdayNamesByActivity($activity['gibbonActivityID'])->fetchAll(\PDO::FETCH_COLUMN)));
        });

    $table->addColumn('yearGroups', __('Years'))
        ->format(function($activity) use ($yearGroups) {
            return ($activity['yearGroupCount'] >= count($yearGroups)/2)? '<i>'.__('All').'</i>' : $activity['yearGroups'];
        });

    $table->addColumn('date', $dateType != 'Date'? __('Term') : __('Dates'))
        ->sortable($dateType != 'Date' ? ['gibbonSchoolYearTermIDList'] : ['programStart', 'programEnd'])
        ->format(function($activity) use ($dateType, $schoolTerms) {
            if (empty($schoolTerms)) return '';
            if ($dateType != 'Date') {
                $termList = array_intersect_key($schoolTerms, array_flip(explode(',', $activity['gibbonSchoolYearTermIDList'] ?? '')));
                if (!empty($termList)) {
                    return implode('<br/>', $termList);
                }
            } else {
                return Format::dateRangeReadable($activity['programStart'], $activity['programEnd']);
            }
        });

    if ($paymentOn) {
        $table->addColumn('payment', __('Cost'))
            ->description($session->get('currency'))
            ->format(function($activity) {
                $payment = ($activity['payment'] > 0)
                    ? Format::currency($activity['payment']) . '<br/>' . __($activity['paymentType'])
                    : '<i>'.__('None').'</i>';
                if ($activity['paymentFirmness'] != 'Finalised') $payment .= '<br/><i>'.__($activity['paymentFirmness']).'</i>';

                return $payment;
            });
    }

    $table->addColumn('provider', __('Provider'))
        ->format(function($activity) use ($session){
            return ($activity['provider'] == 'School')? $session->get('organisationNameShort') : __('External');
        });

    $table->addColumn('enrolment', __('Enrolment'))
        ->format(function($activity) {
            return $activity['enrolment'] .' / '. $activity['maxParticipants']
                . (!empty($activity['waiting'])? '<br><small><i>' .$activity['waiting'].' '.__('Waiting') .'</i></small>' : '')
                . (!empty($activity['pending'])? '<br><small><i>' .$activity['pending'].' '.__('Pending') .'</i></small>' : '');
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonActivityID')
        ->addParam('search', $criteria->getSearchText(true))
        ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
        ->format(function ($activity, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Activities/activities_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Activities/activities_manage_delete.php');

            $actions->addAction('enrolment', __('Enrolment'))
                    ->setURL('/modules/Activities/activities_manage_enrolment.php')
                    ->setIcon('attendance');
        });

    $table->addCheckboxColumn('gibbonActivityID');

    echo $form->getOutput();
}
