<?php
require_once './config.php';


$page = new AdminPage();
$users = new Users();
$id = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

// Get the user roles.
$userroles = $users->getRoles();
$roles = array();
$defaultrole = Users::ROLE_USER;
$defaultinvites = Users::DEFAULT_INVITES;
foreach ($userroles as $r)
{
	$roles[$r['id']] = $r['name'];
	if ($r['isdefault'] == 1)
	{
		$defaultrole = $r['id'];
		$defaultinvites = $r['defaultinvites'];
	}
}

switch($action)
{
	case 'add':
		$user = array();
		$user["role"] = $defaultrole;
		$user["invites"] = $defaultinvites;
		$user["movieview"] = "1";
		$user["musicview"] = "1";
		$user["consoleview"] = "1";
		$user["bookview"] = "1";
		$page->smarty->assign('user', $user);
		break;

	case 'submit':
		if ($_POST["id"] == "")
		{
			$invites = $defaultinvites;
			foreach($userroles as $role)
			{
				if ($role['id'] == $_POST['role'])
					$invites = $role['defaultinvites'];
			}
			$ret = $users->signup($_POST["username"], $_POST["firstname"], $_POST["lastname"], $_POST["password"], $_POST["email"], '', 	$_POST["role"], $invites, "", true);
		}
		else
		{
			$ret = $users->update($_POST["id"], $_POST["username"], $_POST["firstname"], $_POST["lastname"], $_POST["email"], $_POST["grabs"], $_POST["role"], $_POST["invites"], (isset($_POST['movieview']) ? "1" : "0"), (isset($_POST['musicview']) ? "1" : "0"), (isset($_POST['consoleview']) ? "1" : "0"), (isset($_POST['bookview']) ? "1" : "0"));
			if ($_POST['password'] != "")
					$users->updatePassword($_POST["id"], $_POST['password']);
		}

		if ($ret >= 0)
			header("Location:".WWW_TOP."/user-list.php");
		else
		{
			switch ($ret)
			{
				case Users::ERR_SIGNUP_BADUNAME:
					$page->smarty->assign('error', "Bad username. Try a better one.");
					break;

				case Users::ERR_SIGNUP_BADPASS:
					$page->smarty->assign('error', "Bad password. Try a longer one.");
					break;

				case Users::ERR_SIGNUP_BADEMAIL:
					$page->smarty->assign('error', "Bad email.");
					break;

				case Users::ERR_SIGNUP_UNAMEINUSE:
					$page->smarty->assign('error', "Username in use.");
					break;

				case Users::ERR_SIGNUP_EMAILINUSE:
					$page->smarty->assign('error', "Email in use.");
					break;

				default:
					$page->smarty->assign('error', "Unknown save error.");
					break;
			}

			$user = array();
			$user["id"] = $_POST["id"];
			$user["username"] = $_POST["username"];
			$user["firstname"] = $_POST["firstname"];
			$user["lastname"] = $_POST["lastname"];
			$user["email"] = $_POST["email"];
			$user["grabs"] = (isset($_POST["grabs"]) ? $_POST["grabs"] : "0");
			$user["role"] = $_POST["role"];
			$user["invites"] =  (isset($_POST["invites"]) ? $_POST["invites"] : "0");
			$user["movieview"] = $_POST["movieview"];
			$page->smarty->assign('user', $user);
		}
		break;

	case 'view':
	default:
		if (isset($_GET["id"]))
		{
			$page->title = "User Edit";
			$id = $_GET["id"];
			$user = $users->getByID($id);
			$page->smarty->assign('user', $user);
		}
		break;
}

$page->smarty->assign('yesno_ids', array(1,0));
$page->smarty->assign('yesno_names', array( 'Yes', 'No'));

$page->smarty->assign('role_ids', array_keys($roles));
$page->smarty->assign('role_names', $roles);

$page->content = $page->smarty->fetch('user-edit.tpl');
$page->render();

?>
