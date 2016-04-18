<!DOCTYPE html>
<html>

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
	<meta charset="UTF-8">
	<title>{$site->title} | Password reset</title>
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
</head>

{if $confirmed == '' && $sent == ''}
<body class="login-page">
<div class="login-box">
	<div class="login-logo">
		<a href="{$serverrroot}"><b>{$site->title}</b></a>
	</div><!-- /.login-logo -->
	<div class="login-box-body">
		<p class="login-box-msg">Please enter the email address you used to register and we will send an email to reset your password. If you
			cannot remember your email, or no longer have access to it, please <a href="{$smarty.const.WWW_TOP}/contact-us">contact
				us</a>.</p>
		<form action="forgottenpassword?action=submit" method="post">
			<div class="form-group has-feedback">
				<input autocomplete="off" id="email" name="email" value="{$email}" type="email" class="form-control" placeholder="Email"/>
				<span class="glyphicon glyphicon-envelope form-control-feedback"></span>
			</div>
			<div class="row">
				<div class="col-xs-6">
					{$page->smarty->fetch('captcha.tpl')}
				</div><!-- /.col -->
				<hr>
				<div class="col-xs-12">
					<button type="submit" class="btn btn-primary btn-block btn-flat">Request Password Reset</button>

				</div><!-- /.col -->
			</div>
		</form>
</body>
{elseif $sent != ''}
	<p>
		A password reset request has been sent to your email.
	</p>
{else}
	<p>
		Your password has been reset and sent to you in an email.
	</p>
{/if}
</html>
