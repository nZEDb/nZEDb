<?php

use nzedb\utility;

if ($users->isLoggedIn()) {
	$page->show404();
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch($action)
{
	case "reset":
		if (!isset($_REQUEST['guid']))
		{
			$page->smarty->assign('error', "No reset code provided.");
			break;
		}

		$ret = $users->getByPassResetGuid($_REQUEST['guid']);
		if (!$ret)
		{
			$page->smarty->assign('error', "Bad reset code provided.");
			break;
		}
		else
		{
			// Reset the password, inform the user, send out the email.
			$users->updatePassResetGuid($ret["id"], "");
			$newpass = $users->generatePassword();
			$users->updatePassword($ret["id"], $newpass);

			$to = $ret["email"];
			$subject = $page->settings->getSetting('title')." Password Reset";
			$contents = "Your password has been reset to ".$newpass;
			nzedb\utility\sendEmail($to, $subject, $contents, $page->settings->getSetting('email'));

			$page->smarty->assign('confirmed', "true");

			break;
		}

		break;
	case 'submit':

		$page->smarty->assign('email', $_POST['email']);

		if ($_POST['email'] == "") {
			$page->smarty->assign('error', "Missing Email");
		} else {
			// Check users exists and send an email.
			$ret = $users->getByEmail($_POST['email']);
			if (!$ret) {
				$page->smarty->assign('sent', "true");
				break;
			} else {
				// Generate a forgottenpassword guid, store it in the user table.
				$guid = md5(uniqid());
				$users->updatePassResetGuid($ret["id"], $guid);

				// Send the email
				$to = $ret["email"];
				$subject = $page->settings->getSetting('title') . " Forgotten Password Request";
				$contents = "Someone has requested a password reset for this email address.<br>To reset the password use <a href=\"" . $page->serverurl . "forgottenpassword?action=reset&guid=$guid\">this link</a>\n";
				$page->smarty->assign('sent', "true");
				nzedb\utility\sendEmail($to, $subject, $contents, $page->settings->getSetting('email'));
				break;
			}
		}
		break;
}

$page->title = "Forgotten Password";
$page->meta_title = "Forgotten Password";
$page->meta_keywords = "forgotten,password,signup,registration";
$page->meta_description = "Forgotten Password";

$page->content = $page->smarty->fetch('forgottenpassword.tpl');
$page->render();
