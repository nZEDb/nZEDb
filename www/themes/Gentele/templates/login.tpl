<!DOCTYPE html>
<html>
{if isset($error) && $error != ''}
	<div class="alert alert-danger">{$error}</div>
{/if}
{if isset($notice) && $notice != ''}
	<div class="alert alert-info">{$notice}</div>
{/if}
<head>
	<meta charset="UTF-8">
	<title>{$site->title} | Log in</title>
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
</head>
<body class="login-page">
<div class="login-box">
	<div class="login-logo">
		<a href="{$serverroot}"><b>{$site->title}</b></a>
	</div><!-- /.login-logo -->
	<div class="login-box-body">
		<p class="login-box-msg">Please sign in to access the site</p>
		<form action="login" method="post">
			{if isset($redirect)}
				<input type="hidden" name="redirect" value="{$redirect|escape:"htmlall"}"/>
			{/if}
			<div class="form-group has-feedback">
				<input id="username" name="username" type="text" class="form-control" placeholder="Username"/>
				<span class="glyphicon glyphicon-envelope form-control-feedback"></span>
			</div>
			<div class="form-group has-feedback">
				<input id="password" name="password" type="password" class="form-control" placeholder="Password"/>
				<span class="glyphicon glyphicon-lock form-control-feedback"></span>
			</div>
			<div class="row">
				<div class="col-xs-8">
					<div class="checkbox flat">
						<label>
							<input id="rememberme" {if isset($rememberme) && $rememberme == 1}checked="checked"{/if} name="rememberme" type="checkbox"> Remember Me
						</label>
						<hr>
						<div style="text-align: center;">
							{$page->smarty->fetch('captcha.tpl')}
						</div>
					</div>
				</div><!-- /.col -->
				<div class="col-xs-4">
					<button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
				</div><!-- /.col -->
			</div>
		</form>
		<a href="{$smarty.const.WWW_TOP}forgottenpassword" class="text-center">I forgot my password</a><br>
		<a href="{$smarty.const.WWW_TOP}register" class="text-center">Register a new membership</a>
</body>
</html>
