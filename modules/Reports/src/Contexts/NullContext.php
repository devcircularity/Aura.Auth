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

namespace Gibbon\Module\Reports\Contexts;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Module\Reports\DataContext;

class NullContext implements DataContext
{

    /**
     * {@inheritDoc}
     */
    public function getFormatter()
    {
        return function () { return ''; };
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifiers(Connection $pdo, string $gibbonReportID, string $gibbonYearGroupID)
    {
        return [];
    }
}
