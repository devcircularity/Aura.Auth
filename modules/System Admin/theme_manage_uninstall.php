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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\System\ThemeGateway;

$gibbonThemeID = $_GET['gibbonThemeID'] ?? '';
$orphaned = $_GET['orphaned'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage_uninstall.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Themes'), 'theme_manage.php')
        ->add(__('Uninstall Theme'));

    //Check if theme specified
    if ($gibbonThemeID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $themeGateway = $container->get(ThemeGateway::class);
        //Check for existence of theme
        $dataTheme = array('gibbonThemeID' => $gibbonThemeID, 'active' => 'N');
        $existsTheme = $themeGateway->selectBy($dataTheme)->rowCount();

        if ($existsTheme == 0) {
            echo "<div class='error'>";
            echo __('The specified theme cannot be found or is active and so cannot be removed.');
            echo '</div>';
        } else {
            $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/theme_manage_uninstallProcess.php?gibbonThemeID=$gibbonThemeID&orphaned=$orphaned");
            echo $form->getOutput();
        }
    }
}
