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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v17
 * @since   v17
 */
class GradeScaleGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonScale';
    private static $primaryKey = 'gibbonScaleID';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryGradeScales(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonScaleID', 'name', 'nameShort', 'gibbonScale.usage', 'gibbonScale.active', 'gibbonScale.numeric'
            ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryGradeScaleGrades(QueryCriteria $criteria, $gibbonScaleID)
    {
        $query = $this
            ->newQuery()
            ->from('gibbonScaleGrade')
            ->cols([
                'gibbonScaleGradeID', 'gibbonScaleID', 'value', 'descriptor', 'sequenceNumber', 'isDefault'
            ])
            ->where('gibbonScaleGrade.gibbonScaleID = :gibbonScaleID')
            ->bindValue('gibbonScaleID', $gibbonScaleID);

        return $this->runQuery($query, $criteria);
    }
}
