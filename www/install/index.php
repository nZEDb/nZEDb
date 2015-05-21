<?php
@session_start();
require_once realpath(__DIR__ . '/../automated.config.php');

use nzedb\Install;

$page_title = "Welcome";

$cfg = new Install();
if ($cfg->isLocked()) {
	$cfg->error = true;
}

$cfg->cacheCheck = is_writable(SMARTY_DIR . 'templates_c');
if ($cfg->cacheCheck === false) {
	$cfg->error = true;
}

if (!$cfg->error) {
	$cfg->setSession();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>
		<?php
		echo $page_title;
		?>
	</title>
	<link href="./templates/install.css" rel="stylesheet" type="text/css" media="screen" />
	<link rel="shortcut icon" type="image/ico" href="../themes_shared/images/favicon.ico"/>
</head>
<body>
<h1 id="logo">
	<img alt="nZEDb" src="../themes_shared/images/logo.png" />
</h1>
<div class="content">
	<h2>Index Usenet. Now.</h2>

	<p>Welcome to nZEDb.</p>
	<p>Before getting started, you need to make sure that the server meets the minimum requirements. You will also need...</p>
	<ol>
		<li>Your database credentials.</li>
		<li>Your news server credentials.</li>
		<li>SSH & root ability on your server (in case you need to install missing packages).</li>
	</ol>
	<br/><br/>
	<p>
		<strong>
			<div style="color: #ff0000">WARNING: </div>
			This software is not practical for use on shared hosting. You should only use this on a server where YOU have the required privileges and knowledge to solve any challenges that might appear.
		</strong>
	</p>
	<div align="center">
		<?php
		if (!$cfg->error) {
			?>
			<form action="step1.php">
				<input type="submit" value="Go to step one: Pre flight check" />
			</form>
		<?php
		} else {
			if (!$cfg->cacheCheck) {
				?>
				<div class="error">
					The template compile dir must be writable.<br />A quick solution is to run:	<br />
					<?php
					echo 'chmod 777 ' . SMARTY_DIR . 'templates_c';
					if (extension_loaded('posix') && strtolower(substr(PHP_OS, 0, 3)) !== 'win') {
						$group = posix_getgrgid(posix_getgid());
						echo
						'<br /><br />Another solution is to run:<br />chown -R YourUnixUserName:' . $group['name'] . ' ' . nZEDb_ROOT .
						'<br />Then give your user access to the group:<br />usermod -a -G ' . $group['name'] . ' YourUnixUserName' .
						'<br />Finally give read/write access to your user/group:<br />chmod -R 774 ' . nZEDb_ROOT;
					}
					?>
				</div>
			<?php
			} else {
				?>
				<div class="error">Installation Locked! If reinstalling, please remove www/install/install.lock.</div>
			<?php
			}
		}
		?>
	</div>

	<div class="footer">
		<p>
			<br />
			nZEDb is released under GPL.
		</p>
	</div>
</div>
</body>
</html>
