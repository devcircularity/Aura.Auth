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
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['comment' => 'HTML']);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
$gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['address'])."/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&gibbonPersonID=$gibbonPersonID";

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view_discuss_post.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonPlannerEntryID, gibbonPlannerEntryHomeworkID, and gibbonPersonID specified
    if ($gibbonPlannerEntryID == '' or $gibbonPlannerEntryHomeworkID == '' or $gibbonPersonID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $and = " AND gibbonPlannerEntryID=$gibbonPlannerEntryID";
        $sql = getLessons($guid, $connection2, $and);
        try {
            $result = $connection2->prepare($sql[1]);
            $result->execute($sql[0]);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $row = $result->fetch();

            $comment = $_POST['comment'] ?? '';
            $role = getCARole($guid, $connection2, $row['gibbonCourseClassID']);

            if ($role == '' or empty($comment)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $sqlList = getStudents($guid, $connection2, $role, $row['gibbonCourseClassID'], $row['homeworkCrowdAssessOtherTeachersRead'], $row['homeworkCrowdAssessOtherParentsRead'], $row['homeworkCrowdAssessSubmitterParentsRead'], $row['homeworkCrowdAssessClassmatesParentsRead'], $row['homeworkCrowdAssessOtherStudentsRead'], $row['homeworkCrowdAssessClassmatesRead'], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID");

                if ($sqlList[1] != '') {
                    try {
                        $resultList = $connection2->prepare($sqlList[1]);
                        $resultList->execute($sqlList[0]);
                    } catch (PDOException $e) {
                        $URL .= '&return=erorr2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($resultList->rowCount() != 1) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                    } else {
                        //INSERT
                        $replyTo = !empty($_GET['replyTo']) ? $_GET['replyTo'] : null;


                        try {
                            $data = array('gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'comment' => $comment, 'replyTo' => $replyTo);
                            $sql = 'INSERT INTO gibbonCrowdAssessDiscuss SET gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonCrowdAssessDiscussIDReplyTo=:replyTo';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=erorr2';
                            header("Location: {$URL}");
                            exit();
                        }
                        $hash = '#'.$replyTo;


                        //Work out who we are replying too
                        $replyToID = null;
                        $dataClassGroup = array('gibbonCrowdAssessDiscussID' => $replyTo);
                        $sqlClassGroup = 'SELECT * FROM gibbonCrowdAssessDiscuss WHERE gibbonCrowdAssessDiscussID=:gibbonCrowdAssessDiscussID';
                        $resultClassGroup = $connection2->prepare($sqlClassGroup);
                        $resultClassGroup->execute($dataClassGroup);
                        if ($resultClassGroup->rowCount() == 1) {
                            $rowClassGroup = $resultClassGroup->fetch();
                            $replyToID = $rowClassGroup['gibbonPersonID'];
                        }

                        //Get lesson plan name
                        $dataLesson = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                        $sqlLesson = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                        $resultLesson = $connection2->prepare($sqlLesson);
                        $resultLesson->execute($dataLesson);
                        if ($resultLesson->rowCount() == 1) {
                            $rowLesson = $resultLesson->fetch();
                            $name = $rowLesson['name'];
                        }

                        $homeworkNameSingular = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'homeworkNameSingular');

                        $notificationSender = $container->get(NotificationSender::class);

                        $personName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);

                        //Create notification for homework owner, as long as it is not me.
                        if ($gibbonPersonID != $session->get('gibbonPersonID') and $gibbonPersonID != $replyToID) {
                            $notificationText = __('{person} has commented on your {homeworkName} for lesson plan "{lessonName}".', ['lessonName' => $name, 'homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'person' => $personName]);
                            $notificationSender->addNotification($gibbonPersonID, $notificationText, 'Crowd Assessment', "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&gibbonPersonID=$gibbonPersonID");
                        }

                        //Create notification to person I am replying to
                        if (is_null($replyToID) == false) {
                            $notificationText = __('{person} has replied to a comment on the {homeworkName} for lesson plan "{lessonName}".', ['lessonName' => $name, 'homeworkName' => mb_strtolower(__($homeworkNameSingular)), 'person' => $personName]);
                            $notificationSender->addNotification($replyToID, $notificationText, 'Crowd Assessment', "/index.php?q=/modules/Crowd Assessment/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&gibbonPersonID=$gibbonPersonID");
                        }

                        $notificationSender->sendNotifications();

                        $URL .= "&return=success0$hash";
                        header("Location: {$URL}");
                    }
                }
            }
        }
    }
}
