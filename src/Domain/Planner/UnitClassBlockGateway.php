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

namespace Gibbon\Domain\Planner;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * UnitClassBlockGateway
 *
 * @version v21
 * @since   v21
 */
class UnitClassBlockGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonUnitClassBlock';
    private static $primaryKey = 'gibbonUnitClassBlockID';
    private static $searchableColumns = [];
    
    public function selectBlocksByLessonAndClass($gibbonPlannerEntryID, $gibbonCourseClassID)
    {
        $data = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonCourseClassID' => $gibbonCourseClassID];
        $sql = 'SELECT * FROM gibbonUnitClassBlock 
                JOIN gibbonUnitClass ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) 
                WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID 
                AND gibbonCourseClassID=:gibbonCourseClassID 
                ORDER BY sequenceNumber';

        return $this->db()->select($sql, $data);
    }
    

}
