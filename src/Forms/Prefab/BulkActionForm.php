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
 * BulkActionForm
 *
 * @version v15
 * @since   v15
 */
class BulkActionForm extends Form
{
    public static function create($id, $action, $method = 'post', $class = 'w-full blank bulkActionForm border-0 bg-transparent p-0')
    {
        global $container;

        $form = $container->get(BulkActionForm::class)
            ->setID($id)
            ->setClass($class)
            ->setAction($action)
            ->setMethod($method);
        
            $form->renderer->setTemplate('components/formBlank.twig.html');

        $form->addConfirmation(__('Are you sure you wish to process this action? It cannot be undone.'));
        $form->addHiddenValue('address', $_GET['q']);

        return $form;
    }

    public function addBulkActionRow($actions = [])
    {
        $row = $this->addRow()->setClass('');
        $col = $row->addElement($this->createBulkActionColumn($actions));

        return $col;
    }

    public function createBulkActionColumn($actions = [])
    {
        $col = $this->getFactory()->createColumn()->addClass('');

        $col->addSelect('action')
            ->fromArray([__('Select action') => $actions])
            ->required()
            ->setClass('relative w-32 sm:w-48 mr-1 flex items-center');

        return $col;
    }
}
