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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/permission_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Permissions'));

    $returns = array();
    $returns['error3'] = sprintf(__('Your PHP environment cannot handle all of the fields in this form (the current limit is %1$s). Ask your web host or system administrator to increase the value of the max_input_vars in php.ini.'), ini_get('max_input_vars'));
    $page->return->addReturns($returns);

    echo '<h2>';
    echo __('Filter');
    echo '</h2>';

    $gibbonModuleID = isset($_GET['gibbonModuleID'])? $_GET['gibbonModuleID'] : '';
    $gibbonRoleID = isset($_GET['gibbonRoleID'])? $_GET['gibbonRoleID'] : '';

    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/permission_manage.php');

    $sql = "SELECT gibbonModuleID as value, name FROM gibbonModule WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonModuleID', __('Module'));
        $row->addSelect('gibbonModuleID')->fromQuery($pdo, $sql)->selected($gibbonModuleID)->placeholder();

    $sql = "SELECT gibbonRoleID as value, name FROM gibbonRole ORDER BY type, nameShort";
    $row = $form->addRow();
        $row->addLabel('gibbonRoleID', __('Role'));
        $row->addSelect('gibbonRoleID')->fromQuery($pdo, $sql)->selected($gibbonRoleID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (empty($gibbonModuleID) && empty($gibbonRoleID)) {
        echo Format::alert(__('Select a module or role from the filters above to view and edit user permissions.'), 'message');
        return;
    }

    try {
        if (!empty($gibbonModuleID)) {
            $dataModules = array('gibbonModuleID' => $gibbonModuleID);
            $sqlModules = "SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID AND active='Y'";
        } else {
            $dataModules = array();
            $sqlModules = "SELECT * FROM gibbonModule WHERE active='Y' ORDER BY name";
        }

        $resultModules = $connection2->prepare($sqlModules);
        $resultModules->execute($dataModules);
    } catch (PDOException $e) {
    }

    try {
        if (!empty($gibbonRoleID)) {
            $dataRoles = array('gibbonRoleID' => $gibbonRoleID);
            $sqlRoles = 'SELECT gibbonRoleID, nameShort, category, name FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
        } else {
            $dataRoles = array();
            $sqlRoles = 'SELECT gibbonRoleID, nameShort, category, name FROM gibbonRole ORDER BY type, nameShort';
        }
        $resultRoles = $connection2->prepare($sqlRoles);
        $resultRoles->execute($dataRoles);
    } catch (PDOException $e) {
    }

    
        $dataPermissions = array();
        $sqlPermissions = 'SELECT gibbonRoleID, gibbonActionID FROM gibbonPermission';
        $resultPermissions = $connection2->prepare($sqlPermissions);
        $resultPermissions->execute($dataPermissions);

    if ($resultRoles->rowCount() < 1 or $resultModules->rowCount() < 1) {
        $page->addError(__('Your request failed due to a database error.'));
    } else {
        //Fill role and permission arrays
        $roleArray = ($resultRoles->rowCount() > 0)? $resultRoles->fetchAll() : array();
        $permissionsArray = ($resultPermissions->rowCount() > 0)? $resultPermissions->fetchAll() : array();
        $totalCount = 0;

        $form = Form::createBlank('permissions', $session->get('absoluteURL').'/modules/'.$session->get('module').'/permission_manageProcess.php');
        $form->setClass('overflow-x-auto');
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonModuleID', $gibbonModuleID);
        $form->addHiddenValue('gibbonRoleID', $gibbonRoleID);

        while ($rowModules = $resultModules->fetch()) {
            $form->addRow()->addHeading($rowModules['name'], __($rowModules['name']));
            $table = $form->addRow()->addTable()->setClass('mini rowHighlight columnHighlight w-full');

            
                $dataActions = array('gibbonModuleID' => $rowModules['gibbonModuleID']);
                $sqlActions = 'SELECT * FROM gibbonAction WHERE gibbonModuleID=:gibbonModuleID ORDER BY name';
                $resultActions = $connection2->prepare($sqlActions);
                $resultActions->execute($dataActions);

            if ($resultActions->rowCount() > 0) {
                $row = $table->addHeaderRow();
                $row->addContent(__('Action'))->wrap('<div style="width: 350px;">', '</div>');

                // Add headings for each Role
                foreach ($roleArray as $role) {
                    $row->addContent(__($role['nameShort']))->wrap('<span title="'.htmlPrep(__($role['name'])).'">', '</span>');
                }

                while ($rowActions = $resultActions->fetch()) {
                    $row = $table->addRow();

                    // Add names and hover-over descriptions for each Action
                    if ($rowModules['type'] == 'Core') {
                        $row->addContent(__($rowActions['name']))->wrap('<span title="'.htmlPrep(__($rowActions['description'])).'">', '</span>');
                    } else {
                        $row->addContent(__($rowActions['name']), $rowModules['name'])->wrap('<span title="'.htmlPrep(__($rowActions['description'], $rowModules['name'])).'">', '</span>');
                    }

                    foreach ($roleArray as $role) {
                        $checked = false;

                        // Check to see if the current action is turned on
                        foreach ($permissionsArray as $permission) {
                            if ($permission['gibbonRoleID'] == $role['gibbonRoleID'] && $permission['gibbonActionID'] == $rowActions['gibbonActionID']) {
                                $checked = true;
                            }
                        }

                        $readonly = ($rowActions['categoryPermission'.$role['category']] == 'N');
                        $checked = !$readonly && $checked;

                        $name = 'permission['.$rowActions['gibbonActionID'].']['.$role['gibbonRoleID'].']';
                        $row->addCheckbox($name)->disabled($readonly)->checked($checked)->alignLeft();

                        ++$totalCount;
                    }
                }
            }
        }

        $form->addHiddenValue('totalCount', $totalCount);

        $max_input_vars = ini_get('max_input_vars');
        $total_vars = $totalCount + 10;
        $total_vars_rounded = (ceil($total_vars / 1000) * 1000) + 1000;

        if ($total_vars > $max_input_vars) {
            $row = $form->addRow();
            $row->addAlert('php.ini max_input_vars='.$max_input_vars.'<br />')
                ->append(__('Number of inputs on this page').'='.$total_vars.'<br/>')
                ->append(sprintf(__('This form is very large and data will be truncated unless you edit php.ini. Add the line <i>max_input_vars=%1$s</i> to your php.ini file on your server.'), $total_vars_rounded));
        } else {
            $row = $form->addRow();
            $row->addSubmit()->addClass('mt-2');
        }

        echo $form->getOutput();
    }
}
