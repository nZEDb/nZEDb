<div class="span6 offset2"
	 style="
padding:40px;
border: 1px solid #e1e1e8;
  -webkit-border-radius: 4px;
     -moz-border-radius: 4px;
          border-radius: 4px;
">
	<h1>{$page->title}</h1>
	<p>
		Please enter the email address you used to register and we will send an email to reset your password. If you cannot remember your email, or no longer have access to it, please <a href="{$smarty.const.WWW_TOP}/contact-us">contact us</a>.
	</p>
	{if isset($confirmed) && $confirmed == '' && isset($sent) && $sent == ''}
	<p>
		<form class="form-horizontal" action="forgottenpassword?action=submit" method="post">
			<table class="data">
			<input type="hidden" name="redirect" value="{$redirect|escape:"htmlall"}" />

			{if isset($error) && $error != ''}
				<div class="alert alert-error">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					  <h4>Error!</h4>
					  {$error}
				</div>
			{/if}
			<input class="input-block-level" autocomplete="off"  type="email" id="username prependedInput" value="{$email}" name="email" placeholder="Email" style="margin-bottom:5px;">
			<div>
				{$page->smarty->fetch('captcha.tpl')}
			</div>
			<br>
				<button type="submit" class="btn btn-success pull-warning">Request password reset</button>
			</table>
		</form>
	</p>
	{elseif $sent != ''}
		<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<h4>Success!</h4>
			A password reset request has been sent to your email.
		</div>
	{else}
		<div class="alert alert-success">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			<h4>Success!</h4>
			Your password has been reset and sent to you in an email.
		</div>
	{/if}
</div>
