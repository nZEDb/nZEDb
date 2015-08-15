<!DOCTYPE html>
<html lang="en">
{if isset($error) && $error != ''}
	<div class="alert alert-danger">{$error}</div>
{/if}
{if isset($notice) && $notice != ''}
	<div class="alert alert-info">{$notice}</div>
{/if}
{if isset($sent) && $sent != ''}
	<div class="alert alert-info">A link to reset your password has been sent to your e-mail account.</div>
{/if}
<head>
	<script type="text/javascript">
		/* <![CDATA[ */
		var WWW_TOP = "{$smarty.const.WWW_TOP}";
		var SERVERROOT = "{$serverroot}";
		var UID = "{if $loggedin=="true"}{$userdata.id}{else}{/if}";
		var RSSTOKEN = "{if $loggedin=="true"}{$userdata.rsstoken}{else}{/if}";
		/* ]]> */
	</script>
	<!--
		===
		This comment should NOT be removed.
		Charisma v2.0.0
		Copyright 2012-2014 Muhammad Usman
		Licensed under the Apache License v2.0
		http://www.apache.org/licenses/LICENSE-2.0
		http://usman.it
		http://twitter.com/halalit_usman
		===
	-->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The styles -->
	<link id="bs-css" href="{$smarty.const.WWW_TOP}/themes/charisma/css/bootstrap-spacelab.min.css" rel="stylesheet">
	<link href="{$smarty.const.WWW_TOP}/themes/charisma/css/charisma-app.css" rel="stylesheet">
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/fullcalendar/dist/fullcalendar.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/fullcalendar/dist/fullcalendar.print.css'
		  rel='stylesheet' media='print'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/chosen/chosen.min.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/colorbox/example3/colorbox.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/responsive-tables/responsive-tables.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/bootstrap-tour/build/css/bootstrap-tour.min.css'
		  rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/jquery.noty.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/noty_theme_default.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/elfinder.min.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/elfinder.theme.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/jquery.iphone.toggle.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/uploadify.css' rel='stylesheet'>
	<link href='{$smarty.const.WWW_TOP}/themes/charisma/css/animate.min.css' rel='stylesheet'>
	<!-- jQuery -->
	<script src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/jquery/jquery.min.js"></script>
	<!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	<!-- The fav icon -->
	<link rel="shortcut icon" href="{$smarty.const.WWW_TOP}/themes/charisma/img/favicon.ico">
</head>
{if $confirmed == '' && $sent == ''}
	<div class="ch-container">
		<div class="row">
			<div class="row">
				<div class="col-md-12 center login-header">
					<h2>Request a password reset</h2>
				</div>
				<!--/span-->
			</div>
			<!--/row-->
			<div class="row">
				<div class="well col-md-5 center login-box">
					<div class="alert alert-info">
						Request a password reset
					</div>
					<p class="login-box-msg">Please enter the email address you used to register and we will send an email to reset your password. If you
						cannot remember your email, or no longer have access to it, please <a href="{$smarty.const.WWW_TOP}/contact-us">contact
							us</a>.</p>
					<form class="form-horizontal" method="post" action="forgottenpassword?action=submit">
						<fieldset>
							<div class="input-group input-group-lg">
								<span class="input-group-addon"><i class="glyphicon glyphicon-envelope red"></i></span>
								<input autocomplete="off" id="email" name="email" value="{$email}" type="email" class="form-control" placeholder="Email"/>
							</div>
							<div class="clearfix"></div>
							<br>
							<p class="center col-md-5">
							<p class="center col-md-5">
								{$page->smarty->fetch('captcha.tpl')}
							</p>
							<button type="submit" class="btn btn-primary">Request Password Reset</button>
						</fieldset>
						<a href="{$serverroot}login" class="text-center">I already have a membership</a>
					</form>
				</div>
				<!--/span-->
			</div>
			<!--/row-->
		</div>
		<!--/fluid-row-->
	</div>
	<!--/.fluid-container-->
{/if}