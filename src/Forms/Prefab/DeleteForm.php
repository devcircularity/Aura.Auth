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

namespace Gibbon\Forms\Prefab;

use Gibbon\Forms\Form;

/**
 * DeleteForm
 *
 * @version v15
 * @since   v15
 */
class DeleteForm extends Form
{
    public static function createForm($action, $confirmation = false, $submit = true)
    {
        $form = parent::createBlank('deleteRecord'.substr(md5(random_bytes(10)), 0, 20), $action);
        $form->addHiddenValue('address', $_GET['q']);
        $form->addClass('font-sans text-xs text-gray-700');

        foreach ($_GET as $key => $value) {
            if (is_array($value)) continue;
            $form->addHiddenValue($key, $value);
        }

        $row = $form->addRow();
            $col = $row->addColumn()->addClass('mb-4');
            $col->addContent(__('Are you sure you want to delete this record?'))->wrap('<div class="font-bold text-base mb-2">', '</div>');
            $col->addContent(__('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!'))
                ->wrap('<span class="text-red-700">', '</span>');

        if ($confirmation) {
            $row = $form->addRow();
            $row->addLabel('confirm', sprintf(__('Type %1$s to confirm'), __('DELETE')));
            $row->addTextField('confirm')
                ->required()
                ->addValidation(
                    'Validate.Inclusion',
                    'within: [\''.__('DELETE').'\'], failureMessage: "'.__('Please enter the text exactly as it is displayed to confirm this action.').'", caseSensitive: false')
                ->addValidationOption('onlyOnSubmit: true');
        }

        if ($submit) {
            $form->addRow()->addClass('mt-6')->addConfirmSubmit(__('Delete'))->setColor('red');
        }

        return $form;
    }
}
