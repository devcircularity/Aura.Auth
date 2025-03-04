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

namespace Gibbon\Domain\Messenger;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * MailingList Gateway
 *
 * @version v28
 * @since   v28
 */
class MailingListGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMessengerMailingList';
    private static $primaryKey = 'gibbonMessengerMailingListID';
    private static $searchableColumns = ['gibbonMessengerMailingList.name'];
    
    /**
     * Queries the list of groups for the messenger Manage MailingLists page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryMailingList(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonMessengerMailingList.gibbonMessengerMailingListID', 'name', 'active'
            ]);
    
        return $this->runQuery($query, $criteria);
    }
}
