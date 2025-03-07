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
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

include './moduleFunctions.php';

//Search & Filters
$search = $_GET['search'] ?? '';

$filter2 = $_GET['filter2'] ?? '';


$gibbonRubricID = $_GET['gibbonRubricID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rubrics_edit_editRowsColumns.php&gibbonRubricID=$gibbonRubricID&sidebar=true&search=$search&filter2=$filter2";
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rubrics_edit.php&gibbonRubricID=$gibbonRubricID&sidebar=false&search=$search&filter2=$filter2";

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Check if gibbonRubricID specified
            if ($gibbonRubricID == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    if ($highestAction == 'Manage Rubrics_viewEditAll') {
                        $data = array('gibbonRubricID' => $gibbonRubricID);
                        $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
                    } elseif ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                        $data = array('gibbonRubricID' => $gibbonRubricID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                        $sql = "SELECT * FROM gibbonRubric JOIN gibbonDepartment ON (gibbonRubric.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonRubric.gibbonDepartmentID IS NULL WHERE gibbonRubricID=:gibbonRubricID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();
                    $gibbonScaleID = $row['gibbonScaleID'];
                    $partialFail = false;

                    //DEAL WITH ROWS
                    $rowTitles = $_POST['rowTitle'] ?? [];
                    $rowColors = $_POST['rowColor'] ?? [];
                    $rowOutcomes = $_POST['gibbonOutcomeID'] ?? [];
                    $rowIDs = $_POST['gibbonRubricRowID'] ?? [];
                    $count = 0;
                    foreach ($rowIDs as $gibbonRubricRowID) {
                        $type = isset($_POST["type$count"])? $_POST["type$count"] : 'Standalone';
                        $backgroundColor = !empty($rowColors[$count]) ? preg_replace('/[^a-fA-F0-9\#]/', '', mb_substr($rowColors[$count], 0, 7)) : null;

                        if ($type == 'Standalone' or $rowOutcomes[$count] == '') {
                            try {
                                $data = array('title' => $rowTitles[$count], 'backgroundColor' => $backgroundColor ?? null, 'gibbonRubricRowID' => $gibbonRubricRowID);
                                $sql = 'UPDATE gibbonRubricRow SET title=:title, backgroundColor=:backgroundColor, gibbonOutcomeID=NULL WHERE gibbonRubricRowID=:gibbonRubricRowID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        } elseif ($type == 'Outcome Based') {
                            try {
                                $data = array('gibbonOutcomeID' => $rowOutcomes[$count], 'backgroundColor' => $backgroundColor ?? null, 'gibbonRubricRowID' => $gibbonRubricRowID);
                                $sql = "UPDATE gibbonRubricRow SET title='', backgroundColor=:backgroundColor, gibbonOutcomeID=:gibbonOutcomeID WHERE gibbonRubricRowID=:gibbonRubricRowID";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        } else {
                            $partialFail = true;
                        }

                        ++$count;
                    }

                    //DEAL WITH COLUMNS
                    //If no grade scale specified
                    if ($row['gibbonScaleID'] == '') {
                        $columnTitles = $_POST['columnTitle'] ?? [];
                        $columnColors = $_POST['columnColor'] ?? [];
                        $columnIDs = $_POST['gibbonRubricColumnID'] ?? [];
                        $columnVisualises = $_POST['columnVisualise'] ?? [];
                        $count = 0;
                        foreach ($columnIDs as $gibbonRubricColumnID) {
                            $visualise = $columnVisualises[$count] ?? 'N';
                            try {
                                $data = array('title' => $columnTitles[$count], 'backgroundColor' => $columnColors[$count] ?? null, 'visualise' => $visualise, 'gibbonRubricColumnID' => $gibbonRubricColumnID);
                                $sql = 'UPDATE gibbonRubricColumn SET title=:title, backgroundColor=:backgroundColor, gibbonScaleGradeID=NULL, visualise=:visualise WHERE gibbonRubricColumnID=:gibbonRubricColumnID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            ++$count;
                        }
                    }
                    //If scale specified
                    else {
                        $columnGrades = $_POST['gibbonScaleGradeID'] ?? [];
                        $columnColors = $_POST['columnColor'] ?? [];
                        $columnIDs = $_POST['gibbonRubricColumnID'] ?? [];
                        $columnVisualises = isset($_POST['columnVisualise'])? $_POST['columnVisualise'] : array();
                        $count = 0;
                        foreach ($columnIDs as $gibbonRubricColumnID) {
                            $visualise = $columnVisualises[$count] ?? 'N';
                            try {
                                $data = array('gibbonScaleGradeID' => $columnGrades[$count], 'backgroundColor' => $columnColors[$count] ?? null, 'visualise' => $visualise, 'gibbonRubricColumnID' => $gibbonRubricColumnID);
                                $sql = "UPDATE gibbonRubricColumn SET title='', backgroundColor=:backgroundColor, gibbonScaleGradeID=:gibbonScaleGradeID, visualise=:visualise WHERE gibbonRubricColumnID=:gibbonRubricColumnID";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            ++$count;
                        }
                    }

                    if ($partialFail) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        $URL = $URLSuccess.'&return=success0#rubricDesign';
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
