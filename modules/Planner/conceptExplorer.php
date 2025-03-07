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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('Concept Explorer'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/conceptExplorer.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Get all concepts in current year and convert to ordered array
    $tagsAll = getTagList($connection2, $session->get('gibbonSchoolYearID'));

    //Deal with paramaters
    $tags = array();
    if (isset($_GET['tags'])) {
        $tags = $_GET['tags'] ?? '';
    }
    else if (isset($_GET['tag'])) {
        $tags[0] = $_GET['tag'] ?? '';
    }
    $gibbonYearGroupID = isset($_GET['gibbonYearGroupID'])? $_GET['gibbonYearGroupID'] : '';

    //Display concept cloud
    if (count($tags) == 0) {
        echo '<h2>';
        echo __('Concept Cloud');
        echo '</h2>';
        echo getTagCloud($guid, $connection2, $session->get('gibbonSchoolYearID'));
    }

    //Allow tag selection
    $form = Form::create('conceptExplorer', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->setTitle(__('Choose Concept'));
    $form->setClass('noIntBorder w-full');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/conceptExplorer.php');

    $row = $form->addRow();
        $row->addLabel('tags', __('Concepts & Keywords'));
        $row->addSelect('tags')->fromArray(array_column($tagsAll, 1))->selectMultiple()->required()->selected($tags);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (count($tags) > 0) {
        //Set up for edit access
        $highestAction = getHighestGroupedAction($guid, '/modules/Planner/units.php', $connection2);
        $departments = array();
        if ($highestAction == 'Unit Planner_learningAreas') {
            $departmentCount = 1 ;
            try {
                $dataSelect = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                $sqlSelect = "SELECT gibbonDepartment.gibbonDepartmentID FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonDepartment.name";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) { }
            while ($rowSelect = $resultSelect->fetch()) {
                $departments[$departmentCount] = $rowSelect['gibbonDepartmentID'];
                $departmentCount ++;
            }
        }

        //Search for units with these tags
        try {
            $data = array() ;

            //Tag filter
            $sqlWhere = ' AND (';
            $count = 0;
            foreach ($tags as $tag) {
                $data["tag$count"] = "%,$tag,%";
                $sqlWhere .= "concat(',',tags,',') LIKE :tag$count OR ";
                $count ++;
            }
            if ($sqlWhere == ' AND (')
                $sqlWhere = '';
            else
                $sqlWhere = substr($sqlWhere, 0, -3).')';

            //Year group Filters
            if ($gibbonYearGroupID != '') {
                $data['gibbonYearGroupID'] = '%'.$gibbonYearGroupID.'%';
                $sqlWhere .= ' AND gibbonYearGroupIDList LIKE :gibbonYearGroupID ';
            }


            $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
            $sql = "SELECT gibbonUnitID, gibbonUnit.name, gibbonUnit.description, attachment, tags, gibbonCourse.name AS course, gibbonDepartmentID, gibbonCourse.gibbonCourseID, gibbonSchoolYearID FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonUnit.map='Y' AND gibbonCourse.map='Y' $sqlWhere ORDER BY gibbonUnit.name";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }


        if ($result->rowCount() < 1) {
            echo $page->getBlankSlate();
        }
        else {
            echo '<h2 class=\'bigTop\'>';
            echo __('Results');
            echo '</h2>';

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th style=\'width: 23%\'>';
            echo __('Unit');
            echo "<br/><span style='font-style: italic; font-size: 85%'>".__('Course').'</span>';
            echo '</th>';
            echo '<th style=\'width: 37%\'>';
            echo __('Description');
            echo '</th>';
            echo "<th style=\'width: 30%\'>";
            echo __('Concepts & Keywords');
            echo '</th>';
            echo "<th style='width: 10%'>";
            echo __('Actions');
            echo '</th>';
            echo '</tr>';


            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                //Can this unit be edited?
                $canEdit = false ;
                if ($highestAction == 'Unit Planner_all') {
                    $canEdit = true ;
                }
                else if ($highestAction == 'Unit Planner_learningAreas') {
                    foreach ($departments AS $department) {
                        if ($department == $row['gibbonDepartmentID']) {
                            $canEdit = true ;
                        }
                    }
                }

                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo $row['name'].'<br/>';
                echo "<span style='font-style: italic; font-size: 85%'>".$row['course'].'</span>';
                echo '</td>';
                echo '<td>';
                echo $row['description'].'<br/>';
                if ($row['attachment'] != '') {
                    echo "<br/><br/><a href='".$session->get('absoluteURL').'/'.$row['attachment']."'>".__('Download Unit Outline').'</a></li>';
                }
                echo '</td>';
                echo '<td>';
                $tagsUnit = explode(',', $row['tags']);
                $tagsOutput = '' ;
                foreach ($tagsUnit as $tag) {
                    $style = '';
                    foreach ($tags AS $tagInner) {
                        if ($tagInner == $tag) {
                            $style = 'style=\'color: #000; font-weight: bold\'';
                        }
                    }
                    $tagsOutput .= "<a $style href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/conceptExplorer.php&tag=$tag'>".$tag.'</a>, ';
                }
                if ($tagsOutput != '')
                    $tagsOutput = substr($tagsOutput, 0, -2);
                echo $tagsOutput;
                echo '</td>';
                echo '<td>';
                    if ($canEdit) {
                        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/units_edit.php&gibbonUnitID='.$row['gibbonUnitID']."&gibbonCourseID=".$row['gibbonCourseID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/units_dump.php&gibbonCourseID=".$row['gibbonCourseID']."&gibbonUnitID=".$row['gibbonUnitID']."&gibbonSchoolYearID=".$row['gibbonSchoolYearID']."&sidebar=false'><img title='".__('View')."' src='./themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a>";
                    }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
