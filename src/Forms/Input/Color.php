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
 * Color
 *
 * @version v21
 * @since   v21
 */
class Color extends Input
{
    /**
     * Create an HTML color input.
     * @param  string  $name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setAttribute('maxlength', 7);
        $this->addValidation(
            'Validate.Format',
            'pattern: /#[0-9a-fA-F]{6}/, failureMessage: "'.__('Must be a valid hex colour').'"'
        );
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return Component::render(Color::class, $this->getAttributeArray() + [
            'color' => !empty($this->getValue()) ? $this->getValue() : '#ffffff'
        ]);
    }
}
