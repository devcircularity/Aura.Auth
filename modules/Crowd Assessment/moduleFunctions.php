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

use Gibbon\Services\Format;

function getLessons($guid, $connection2, $and = '')
{
    global $session;

    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    $fields = 'gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDetails, date, gibbonPlannerEntry.gibbonCourseClassID, homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead';
    //Get my classes (student, teacher, classmates)
    $data = array('today1' => $today, 'gibbonPersonID1' => $session->get('gibbonPersonID'), 'now1' => $now, 'gibbonSchoolYearID1' => $session->get('gibbonSchoolYearID'));
    $sql = "(SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today1 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND (role='Teacher' OR role='Student') AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now1 AND gibbonSchoolYearID=:gibbonSchoolYearID1 $and)";

    //Get other classes if teacher
    $dataTeacher = array('gibbonPersonID' => $session->get('gibbonPersonID'));
    $sqlTeacher = "SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID AND type='Teaching'";
    $resultTeacher = $connection2->prepare($sqlTeacher);
    $resultTeacher->execute($dataTeacher);
    if ($resultTeacher->rowCount() == 1) {
        $data['today2'] = $today;
        $data['gibbonSchoolYearID2'] = $session->get('gibbonSchoolYearID');
        $data['now2'] = $now;
        $sql = $sql." UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today2 AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now2 AND gibbonSchoolYearID=:gibbonSchoolYearID2 AND homeworkCrowdAssessOtherTeachersRead='Y' $and)";
    }

    //Get other classes if student
    $dataStudent = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
    $resultStudent = $connection2->prepare($sqlStudent);
    $resultStudent->execute($dataStudent);
    if ($resultStudent->rowCount() == 1) {
        $data['today3'] = $today;
        $data['gibbonSchoolYearID3'] = $session->get('gibbonSchoolYearID');
        $data['now3'] = $now;
        $sql = $sql." UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today3 AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now3 AND gibbonSchoolYearID=:gibbonSchoolYearID3 AND homeworkCrowdAssessOtherStudentsRead='Y' $and)";
    }

    //Get classes if parent
    $dataParent = array('gibbonPersonID' => $session->get('gibbonPersonID'));
    $sqlParent = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
    $resultParent = $connection2->prepare($sqlParent);
    $resultParent->execute($dataParent);

    if ($resultParent->rowCount() > 0) {
        //Get child list for family
        $childCount = 0;
        while ($rowParent = $resultParent->fetch()) {
            $dataChild = array('gibbonFamilyID' => $rowParent['gibbonFamilyID']);
            $sqlChild = "SELECT gibbonPerson.gibbonPersonID, image_240, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName ";
            $resultChild = $connection2->prepare($sqlChild);
            $resultChild->execute($dataChild);
            while ($rowChild = $resultChild->fetch()) {
                //submitters+classmates parents
                $data['today4'.$childCount] = $today;
                $data['gibbonSchoolYearID4'.$childCount] = $session->get('gibbonSchoolYearID');
                $data['now4'.$childCount] = $now;
                $data['gibbonPersonID4'.$childCount] = $rowChild['gibbonPersonID'];
                $sql = $sql." UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today4$childCount AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID4$childCount AND role='Student' AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now4$childCount AND gibbonSchoolYearID=:gibbonSchoolYearID4$childCount AND (homeworkCrowdAssessSubmitterParentsRead='Y' OR homeworkCrowdAssessClassmatesParentsRead='Y') $and)";
                ++$childCount;
            }
        }
        //Other classes
        $data['today5'] = $today;
        $data['gibbonSchoolYearID5'] = $session->get('gibbonSchoolYearID');
        $data['now5'] = $now;
        $sql = $sql." UNION (SELECT $fields FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE homeworkSubmissionDateOpen<=:today5 AND homeworkCrowdAssess='Y' AND ADDTIME(date, '1344:00:00.0')>=:now5 AND gibbonSchoolYearID=:gibbonSchoolYearID5 AND homeworkCrowdAssessOtherParentsRead='Y' $and)";
    }

    return array($data, $sql);
}

