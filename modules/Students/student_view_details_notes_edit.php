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
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    } else {
        $allStudents = $_GET['allStudents'] ?? '';
        $search = $_GET['search'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $category = $_GET['category'] ?? '';

        $enableStudentNotes = $container->get(SettingGateway::class)->getSettingByScope('Students', 'enableStudentNotes');
        if ($enableStudentNotes != 'Y') {
            $page->addError(__('You do not have access to this action.'));
        } else {
            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
            $subpage = $_GET['subpage'] ?? '';
            if ($gibbonPersonID == '' or $subpage == '') {
                $page->addError(__('You have not specified one or more required parameters.'));
            } else {
                
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() != 1) {
                    $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                } else {
                    $student = $result->fetch();

                    //Proceed!
                    $page->breadcrumbs
                        ->add(__('View Student Profiles'), 'student_view.php')
                        ->add(Format::name('', $student['preferredName'], $student['surname'], 'Student'), 'student_view_details.php', ['gibbonPersonID' => $gibbonPersonID, 'subpage' => $subpage, 'allStudents' => $allStudents])
                        ->add(__('Edit Student Note'));

                    //Check if gibbonStudentNoteID specified
                    $gibbonStudentNoteID = $_GET['gibbonStudentNoteID'] ?? '';
                    if ($gibbonStudentNoteID == '') {
                        $page->addError(__('The specified record cannot be found.'));
                    } else {
                        try {
                            if ($highestAction == "View Student Profile_fullEditAllNotes") {
                                $data = array('gibbonStudentNoteID' => $gibbonStudentNoteID);
                                $sql = 'SELECT * FROM gibbonStudentNote WHERE gibbonStudentNoteID=:gibbonStudentNoteID';
                            }
                            else {
                                $data = array('gibbonStudentNoteID' => $gibbonStudentNoteID, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'));
                                $sql = 'SELECT * FROM gibbonStudentNote WHERE gibbonStudentNoteID=:gibbonStudentNoteID AND gibbonPersonIDCreator=:gibbonPersonIDCreator';
                            }
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }

                        if ($result->rowCount() != 1) {
                            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                        } else {
                            //Let's go!
                            $values = $result->fetch();

                            $form = Form::create('notes', $session->get('absoluteURL').'/modules/'.$session->get('module')."/student_view_details_notes_editProcess.php?gibbonPersonID=$gibbonPersonID&search=".$search."&subpage=$subpage&gibbonStudentNoteID=$gibbonStudentNoteID&category=".$category."&allStudents=$allStudents");

                            $form->addHiddenValue('address', $session->get('address'));
                            
                            if ($search != '') {
                                $params = [
                                    "search" => $search,
                                    "gibbonPersonID" => $gibbonPersonID,
                                    "subpage" => $subpage,
                                    "category" => $category,
                                    "allStudents" => $allStudents,
                                ];
                                $form->addHeaderAction('back', __('Back'))
                                    ->setURL('/modules/Students/student_view_details.php')
                                    ->addParams($params);
                            }

                            $row = $form->addRow();
                                $row->addLabel('title', __('Title'));
                                $row->addTextField('title')->required()->maxLength(100);

                            $sql = "SELECT gibbonStudentNoteCategoryID as value, name FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
                            $row = $form->addRow();
                                $row->addLabel('gibbonStudentNoteCategoryID', __('Category'));
                                $row->addSelect('gibbonStudentNoteCategoryID')->fromQuery($pdo, $sql)->required()->placeholder();

                            $row = $form->addRow();
                                $column = $row->addColumn();
                                $column->addLabel('note', __('Note'));
                                $column->addEditor('note', $guid)->required()->setRows(25)->showMedia();

                            $row = $form->addRow();
                                $row->addFooter();
                                $row->addSubmit();

                            $form->loadAllValuesFrom($values);

                            echo $form->getOutput();
                        }
                    }
                }
            }
        }
    }
}
