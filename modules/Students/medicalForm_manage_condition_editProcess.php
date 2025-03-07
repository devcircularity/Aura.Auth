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

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Data\Validator;
use Gibbon\Domain\System\AlertLevelGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'] ?? '';
$gibbonPersonMedicalConditionID = $_GET['gibbonPersonMedicalConditionID'] ?? '';
$search = $_GET['search'] ?? '';
if ($gibbonPersonMedicalID == '' or $gibbonPersonMedicalConditionID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/medicalForm_manage_condition_edit.php&gibbonPersonMedicalID=$gibbonPersonMedicalID&gibbonPersonMedicalConditionID=$gibbonPersonMedicalConditionID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_condition_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonPersonMedicalConditionID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $medicalGateway = $container->get(MedicalGateway::class);
            $values = $medicalGateway->getMedicalConditionByID($gibbonPersonMedicalConditionID);

            if (empty($values)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Validate Inputs
                $name = $_POST['name'] ?? '';
                $gibbonAlertLevelID = $_POST['gibbonAlertLevelID'] ?? '';
                $triggers = $_POST['triggers'] ?? '';
                $reaction = $_POST['reaction'] ?? '';
                $response = $_POST['response'] ?? '';
                $medication = $_POST['medication'] ?? '';
                if ($_POST['lastEpisode'] == '') {
                    $lastEpisode = null;
                } else {
                    $lastEpisode = !empty($_POST['lastEpisode']) ? Format::dateConvert($_POST['lastEpisode']) : null;
                }
                $lastEpisodeTreatment = $_POST['lastEpisodeTreatment'] ?? '';
                $comment = $_POST['comment'] ?? '';

                // File Upload
                if (!empty($_FILES['attachment']['tmp_name'])) {
                    // Upload the file, return the /uploads relative path
                    $fileUploader = new FileUploader($pdo, $session);
                    $attachment = $fileUploader->uploadFromPost($_FILES['attachment']);

                    if (empty($attachment)) {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                        exit;
                    }
                } else {
                    // Remove the attachment if it has been deleted, otherwise retain the original value
                    $attachment = empty($_POST['attachment']) ? '' : $values['attachment'];
                }

                if ($name == '' or $gibbonAlertLevelID == '') {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonMedicalID' => $gibbonPersonMedicalID, 'name' => $name, 'gibbonAlertLevelID' => $gibbonAlertLevelID, 'triggers' => $triggers, 'reaction' => $reaction, 'response' => $response, 'medication' => $medication, 'lastEpisode' => $lastEpisode, 'lastEpisodeTreatment' => $lastEpisodeTreatment, 'comment' => $comment, 'attachment' => $attachment, 'gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID);
                        $sql = 'UPDATE gibbonPersonMedicalCondition SET gibbonPersonMedicalID=:gibbonPersonMedicalID, name=:name, gibbonAlertLevelID=:gibbonAlertLevelID, triggers=:triggers, reaction=:reaction, response=:response, medication=:medication, lastEpisode=:lastEpisode, lastEpisodeTreatment=:lastEpisodeTreatment, comment=:comment, attachment=:attachment WHERE gibbonPersonMedicalConditionID=:gibbonPersonMedicalConditionID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    /**
                     * @var AlertLevelGateway
                     */
                    $alertLevelGateway = $container->get(AlertLevelGateway::class);
                    $alert = $alertLevelGateway->getByID($gibbonAlertLevelID);

                    // Has the medical condition risk changed?
                    if ($values['gibbonAlertLevelID'] != $gibbonAlertLevelID && ($alert['gibbonAlertLevelID'] == '001' || $alert['gibbonAlertLevelID'] == '002')) {
                        $student = $container->get(StudentGateway::class)->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $values['gibbonPersonID'])->fetch();

                        // Raise a new notification event
                        $event = new NotificationEvent('Students', 'Medical Condition');
                        $event->addScope('gibbonPersonIDStudent', $student['gibbonPersonID']);
                        $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);

                        $event->setNotificationText(__('{name} has a new or updated medical condition ({condition}) with a {risk} risk level.', [
                            'name' => Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true),
                            'condition' => $name,
                            'risk' => $alert['name'],
                        ]));
                        $event->setActionLink('/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'&search=&allStudents=&subpage=Medical');

                        // Send all notifications
                        $sendReport = $event->sendNotifications($pdo, $session);
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
