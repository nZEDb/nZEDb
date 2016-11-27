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

</head>

<body>

{if $showregister != "0"}
	<div class="ch-container">
		<div class="row">

			<div class="row">
				<div class="col-md-12 center login-header">
					<h2>Register</h2>
				</div>
				<!--/span-->
			</div>
			<!--/row-->

			<div class="row">
				<div class="well col-md-5 center login-box">
					<div class="alert alert-info">
						Register for a new account
					</div>
					<form class="form-horizontal" method="post" action="register?action=submit{$invite_code_query}">
						<fieldset>
							<div class="input-group input-group-lg">
								<span class="input-group-addon"><i class="glyphicon glyphicon-user red"></i></span>
								<input id="username" name="username" type="text" class="form-control"
									   placeholder="Username">
							</div>
							<div class="clearfix"></div>
							<br>
							<div class="input-group input-group-lg">
								<span class="input-group-addon"><i class="glyphicon glyphicon-envelope red"></i></span>
								<input autocomplete="off" id="email" name="email" value="{$email}" type="email" class="form-control" placeholder="Email"/>
							</div>
							<div class="clearfix"></div>
							<br>

							<div class="input-group input-group-lg">
								<span class="input-group-addon"><i class="glyphicon glyphicon-lock red"></i></span>
								<input autocomplete="off" id="password" name="password" value="{$confirmpassword}" type="password" class="form-control" placeholder="Password"/>
							</div>
							<div class="clearfix"></div>

							<div class="input-group input-group-lg">
								<span class="input-group-addon"><i class="glyphicon glyphicon-lock red"></i></span>
								<input autocomplete="off" id="confirmpassword" name="confirmpassword" value="{$confirmpassword}" type="password" class="form-control" placeholder="Retype password"/>
							</div>
							<div class="clearfix"></div>
							<p class="center col-md-5">

							<p class="center col-md-5">
								{$page->smarty->fetch('captcha.tpl')}
							</p>
							<button type="submit" class="btn btn-primary">Register</button>
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
</body>
