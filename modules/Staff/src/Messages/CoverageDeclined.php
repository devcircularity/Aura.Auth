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

namespace Gibbon\Module\Staff\Messages;

use Gibbon\Module\Staff\Message;
use Gibbon\Services\Format;

class CoverageDeclined extends Message
{
    protected $coverage;

    public function __construct($coverage)
    {
        $this->coverage = $coverage;
    }

    public function via() : array
    {
        return $this->coverage['urgent']
            ? ['database', 'mail', 'sms']
            : ['database', 'mail'];
    }

    public function getTitle() : string
    {
        return __('Coverage Unavailable');
    }

    public function getText() : string
    {
        return __("Unfortunately {name} isn't available to cover your {type} absence on {date}.", [
            'date' => Format::dateRangeReadable($this->coverage['dateStart'], $this->coverage['dateEnd']),
            'name' => Format::name($this->coverage['titleCoverage'], $this->coverage['preferredNameCoverage'], $this->coverage['surnameCoverage'], 'Staff', false, true),
            'type' => $this->coverage['type'],
        ]);
    }

    public function getDetails() : array
    {
        return [
            __('Reply') => $this->coverage['notesCoverage'],
        ];
    }

    public function getModule() : string
    {
        return __('Staff');
    }

    public function getAction() : string
    {
        return __('New Coverage Request');
    }

    public function getLink() : string
    {
        return 'index.php?q=/modules/Staff/coverage_request.php&gibbonStaffAbsenceID='.$this->coverage['gibbonStaffAbsenceID'];
    }
}
