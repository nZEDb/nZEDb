{if $error != ''}
	<div class="alert alert-danger">{$error}</div>
{/if}

{if $confirmed == '' && $sent == ''}

	<div class="container">
		<div class="col-sm-6 col-sm-offset-3">
			<div class="well">
				<form class="form-signin" action="forgottenpassword?action=submit" method="post">
					<h2 class="form-signin-heading">Please Sign In</h2>
					<p>
						Please enter the email address you used to register and we will send an email to reset your password. If you cannot remember your email, or no longer have access to it, please <a href="{$smarty.const.WWW_TOP}/contact-us">contact us</a>.
					</p>
					<div class="form-group">
						<label class="sr-only" for="email">E-mail Address</label>
						<input type="email" class="form-control" placeholder="E-mail Address" id="email" value="{$email}" name="email">
					</div>
					{$page->smarty->fetch('captcha.tpl')}
					<button class="btn btn-success" type="submit" value="Request Password Reset">Request Password Reset</button>
				</form>
			</div>
		</div>
	</div>
	</div>
{elseif $sent != ''}
	<p>
		A password reset request has been sent to your email.
	</p>
{else}
	<p>
		Your password has been reset and sent to you in an email.
	</p>
{/if}