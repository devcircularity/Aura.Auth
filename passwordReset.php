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

use Gibbon\Data\PasswordPolicy;
use Gibbon\Forms\Form;
use Gibbon\Data\Validator;

$page->breadcrumbs->add(__('Password Reset'));

$page->return->addReturns([
    'error0'   => __('Email address not set.'),
    'error4'   => __('Your request failed due to incorrect, non-existent or non-unique email address or username.'),
    'error3'   => __('Email failed to send to {email}', ['email' => $_GET['email'] ?? '']),
    'error5'   => __('Your request failed due to non-matching passwords.'),
    'error6'   => __('Your request failed because your password does not meet the minimum requirements for strength.'),
    'error7'   => __('Your request failed because your new password is the same as your current password.'),
    'fail2'    => __('You do not have sufficient privileges to login.'),
    'fail9'    => __('Your primary role does not support the ability to log into the specified year.'),
    'success0' => __('Password reset request successfully initiated, please check your email.'),
]);

$step = 1;
if (isset($_GET['step']) and $_GET['step'] == 2) {
    $step = 2;
}

if ($step == 1) {
    ?>
    <p>
        <?php echo sprintf(__('Enter your %1$s username, or the email address you have listed in the system, and press submit: a unique password reset link will be emailed to you.'), $session->get('systemName')); ?>
    </p>
    <?php
    

    $form = Form::create('action', $session->get('absoluteURL').'/passwordResetProcess.php?step=1');
    $form->addClass('disable-warnings');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('email', __('Username/Email'));
        $row->addTextField('email')->maxLength(255)->required();

    $row = $form->addRow()->addSubmit();

    echo $form->getOutput();
}
else {
    // Sanitize the whole $_GET array
    $validator = $container->get(Validator::class);
    $_GET = $validator->sanitize($_GET);

    //Get URL parameters
    $input = $_GET['input'] ?? null;
    $key = (!empty($_GET['key']) ? $_GET['key'] : null);
    $gibbonPersonResetID = (!empty($_GET['gibbonPersonResetID']) ? $_GET['gibbonPersonResetID'] : null);
    $step = 2;

    $urlParams = compact('input', 'key', 'gibbonPersonResetID', 'step');

    //Verify authenticity of this request and check it is fresh (within 48 hours)

        $data = array('key' => $key, 'gibbonPersonResetID' => $gibbonPersonResetID);
        $sql = "SELECT * FROM gibbonPersonReset WHERE `key`=:key AND gibbonPersonResetID=:gibbonPersonResetID AND (timestamp > DATE_SUB(now(), INTERVAL 2 DAY))";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    if ($result->rowCount() != 1) {
        $page->addError(__('Your reset request is invalid: you may not proceed.'));
    } else {
        echo "<div class='success'>";
        echo __('Your reset request is valid: you may proceed.');
        echo '</div>';

        $form = Form::create('action', $session->get('absoluteURL').'/passwordResetProcess.php?'.http_build_query($urlParams));
        $form->addClass('disable-warnings');

        $form->addHiddenValue('address', $session->get('address'));

        $form->addRow()->addHeading('Reset Password', __('Reset Password'));

        /** @var PasswordPolicy */
        $passwordPolicy = $container->get(PasswordPolicy::class);
        if (($policiesHTML = $passwordPolicy->describeHTML()) !== '') {
            $form->addRow()->addAlert($policiesHTML, 'warning');
        }

        $row = $form->addRow();
            $row->addLabel('passwordNew', __('New Password'));
            $row->addPassword('passwordNew')
                ->addPasswordPolicy($passwordPolicy)
                ->addGeneratePasswordButton($form)
                ->required()
                ->maxLength(30);

        $row = $form->addRow();
            $row->addLabel('passwordConfirm', __('Confirm New Password'));
            $row->addPassword('passwordConfirm')
                ->addConfirmation('passwordNew')
                ->required()
                ->maxLength(30);

        $row = $form->addRow()->addSubmit();

        echo $form->getOutput();
    }
}
