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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Resources'), 'resources_manage.php')
    ->add(__('Add Resource'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/resources_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $search = $_GET['search'] ?? '';

        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Planner/resources_manage_edit.php&gibbonResourceID='.$_GET['editID'].'&search='.$_GET['search'];
        }
        $page->return->setEditLink($editLink);


        if ($search != '') {
            $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Planner', 'resources_manage.php')->withQueryParam('search', $search));
        }

        $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/resources_manage_addProcess.php?search='.$search);
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->addHiddenValue('address', $session->get('address'));

        $form->addRow()->addHeading('Resource Contents', __('Resource Contents'));

        $types = array('File' => __('File'), 'HTML' => __('HTML'), 'Link' => __('Link'));
        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($types)->required()->placeholder();

        // File
        $form->toggleVisibilityByClass('resourceFile')->onSelect('type')->when('File');
        $row = $form->addRow()->addClass('resourceFile');
            $row->addLabel('file', __('File'));
            $row->addFileUpload('file')->required();

        // HTML
        $form->toggleVisibilityByClass('resourceHTML')->onSelect('type')->when('HTML');
        $row = $form->addRow()->addClass('resourceHTML');
            $column = $row->addColumn()->setClass('');
            $column->addLabel('html', __('HTML'));
            $column->addEditor('html', $guid)->required();

        // Link
        $form->toggleVisibilityByClass('resourceLink')->onSelect('type')->when('Link');
        $row = $form->addRow()->addClass('resourceLink');
            $row->addLabel('link', __('Link'));
            $row->addURL('link')->maxLength(255)->required();

        $form->addRow()->addHeading('Resource Details', __('Resource Details'));

        $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')->required()->maxLength(60);

        $settingGateway = $container->get(SettingGateway::class);

        $categories = $settingGateway->getSettingByScope('Resources', 'categories');
        $row = $form->addRow();
            $row->addLabel('category', __('Category'));
            $row->addSelect('category')->fromString($categories)->required()->placeholder();

        $purposesGeneral = $settingGateway->getSettingByScope('Resources', 'purposesGeneral');
        $purposesRestricted = ($highestAction == 'Manage Resources_all')? $settingGateway->getSettingByScope('Resources', 'purposesRestricted') : '';
        $row = $form->addRow();
            $row->addLabel('purpose', __('Purpose'));
            $row->addSelect('purpose')->fromString($purposesGeneral)->fromString($purposesRestricted)->placeholder();

        $sql = "SELECT tag as value, CONCAT(tag, ' <i>(', count, ')</i>') as name FROM gibbonResourceTag WHERE count>0 ORDER BY tag";
        $row = $form->addRow()->addClass('tags');
            $column = $row->addColumn();
            $column->addLabel('tags', __('Tags'))->description(__('Use lots of tags!'));
            $column->addFinder('tags')
                ->fromQuery($pdo, $sql)
                ->required()
                ->setParameter('hintText', __('Type a tag...'))
                ->setParameter('allowFreeTagging', true);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Groups'))->description(__('Students year groups which may participate'));
            $row->addCheckboxYearGroup('gibbonYearGroupID')->checkAll()->addCheckAllNone();

        $row = $form->addRow();
            $row->addLabel('description', __('Description'));
            $row->addTextArea('description')->setRows(8);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
