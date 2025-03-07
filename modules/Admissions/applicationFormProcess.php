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

use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Processor\FormProcessorFactory;
use Gibbon\Forms\Builder\Storage\ApplicationFormStorage;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$public = !$session->has('username');
$accessID = $_REQUEST['accessID'] ?? '';
$gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
$identifier = $_REQUEST['identifier'] ?? null;
$pageNumber = $_REQUEST['page'] ?? 1;

$URL = Url::fromModuleRoute('Admissions', 'applicationForm')->withQueryParams(['gibbonFormID' => $gibbonFormID, 'page' => $pageNumber, 'identifier' => $identifier, 'accessID' => $accessID]);

if (empty($gibbonFormID) || empty($identifier)) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    if (empty($gibbonFormID) || empty($pageNumber)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    $partialFail = false;

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $account = $admissionsAccountGateway->getAccountByAccessID($accessID);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }
    
    // Setup the form data
    $formBuilder = $container->get(FormBuilder::class)->populate($gibbonFormID, $pageNumber, ['identifier' => $identifier, 'accessID' => $accessID]);
    $formData = $container->get(ApplicationFormStorage::class)->setContext($formBuilder->getFormID(), $formBuilder->getPageID(), 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'], $account['email']);
    $formData->load($identifier);

    // Check the honey pot field, it should always be empty
    if (!empty($_POST[$formBuilder->getDetail('honeyPot')])) {
        header("Location: {$URL->withReturn('warning1')}");
        exit;
    }

    // Acquire data from POST - on error, return to the current page
    $data = $formBuilder->acquire();
    if (!$data) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Save data before validation, so users don't lose data?
    $formData->addData($data);
    $formData->save($identifier);

    // Add configuration data to the form, such as recently created IDs
    $formBuilder->addConfig([
        'mode'           => 'submit',
        'foreignTableID' => $formData->identify($identifier),
        'accessID'       => $accessID,
        'accessToken'    => $account['accessToken'],
        'accountEmail'   => $account['email'],
        'gibbonPersonID' => !$public ? $account['gibbonPersonID'] : '',
        'gibbonFamilyID' => !$public ? $account['gibbonFamilyID'] : '',
    ]);

    // Handle file uploads - on error, flag partial failures
    $uploaded = $formBuilder->upload();
    $partialFail &= !$uploaded;

    // Validate submitted data - on error, return to the current page
    $validated = $formBuilder->validate($data);
    if (!empty($validated)) {
        header("Location: {$URL->withReturn('error3')->withQueryParam('invalid', implode(',', $validated))}");
        exit;
    }

    // Update the admissions account activity
    $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], [
        'timestampActive' => date('Y-m-d H:i:s'),
        'ipAddress'       => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    // Determine how to handle the next page
    $formPageGateway = $container->get(FormPageGateway::class);
    $finalPageNumber = $formPageGateway->getFinalPageNumber($gibbonFormID);
    $nextPage = $formPageGateway->getNextPageByNumber($gibbonFormID, $pageNumber);
    $maxPage = max($nextPage['sequenceNumber'] ?? $pageNumber, $formData->get('maxPage') ?? 1);

    if ($pageNumber >= $finalPageNumber) {
        // Run the form processor on this data
        $formProcessor = $container->get(FormProcessorFactory::class)->getProcessor($formBuilder->getDetail('type'));
        $formProcessor->submitForm($formBuilder, $formData);
        $formData->save($identifier);

        if ($formData->hasResult('redirect')) {
            $URL = Url::fromHandlerRoute($formData->getResult('redirect'))->withQueryParams($formData->getResult('redirectParams', []));
        } else {
            $URL = $URL->withQueryParam('page', $pageNumber+1)->withReturn('success0');
        }

    } elseif ($nextPage) {
        // Save data and proceed to the next page
        $formData->addData(['maxPage' => $maxPage]);
        $formData->save($identifier);

        $URL = $URL->withQueryParam('page', $nextPage['sequenceNumber'])->withReturn($partialFail ? 'warning1' : '');
    }

    header("Location: {$URL}");
}
