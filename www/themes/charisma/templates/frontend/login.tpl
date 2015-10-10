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
<body>
<div class="ch-container">
	<div class="row">
		<div class="row">
			<div class="col-md-12 center login-header">
				<h2>Please login</h2>
			</div>
			<!--/span-->
		</div>
		<!--/row-->
		<div class="row">
			<div class="well col-md-5 center login-box">
				<div class="alert alert-info">
					Please login with your Username and Password.
				</div>
				<form class="form-horizontal" method="post" action="login">
					<fieldset>
						<div class="input-group input-group-lg">
							<span class="input-group-addon"><i class="glyphicon glyphicon-user red"></i></span>
							<input id="username" name="username" type="text" class="form-control"
								   placeholder="Username">
						</div>
						<div class="clearfix"></div>
						<br>
						<div class="input-group input-group-lg">
							<span class="input-group-addon"><i class="glyphicon glyphicon-lock red"></i></span>
							<input id="password" name="password" type="password" class="form-control"
								   placeholder="Password">
						</div>
						<div class="clearfix"></div>
						<div class="input-prepend">
							<label class="rememberme" for="rememberme"><input id="rememberme"
																			  {if isset($rememberme) && $rememberme == 1}checked="checked"{/if}
																			  name="rememberme" type="checkbox">
								Remember me</label>
						</div>
						<div class="clearfix"></div>
						<p class="center col-md-5">
						<p class="center col-md-5">
							{$page->smarty->fetch('captcha.tpl')}
						</p>
						<button type="submit" class="btn btn-primary">Login</button>
					</fieldset>
				</form>
				<a href="{$serverroot}forgottenpassword" class="text-center">I forgot my password</a><br>
				<a href="{$serverroot}register" class="text-center">Register a new membership</a>
			</div>
			<!--/span-->
		</div>
		<!--/row-->
		</div>
	<!--/fluid-row-->
</div>
<!--/.fluid-container-->
<!-- external javascript -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- library for cookie management -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.cookie.js"></script>
<!-- calender plugin -->
<script src='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/moment/min/moment.min.js'></script>
<script src='{$smarty.const.WWW_TOP}/themes/charisma/bower_components/fullcalendar/dist/fullcalendar.min.js'></script>
<!-- data table plugin -->
<script src='{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.dataTables.min.js'></script>
<!-- select or dropdown enhancer -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/chosen/chosen.jquery.min.js"></script>
<!-- plugin for gallery image view -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/colorbox/jquery.colorbox-min.js"></script>
<!-- notification plugin -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.noty.js"></script>
<!-- library for making tables responsive -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/responsive-tables/responsive-tables.js"></script>
<!-- tour plugin -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/bower_components/bootstrap-tour/build/js/bootstrap-tour.min.js"></script>
<!-- star rating plugin -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.raty.min.js"></script>
<!-- for iOS style toggle switch -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.iphone.toggle.js"></script>
<!-- autogrowing textarea plugin -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.autogrow-textarea.js"></script>
<!-- multiple file upload plugin -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.uploadify-3.1.min.js"></script>
<!-- history.js for cross-browser state change on ajax -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/jquery.history.js"></script>
<!-- application script for Charisma demo -->
<script src="{$smarty.const.WWW_TOP}/themes/charisma/js/charisma.js"></script>
</body>
</html>