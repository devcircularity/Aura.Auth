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

namespace Gibbon\Module\Reports;

use Gibbon\Contracts\Database\Connection;

abstract class DataSource
{
    private $factory;
    private $db;

    public function __construct(DataFactory $factory, Connection $db)
    {
        $this->factory = $factory;
        $this->db = $db;
    }

    protected function getFactory()
    {
        return $this->factory;
    }

    protected function db()
    {
        return $this->db;
    }
    
    public function getConfig()
    {
        return [];
    }

    public function getIdentifiers()
    {
        return [];
    }

    abstract public function getSchema();

    abstract public function getData($ids = []);

    
}
