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

namespace Gibbon\Domain\Students;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;

/**
 * @version v16
 * @since   v16
 */
class FirstAidGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonFirstAid';
    private static $primaryKey = 'gibbonFirstAidID';

    private static $searchableColumns = [''];
    
    private static $scrubbableKey = 'gibbonPersonIDPatient';
    private static $scrubbableColumns = ['description' => '','actionTaken' => '','followUp' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFirstAidBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFirstAidID', 'gibbonFirstAid.date', 'gibbonFirstAid.timeIn', 'gibbonFirstAid.timeOut', 'gibbonFirstAid.description', 'gibbonFirstAid.actionTaken', 'gibbonFirstAid.followUp', 'gibbonFirstAid.date', 'patient.surname AS surnamePatient', 'patient.preferredName AS preferredNamePatient', 'gibbonFirstAid.gibbonPersonIDPatient', 'gibbonFormGroup.name as formGroup', 'firstAider.title', 'firstAider.surname AS surnameFirstAider', 'firstAider.preferredName AS preferredNameFirstAider', 'timestamp'
            ])
            ->innerJoin('gibbonPerson AS patient', 'gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS firstAider', 'gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->where('gibbonFirstAid.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'student' => function ($query, $gibbonPersonID) {
                return $query
                    ->where('gibbonFirstAid.gibbonPersonIDPatient = :gibbonPersonID')
                    ->bindValue('gibbonPersonID', $gibbonPersonID);
            },

            'formGroup' => function ($query, $gibbonFormGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonFormGroupID = :gibbonFormGroupID')
                    ->bindValue('gibbonFormGroupID', $gibbonFormGroupID);
            },

            'yearGroup' => function ($query, $gibbonYearGroupID) {
                return $query
                    ->where('gibbonStudentEnrolment.gibbonYearGroupID = :gibbonYearGroupID')
                    ->bindValue('gibbonYearGroupID', $gibbonYearGroupID);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryFirstAidByStudent(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonFirstAidID', 'gibbonFirstAid.date', 'gibbonFirstAid.timeIn', 'gibbonFirstAid.timeOut', 'gibbonFirstAid.description', 'gibbonFirstAid.actionTaken', 'gibbonFirstAid.followUp', 'gibbonFirstAid.date', 'patient.surname AS surnamePatient', 'patient.preferredName AS preferredNamePatient', 'gibbonFirstAid.gibbonPersonIDPatient', 'gibbonFormGroup.name as formGroup', 'firstAider.title', 'firstAider.surname AS surnameFirstAider', 'firstAider.preferredName AS preferredNameFirstAider', 'timestamp'
            ])
            ->innerJoin('gibbonPerson AS patient', 'gibbonFirstAid.gibbonPersonIDPatient=patient.gibbonPersonID')
            ->innerJoin('gibbonStudentEnrolment', 'patient.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID')
            ->innerJoin('gibbonYearGroup', 'gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->leftJoin('gibbonPerson AS firstAider', 'gibbonFirstAid.gibbonPersonIDFirstAider=firstAider.gibbonPersonID')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID = :gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where('gibbonFirstAid.gibbonPersonIDPatient = :gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID);

        return $this->runQuery($query, $criteria);
    }

    public function queryFollowUpByFirstAidID($gibbonFirstAidID)
    {
        $query = $this
            ->newSelect()
            ->from($this->getTableName())
            ->cols([
                'gibbonFirstAidFollowUp.*',
                'gibbonFirstAidFollowUp.followUp as comment',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonPerson.image_240'
            ])
            ->innerJoin('gibbonFirstAidFollowUp', 'gibbonFirstAidFollowUp.gibbonFirstAidID=gibbonFirstAid.gibbonFirstAidID')
            ->innerJoin('gibbonPerson', 'gibbonFirstAidFollowUp.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonFirstAidFollowUp.gibbonFirstAidID=:gibbonFirstAidID')
            ->bindValue('gibbonFirstAidID', $gibbonFirstAidID);

        return $this->runSelect($query);
    }
}
