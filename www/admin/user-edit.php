<?php
require_once './config.php';

use nzedb\Users;

$page = new AdminPage();

$user = ['id' => '', 'username' => '', 'firstname' => '', 'lastname' => '', 'email' => '', 'password' => ''];
$error = '';

// Get the user roles.
$userRoles      = $page->users->getRoles();
$roles          = [];
$defaultRole    = Users::ROLE_USER;
$defaultInvites = Users::DEFAULT_INVITES;
foreach ($userRoles as $userRole) {
	$roles[$userRole['id']] = $userRole['name'];
	if ($userRole['isdefault'] == 1) {
		$defaultRole    = $userRole['id'];
		$defaultInvites = $userRole['defaultinvites'];
	}
}

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'add':
		$user                += [
			'role'        => $defaultRole,
			'invites'     => $defaultInvites,
			'movieview'   => '1',
			'xxxview'     => '1',
			'musicview'   => '1',
			'consoleview' => '1',
			'gameview'    => '1',
			'bookview'    => '1'
		];
		break;

	case 'submit':
		if ($_POST["id"] == '') {
			$invites = $defaultInvites;
			foreach ($userRoles as $role) {
				if ($role['id'] == $_POST['role']) {
					$invites = $role['defaultinvites'];
				}
			}
			$ret = $page->users->signUp(
				$_POST["username"], $_POST["firstname"], $_POST["lastname"], $_POST["password"],
				$_POST["email"], '', $_POST["role"], $invites, '', true
			);
		} else {
			$ret = $page->users->update(
				$_POST["id"], $_POST["username"], $_POST["firstname"], $_POST["lastname"], $_POST["email"],
				$_POST["grabs"], $_POST["role"], $_POST["invites"], (isset($_POST['movieview']) ? '1' : '0'),
				(isset($_POST['xxxview']) ? '1' : '0'), (isset($_POST['musicview']) ? '1' : '0'),
				(isset($_POST['consoleview']) ? '1' : '0'), (isset($_POST['gameview']) ? '1' : '0'),
				(isset($_POST['bookview']) ? '1' : '0')
			);
			if ($_POST['password'] != '') {
				$page->users->updatePassword($_POST["id"], $_POST['password']);
			}
		}

		if ($ret >= 0) {
			header("Location:" . WWW_TOP . "/user-list.php");
		} else {
			switch ($ret) {
				case Users::ERR_SIGNUP_BADUNAME:
					$error = "Bad username. Try a better one.";
					break;

				case Users::ERR_SIGNUP_BADPASS:
					$error = "Bad password. Try a longer one.";
					break;

				case Users::ERR_SIGNUP_BADEMAIL:
					$error = "Bad email.";
					break;

				case Users::ERR_SIGNUP_UNAMEINUSE:
					$error = "Username in use.";
					break;

				case Users::ERR_SIGNUP_EMAILINUSE:
					$error = "Email in use.";
					break;

				default:
					$error = "Unknown save error.";
					break;
			}

			$user = [
				'id'        => $_POST["id"],
				'username'  => $_POST["username"],
				'firstname' => $_POST["firstname"],
				'lastname'  => $_POST["lastname"],
				'email'     => $_POST["email"],
				'grabs'     => (isset($_POST["grabs"]) ? $_POST["grabs"] : '0'),
				'role'      => $_POST["role"],
				'invites'   => (isset($_POST["invites"]) ? $_POST["invites"] : '0'),
				'movieview' => $_POST["movieview"]
			];
		}
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "User Edit";
			$user = $page->users->getByID($_GET["id"]);
		}
		break;
}

$page->smarty->assign('error', $error);
$page->smarty->assign('user', $user);
$page->smarty->assign('yesno_ids', [1, 0]);
$page->smarty->assign('yesno_names', ['Yes', 'No']);

$page->smarty->assign('role_ids', array_keys($roles));
$page->smarty->assign('role_names', $roles);

$page->content = $page->smarty->fetch('user-edit.tpl');
$page->render();
