<?php

use nzedb\utility\Utility;
use nzedb\Captcha;

if ($page->users->isLoggedIn()) {
	header('Location: ' . WWW_TOP . '/');
}

$captcha = new Captcha($page);
$email = $sent = $confirmed = '';
switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case "reset":
		if (!isset($_REQUEST['guid'])) {
			$page->smarty->assign('error', "No reset code provided.");
			break;
		}

		$ret = $page->users->getByPassResetGuid($_REQUEST['guid']);
		if (!$ret) {
			$page->smarty->assign('error', "Bad reset code provided.");
			break;
		} else {
			// Reset the password, inform the user, send out the email.
			$page->users->updatePassResetGuid($ret["id"], '');
			$newPassword = $page->users->generatePassword();
			$page->users->updatePassword($ret["id"], $newPassword);

			Utility::sendEmail($ret["email"], ($page->settings->getSetting('title') . " Password Reset"),
				"Your password has been reset to $newPassword", $page->settings->getSetting('email')
			);

			$confirmed = "true";
			break;
		}

		break;
	case 'submit':
		if ($captcha->getError() === false) {
			$email = $_POST['email'];
			if ($email == '') {
				$page->smarty->assign('error', "Missing Email");
			} else {
				// Check users exists and send an email.
				$ret = $page->users->getByEmail($email);
				if (!$ret) {
					$sent = "true";
					break;
				} else {
					// Generate a forgottenpassword guid, store it in the user table.
					$guid = md5(uniqid());
					$page->users->updatePassResetGuid($ret["id"], $guid);

					// Send the email
					Utility::sendEmail(
						$ret["email"],
						($page->settings->getSetting('title') . " Forgotten Password Request"),
						("Someone has requested a password reset for this email address.<br>To reset the password use <a href=\"" .
							$page->serverurl . "forgottenpassword?action=reset&guid=$guid\">this link</a>\n"),
						$page->settings->getSetting('email')
					);
					$sent = "true";
					break;
				}
			}
		}
		break;
}
$page->smarty->assign([
		'email'     => $email,
		'confirmed' => $confirmed,
		'sent'      => $sent
	]
);
$page->title = "Forgotten Password";
$page->meta_title = "Forgotten Password";
$page->meta_keywords = "forgotten,password,signup,registration";
$page->meta_description = "Forgotten Password";

$page->content = $page->smarty->fetch('forgottenpassword.tpl');
$page->render();
