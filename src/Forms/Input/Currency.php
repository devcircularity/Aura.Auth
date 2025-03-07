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

namespace Gibbon\Forms\Input;

use Gibbon\View\Component;

/**
 * Date
 *
 * @version v14
 * @since   v14
 */
class Currency extends Number
{
    protected $decimalPlaces = 2;
    protected $onlyInteger = false;

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        global $session;

        list($currencyName, $currencySymbol) = explode(' ', $session->get('currency'));

        return Component::render(Currency::class, $this->getAttributeArray() + [
            'groupClass'     => $this->getGroupClass(),
            'currencyName'   => $currencyName,
            'currencySymbol' => $currencySymbol,
        ]);
    }
}
