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

use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['details' => 'HTML', 'contents*' => 'HTML', 'teachersNotes*' => 'HTML']);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$classCount = $_POST['classCount'] ?? null;
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/units_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID";
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        if (empty($_POST)) {
            $URL .= '&return=error6';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Validate Inputs
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $tags = $_POST['tags'] ?? '';
            $active = $_POST['active'] ?? '';
            $map = $_POST['map'] ?? '';
            $ordering = $_POST['ordering'] ?? '';
            $details = $_POST['details'] ?? '';
            $license = $_POST['license'] ?? null;
            $sharedPublic = $_POST['sharedPublic'] ?? '';


            if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $name == '' or $description == '' or $active == '' or $map == '' or $ordering == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                $courseGateway = $container->get(CourseGateway::class);

                // Check access to specified course
                if ($highestAction == 'Unit Planner_all') {
                    $result = $courseGateway->selectCourseDetailsByCourse($gibbonCourseID);
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $result = $courseGateway->selectCourseDetailsByCourseAndPerson($gibbonCourseID, $session->get('gibbonPersonID'));
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Move attached file, if there is one
                    if (!empty($_FILES['file']['tmp_name'])) {
                        $fileUploader = new Gibbon\FileUploader($pdo, $session);

                        $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                        // Upload the file, return the /uploads relative path
                        $attachment = $fileUploader->uploadFromPost($file, $name);

                        if (empty($attachment)) {
                            $partialFail = true;
                        }
                    } else {
                        $attachment = '';
                    }

                    //Write to database
                    try {
                        $data = array('gibbonCourseID' => $gibbonCourseID, 'name' => $name, 'description' => $description, 'tags' => $tags, 'active' => $active, 'map' => $map, 'ordering' => $ordering, 'license' => $license, 'sharedPublic' => $sharedPublic, 'attachment' => $attachment, 'details' => $details, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID'));
                        $sql = 'INSERT INTO gibbonUnit SET gibbonCourseID=:gibbonCourseID, name=:name, description=:description, tags=:tags, active=:active, map=:map, ordering=:ordering, license=:license, sharedPublic=:sharedPublic, attachment=:attachment, details=:details, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {exit;
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $AI = $connection2->lastInsertID();

                    $partialFail = false;

                    //ADD CLASS RECORDS
                    if ($classCount > 0) {
                        for ($i = 0;$i < $classCount;++$i) {
                            $running = $_POST['running'.$i];
                            if ($running != 'Y' and $running != 'N') {
                                $running = 'N';
                            }

                            try {
                                $dataClass = array('gibbonUnitID' => $AI, 'gibbonCourseClassID' => $_POST['gibbonCourseClassID'.$i], 'running' => $running);
                                $sqlClass = 'INSERT INTO gibbonUnitClass SET gibbonUnitID=:gibbonUnitID, gibbonCourseClassID=:gibbonCourseClassID, running=:running';
                                $resultClass = $connection2->prepare($sqlClass);
                                $resultClass->execute($dataClass);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //ADD BLOCKS
                    $blockCount = ($_POST['blockCount'] ?? 0) - 1;
                    $sequenceNumber = 0;
                    if ($blockCount > 0) {
                        $order = array();
                        if (isset($_POST['order'])) {
                            $order = $_POST['order'] ?? [];
                        }
                        foreach ($order as $i) {
                            $title = '';
                            if ($_POST["title$i"] != "Block $i") {
                                $title = $_POST["title$i"] ?? '';
                            }
                            $type2 = '';
                            if ($_POST["type$i"] != 'type (e.g. discussion, outcome)') {
                                $type2 = $_POST["type$i"] ?? '';
                            }
                            $length = '';
                            if ($_POST["length$i"] != 'length (min)') {
                                $length = $_POST["length$i"] ?? '';
                            }
                            $contents = $_POST["contents$i"] ?? '';
                            $teachersNotes = $_POST["teachersNotes$i"] ?? '';

                            if ($title != '') {
                                try {
                                    $dataBlock = array('gibbonUnitID' => $AI, 'title' => $title, 'type' => $type2, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                    $sqlBlock = 'INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber';
                                    $resultBlock = $connection2->prepare($sqlBlock);
                                    $resultBlock->execute($dataBlock);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                ++$sequenceNumber;
                            }
                        }
                    }

                    //Insert outcomes
                    $count = 0;
                    $outcomeorder = $_POST['outcomeorder'] ?? [];
                    if (count($outcomeorder) > 0) {
                        foreach ($outcomeorder as $outcome) {
                            if ($_POST["outcomegibbonOutcomeID$outcome"] != '') {
                                try {
                                    $dataInsert = array('AI' => $AI, 'gibbonOutcomeID' => $_POST["outcomegibbonOutcomeID$outcome"], 'content' => $_POST["outcomecontents$outcome"], 'count' => $count);
                                    $sqlInsert = 'INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:AI, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count';
                                    $resultInsert = $connection2->prepare($sqlInsert);
                                    $resultInsert->execute($dataInsert);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                            ++$count;
                        }
                    }

                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        $URLSuccess = $URLSuccess."&return=success3&gibbonUnitID=$AI";
                        header("Location: {$URLSuccess}");
                    }
                }
            }
        }
    }
}
