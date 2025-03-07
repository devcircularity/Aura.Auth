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
 * TextArea
 *
 * @version v14
 * @since   v14
 */
class TextArea extends Input
{
    protected $maxLength;
    protected $autosize = false;

    /**
     * Create a textarea with a default height of 6 rows.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setRows(6);
        parent::__construct($name);
    }

    /**
     * Set the textarea rows attribute to control the height of the input box.
     * @param  int  $count
     * @return self
     */
    public function setRows($count)
    {
        $this->setAttribute('rows', $count);

        return $this;
    }

    /**
     * Set the textarea cols attribute to control the width of the input box.
     * @param  int  $count
     * @return self
     */
    public function setCols($count)
    {
        $this->setAttribute('cols', $count);

        return $this;
    }

    /**
     * Set a max character count for this textarea.
     * @param   string  $value
     * @return  self
     */
    public function maxLength($value = '')
    {
        if (!empty($value)) {
            $this->setAttribute('maxlength', $value);
            $this->addValidation('Validate.Length', 'maximum: '.$value);
        }

        return $this;
    }

    /**
     * Set the default text that appears before any text has been entered.
     * @param   string  $value
     * @return  self
     */
    public function placeholder($value = '')
    {
        $this->setAttribute('placeholder', $value);

        return $this;
    }

    /**
     * Enables the jQuery autosize function for this textarea.
     * @param   string  $value
     * @return  self
     */
    public function autosize($autosize = true)
    {
        $this->autosize = $autosize;
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $text = $this->getAttribute('value');
        $this->setAttribute('value', '');

        return Component::render(TextArea::class, $this->getAttributeArray() + [
            'text'     => htmlentities((string) $text, ENT_QUOTES, 'UTF-8'),
            'autosize' => $this->autosize,
        ]);
    }
}
