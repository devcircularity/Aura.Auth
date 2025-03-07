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

namespace Gibbon\Forms\Builder\Storage;

use Gibbon\Contracts\Services\Session;
use Gibbon\Forms\Builder\AbstractFormStorage;

class FormSessionStorage extends AbstractFormStorage
{
    protected $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }
    
    public function identify(string $identifier) : int
    {
        return intval($identifier);
    }

    public function save(string $identifier) : bool
    {
        $existingData = $this->session->get('form'.$identifier, []);

        $this->session->set('form'.$identifier, array_merge($existingData, $this->getData()));

        return $this->session->exists('form'.$identifier);
    }

    public function load(string $identifier) : bool
    {
        $this->setData($this->session->get('form'.$identifier, []));

        return $this->session->exists('form'.$identifier);
    }

    public function clear(string $identifier)
    {
        $this->session->forget('form'.$identifier);
        $this->setData([]);
    }
}
