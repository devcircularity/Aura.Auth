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

namespace Gibbon\Tables\Columns;

use Gibbon\Forms\Input\Radio;

/**
 * RadioColumn
 *
 * @version v25
 * @since   v25
 */
class RadioColumn extends Column
{
    protected $key;
    protected $checked;

    /**
     * Creates a pre-defined column for bulk-action checkboxes.
     */
    public function __construct($id, $key = null)
    {
        parent::__construct($id);
        
        $this->sortable(false)->width('6%');
        $this->context('action');
        $this->key = !empty($key)? $key : $id;

        // $this->modifyCells(function ($data, $cell) {
        //     return $cell->addClass('text-center');
        // });
    }

    public function checked($value = true)
    {
        $this->checked = $value;
        return $this;
    }

    /**
     * Renders a bulk-action checkbox, grabbing the value by key from $data.
     *
     * @param array $data
     * @return string
     */
    public function getOutput(&$data = [], $joinDetails = true)
    {
        $value = isset($data[$this->key])? $data[$this->key] : '';

        return ((new Radio($this->getID()))->wrap('<label for="'.$this->getID().$value.'" class="-m-4 p-4">', '</label>'))
            ->setID($this->getID().$value)
            ->fromArray([$value => ''])
            ->alignCenter()
            ->checked(is_callable($this->checked) ? call_user_func($this->checked, $data) : ($this->checked == $value ? $value : false) )
            ->addClass('mr-2')
            ->getOutput();
    }
}
