<?php
if ($page->isPostBack()) {
	//Loop through post vars and escape them. This protects against XSS.
	foreach($_POST as $key => $_) {
		$_POST[$key] = htmlspecialchars($_POST[$key]);
	}
	if (!isset($_POST["username"]) || !isset($_POST["password"])) {
		$page->smarty->assign('error', "Please enter your username and password.");
	} else {
		$page->smarty->assign('username', $_POST["username"]);
		$logging = new Logging(['Settings' => $page->settings]);
		$res = $page->users->getByUsername($_POST["username"]);
		$dis = $page->users->isDisabled($_POST["username"]);

		if (!$res) {
			$res = $page->users->getByEmail($_POST["username"]);
		}

		if ($res) {
			if ($dis) {
				$page->smarty->assign('error', "Your account has been disabled.");
			} else if ($page->users->checkPassword($_POST["password"], $res["password"], $res['id'])) {
				$rememberMe = (isset($_POST['rememberme']) && $_POST['rememberme'] == 'on') ? 1 : 0;
				$page->users->login($res["id"], $_SERVER['REMOTE_ADDR'], $rememberMe);

				if (isset($_POST["redirect"]) && $_POST["redirect"] != "") {
					header("Location: " . $_POST["redirect"]);
				} else {
					header("Location: " . WWW_TOP . $page->settings->home_link);
				}
				die();
			} else {
				$page->smarty->assign('error', "Incorrect username or password.");
				$logging->LogBadPasswd($_POST["username"], $_SERVER['REMOTE_ADDR']);
			}
		} else {
			$page->smarty->assign('error', "Incorrect username or password.");
			$logging->LogBadPasswd($_POST["username"], $_SERVER['REMOTE_ADDR']);
		}
	}
}

$page->smarty->assign('redirect', (isset($_GET['redirect'])) ? $_GET['redirect'] : '');
$page->meta_title = "Login";
$page->meta_keywords = "Login";
$page->meta_description = "Login";
$page->content = $page->smarty->fetch('login.tpl');
$page->render();