function getCARole($guid, $connection2, $gibbonCourseClassID)
{
    global $session;

    $role = '';
    if ($session->get('gibbonRoleIDCurrentCategory') == 'Parent') {
        $role = 'Parent';
        $childInClass = false;

        //Is child of this perosn in this class?
        $count = 0;
        $children = array();

        $dataParent = array('gibbonPersonID' => $session->get('gibbonPersonID'));
        $sqlParent = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
        $resultParent = $connection2->prepare($sqlParent);
        $resultParent->execute($dataParent);

        if ($resultParent->rowCount() > 0) {
            //Get child list for family
            while ($rowParent = $resultParent->fetch()) {

                    $dataChild = array('gibbonFamilyID' => $rowParent['gibbonFamilyID']);
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID, image_240, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                while ($rowChild = $resultChild->fetch()) {

                        $dataInClass = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $rowChild['gibbonPersonID']);
                        $sqlInClass = "SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Student'";
                        $resultInClass = $connection2->prepare($sqlInClass);
                        $resultInClass->execute($dataInClass);
                    if ($resultInClass->rowCount() == 1) {
                        $childInClass = true;
                        $rowInClass = $resultInClass->fetch();
                        $children[$count] = $rowInClass['gibbonPersonID'];
                        ++$count;
                    }
                }
            }
        }
        if ($childInClass == true) {
            $role = 'Parent - Child In Class';
        }
    } else {
        //Check if in staff table as teacher
        $dataTeacher = array('gibbonPersonID' => $session->get('gibbonPersonID'));
        $sqlTeacher = "SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID AND type='Teaching'";
        $resultTeacher = $connection2->prepare($sqlTeacher);
        $resultTeacher->execute($dataTeacher);

        if ($resultTeacher->rowCount() == 1) {
            $role = 'Teacher';
            $dataRole = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlRole = "SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'";
            $resultRole = $connection2->prepare($sqlRole);
            $resultRole->execute($dataRole);
            if ($resultRole->rowCount() >= 1) {
                $role = 'Teacher - In Class';
            }
        }

        //Check if student
        $dataStudent = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
        $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
        $resultStudent = $connection2->prepare($sqlStudent);
        $resultStudent->execute($dataStudent);

        if ($resultStudent->rowCount() == 1) {
            $role = 'Student';
            $dataRole = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlRole = "SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Student'";
            $resultRole = $connection2->prepare($sqlRole);
            $resultRole->execute($dataRole);
            if ($resultRole->rowCount() == 1) {
                $role = 'Student - In Class';
            }
        }
    }

    return $role;
}

function getStudents($guid, $connection2, $role, $gibbonCourseClassID, $homeworkCrowdAssessOtherTeachersRead, $homeworkCrowdAssessOtherParentsRead, $homeworkCrowdAssessSubmitterParentsRead, $homeworkCrowdAssessClassmatesParentsRead, $homeworkCrowdAssessOtherStudentsRead, $homeworkCrowdAssessClassmatesRead, $and = '')
{
    global $session;

    $data = null;
    $sqlList = null;
    //Fetch and display assessible submissions
    $sqlList = '';
    if (($role == 'Teacher' and $homeworkCrowdAssessOtherTeachersRead == 'Y') or ($role == 'Teacher - In Class')) {
        //Get All students in class
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $and ORDER BY surname, preferredName";
    } elseif ($role == 'Parent' and $homeworkCrowdAssessOtherParentsRead == 'Y') {
        //Get all students in class
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $and ORDER BY surname, preferredName";
    } elseif ($role == 'Parent - Child In Class') {
        //Get array of children
        $count = 0;
        $children = array();
        $dataParent = array('gibbonPersonID' => $session->get('gibbonPersonID'));
        $sqlParent = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
        $resultParent = $connection2->prepare($sqlParent);
        $resultParent->execute($dataParent);
        if ($resultParent->rowCount() > 0) {
            //Get child list for family
            $childCount = 0;
            while ($rowParent = $resultParent->fetch()) {

                    $dataChild = array('gibbonFamilyID' => $rowParent['gibbonFamilyID']);
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID, image_240, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                while ($rowChild = $resultChild->fetch()) {
                    $children[$count] = $rowChild['gibbonPersonID'];
                    ++$count;
                }
            }
        }

        if ($homeworkCrowdAssessSubmitterParentsRead == 'Y' and $homeworkCrowdAssessClassmatesParentsRead == 'Y') {
            //Get all students in class
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $and ORDER BY surname, preferredName";
        } elseif ($homeworkCrowdAssessSubmitterParentsRead == 'Y') {
            //Get only parent's children
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sqlListWhere = 'AND (';
            for ($i = 0; $i < $count; ++$i) {
                $data[$children[$i]] = $children[$i];
                $sqlListWhere .= 'gibbonCourseClassPerson.gibbonPersonID=:'.$children[$i].' OR ';
            }
            if ($sqlListWhere == 'AND (') {
                $sqlListWhere = '';
            } else {
                $sqlListWhere = substr($sqlListWhere, 0, -4).')';
            }
            $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $sqlListWhere $and ORDER BY surname, preferredName";
        } elseif ($homeworkCrowdAssessClassmatesParentsRead == 'Y') {
            //Get all children except parent's children
            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
            $sqlListWhere = '';
            for ($i = 0; $i < $count; ++$i) {
                $data[$children[$i]] = $children[$i];
                $sqlListWhere .= ' AND NOT gibbonCourseClassPerson.gibbonPersonID=:'.$children[$i];
            }
            $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $sqlListWhere $and ORDER BY surname, preferredName";
        }
    } elseif (($role == 'Student' and $homeworkCrowdAssessOtherStudentsRead == 'Y') or ($role == 'Student - In Class' and $homeworkCrowdAssessClassmatesRead == 'Y')) {
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
        $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $and ORDER BY surname, preferredName";
    } elseif ($role == 'Student - In Class') {
        $data = array('gibbonCourseClassID' => $gibbonCourseClassID,'gibbonPersonID' => $session->get('gibbonPersonID'));
        $sqlList = "SELECT * FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') $and ORDER BY surname, preferredName";
    }

    return array($data, $sqlList);
}
