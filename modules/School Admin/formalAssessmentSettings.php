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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formalAssessmentSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Formal Assessment Settings'));

    $form = Form::create('formalAssessmentSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/formalAssessmentSettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Internal Assessment Settings', __('Internal Assessment Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('Formal Assessment', 'internalAssessmentTypes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $form->addRow()->addHeading('Primary External Assessement', __('Primary External Assessement'))->append(__('These settings allow a particular type of external assessment to be associated with each year group. The selected assessment will be used as the primary assessment to be used as a baseline for comparison (for example, within the Markbook). You are required to select a particular field category from which to draw data (if no category is chosen, the data will not be saved).'));

    $row = $form->addRow()->setClass('break');
        $row->addContent(__('Year Group'))->setClass('w-24');
        $row->addContent(__('External Assessment'));
        $row->addContent(__('Field Set'));

    // External Assessments, $key => $valye pairs
    $sql = "SELECT gibbonExternalAssessmentID as `value`, name FROM gibbonExternalAssessment WHERE active='Y' ORDER BY name";
    $results = $pdo->executeQuery(array(), $sql);
    $externalAssessments = $results->fetchAll(\PDO::FETCH_KEY_PAIR);

    // External Assessment Field Sets
    $sql = "SELECT gibbonExternalAssessmentField.gibbonExternalAssessmentID, category FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE active='Y' ORDER BY gibbonExternalAssessmentID, category";
    $results = $pdo->executeQuery(array(), $sql);

    $externalAssessmentsFieldSetNames = array();
    $externalAssessmentsFieldSetIDs = array();

    // Build two arrays, one of $key => $value for the dropdown, one of $key => $class for the chainedTo method
    if ($results && $results->rowCount() > 0) {
        while ($assessment = $results->fetch()) {
            $key = $assessment['gibbonExternalAssessmentID'].'-'.$assessment['category'];
            $externalAssessmentsFieldSetNames[$key] = mb_substr($assessment['category'], mb_strpos($assessment['category'], '_'));
            $externalAssessmentsFieldSetIDs[$key] = $assessment['gibbonExternalAssessmentID'];
        }
    }

    // Get and unserialize the current settings value
    $primaryExternalAssessmentByYearGroup = unserialize($settingGateway->getSettingByScope('School Admin', 'primaryExternalAssessmentByYearGroup'));

    // Split the ID portion off of the ID-category pair, for the first dropdown
    $primaryExternalAssessmentIDsByYearGroup = array_map(function($v) { return (!empty($v) && mb_strpos($v, '-') !== false? mb_substr($v, 0, mb_strpos($v, '-')) : $v); }, $primaryExternalAssessmentByYearGroup);

    $sql = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
    $result = $pdo->executeQuery(array(), $sql);

    // Add one row per year group
    while ($yearGroup = $result->fetch()) {
        $id = $yearGroup['gibbonYearGroupID'];

        $selectedID = (isset($primaryExternalAssessmentIDsByYearGroup[$id]))? $primaryExternalAssessmentIDsByYearGroup[$id] : '';
        $selectedField = (isset($primaryExternalAssessmentByYearGroup[$id]))? $primaryExternalAssessmentByYearGroup[$id] : '';

        $row = $form->addRow();
        $row->addContent($yearGroup['name'])->setClass('w-24');

        $row->addSelect('gibbonExternalAssessmentID['.$id.']')
            ->setID('gibbonExternalAssessmentID'.$id)
            ->placeholder()
            ->fromArray($externalAssessments)
            ->selected($selectedID);

        $row->addSelect('category['.$id.']')
            ->setID('category'.$id)
            ->placeholder()
            ->fromArray($externalAssessmentsFieldSetNames)
            ->selected($selectedField)
            ->chainedTo('gibbonExternalAssessmentID'.$id, $externalAssessmentsFieldSetIDs);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
