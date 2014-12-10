<?php

use \nzedb\db\Settings;

if ($page->users->isLoggedIn()) {
	$page->show404();
}

$showregister = 1;

if ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_CLOSED || $page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_API_ONLY) {
	$page->smarty->assign('error', "Registrations are currently disabled.");
	$showregister = 0;
} elseif ($page->settings->getSetting('registerstatus') == Settings::REGISTER_STATUS_INVITE && (!isset($_REQUEST["invitecode"]) || empty($_REQUEST['invitecode']))) {
	$page->smarty->assign('error', "Registrations are currently invite only.");
	$showregister = 0;
}

if ($showregister == 0) {
	$page->smarty->assign('showregister', "0");
} else {
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

	switch ($action) {
		case 'submit':
			$firstName = (isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : '');
			$lastName = (isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : '');
			$username = htmlspecialchars($_POST['username']);
			$password = htmlspecialchars($_POST['password']);
			$confirmpassword = htmlspecialchars($_POST['confirmpassword']);
			$email = htmlspecialchars($_POST['email']);
			$invitecode = htmlspecialchars($_POST["invitecode"]);

			$page->smarty->assign('username', $username);
			$page->smarty->assign('firstname', $firstName);
			$page->smarty->assign('lastname', $lastName);
			$page->smarty->assign('password', $password);
			$page->smarty->assign('confirmpassword', $confirmpassword);
			$page->smarty->assign('email', $email);
			$page->smarty->assign('invitecode', $invitecode);

			// Check uname/email isn't in use, password valid. If all good create new user account and redirect back to home page.
			if ($password != $confirmpassword) {
				$page->smarty->assign('error', "Password Mismatch");
			} else {
				// Get the default user role.
				$userdefault = $page->users->getDefaultRole();

				$ret = $page->users->signUp($username, $firstname, $lastname, $password, $email, $_SERVER['REMOTE_ADDR'], $userdefault['id'], $userdefault['defaultinvites'], $invitecode);
				if ($ret > 0) {
					$page->users->login($ret, $_SERVER['REMOTE_ADDR']);
					header("Location: " . WWW_TOP . "/");
				} else {
					switch ($ret) {
						case Users::ERR_SIGNUP_BADUNAME:
							$page->smarty->assign('error', "Your username must be at least five characters.");
							break;
						case Users::ERR_SIGNUP_BADPASS:
							$page->smarty->assign('error', "Your password must be longer than eight characters.");
							break;
						case Users::ERR_SIGNUP_BADEMAIL:
							$page->smarty->assign('error', "Your email is not a valid format.");
							break;
						case Users::ERR_SIGNUP_UNAMEINUSE:
							$page->smarty->assign('error', "Sorry, the username is already taken.");
							break;
						case Users::ERR_SIGNUP_EMAILINUSE:
							$page->smarty->assign('error', "Sorry, the email is already in use.");
							break;
						case Users::ERR_SIGNUP_BADINVITECODE:
							$page->smarty->assign('error', "Sorry, the invite code is old or has been used.");
							break;
						default:
							$page->smarty->assign('error', "Failed to register.");
							break;
					}
				}
			}
			break;
		case "view": {
				$invitecode = htmlspecialchars($_GET["invitecode"]);
				if (isset($invitecode)) {
					// See if its a valid invite.
					$invite = $page->users->getInvite($invitecode);
					if (!$invite) {
						$page->smarty->assign('error', sprintf("Bad or invite code older than %d days.", Users::DEFAULT_INVITE_EXPIRY_DAYS));
						$page->smarty->assign('showregister', "0");
					} else {
						$page->smarty->assign('invitecode', $invite["guid"]);
					}
				}
				break;
			}
	}
}

$page->meta_title = "Register";
$page->meta_keywords = "register,signup,registration";
$page->meta_description = "Register";

$page->content = $page->smarty->fetch('register.tpl');
$page->render();
