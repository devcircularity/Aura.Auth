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
use Gibbon\Forms\Element;
use Gibbon\View\Component;

/**
 * Scanner
 *
 * @version v23
 * @since   v23
 */
class Scanner extends TextField
{       
    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        global $page;

        $page->scripts->add('instascan');
        
        return Component::render(Scanner::class, $this->getAttributeArray() + [
            'unique'       => json_encode($this->unique),
            'autocomplete' => !empty($this->autocomplete)
                ? implode(',', array_map(function ($str) { return sprintf('"%s"', $str); }, $this->autocomplete))
                : '',
        ]);
    }
}
