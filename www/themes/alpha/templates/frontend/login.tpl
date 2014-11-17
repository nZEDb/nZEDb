{if $error != ''}
	<div class="alert alert-danger">{$error}</div>
{/if}
<div class="container">
	<div class="col-sm-6 col-sm-offset-3">
		<div class="well">
			<form class="form-signin" action="login" method="post">
				<input type="hidden" name="redirect" value="{$redirect|escape:"htmlall"}">
				<h2 class="form-signin-heading">Please Sign In</h2>
				<div class="form-group">
					<label class="sr-only" for="username">Username</label>
					<input type="text" class="form-control" placeholder="Username or Email" id="username" value="{$username|escape:"htmlall"}" name="username">
				</div>
				<div class="form-group">
					<label class="sr-only" for="password">Password</label>
					<input type="password" class="form-control" placeholder="Password" id="password" name="password">
				</div>
				<div class="checkbox">
					<label>
						<input type="checkbox" id="rememberme" {if $rememberme == 1}checked="checked" {/if}name="rememberme"> Remember me
					</label>
				</div>
				<button class="btn btn-success" type="submit" value="Login">Login</button>
				<a class="text-right" href="{$smarty.const.WWW_TOP}/forgottenpassword"><button class="btn btn-link" type="button">Forgotten your password?</button></a>
			</form>
		</div>
	</div>
</div>
