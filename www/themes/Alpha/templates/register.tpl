{if $error != ''}
	<div class="alert alert-danger">{$error}</div>
{/if}

{if $showregister != "0"}
	<div class="container">
	<div class="col-sm-6 col-sm-offset-3">
		<div class="well">
			<form class="form-signin" action="register?action=submit{$invite_code_query}" method="post">
				<h2 class="form-signin-heading">Please Register</h2>
				<div class="form-group">
					<label class="sr-only" for="username">Username</label>
					<input type="text" class="form-control" placeholder="Username" autocomplete="off" id="username" name="username" value="{$username}">
					<span class="help-block">Should be at least three characters and start with a letter.</span>
				</div>
				<br>
				<div class="form-group">
					<label class="sr-only" for="firstname">First Name</label>
					<input type="text" class="form-control" placeholder="Firstname" autocomplete="off" id="firstname" name="firstname" value="{$firstname}">
					<span class="help-block">Optional real first name.</span>
				</div>
				<br>
				<div class="form-group">
					<label class="sr-only" for="lastname">First Name</label>
					<input type="text" class="form-control" placeholder="Lastname" autocomplete="off" id="lastname" name="lastname" value="{$lastname}">
					<span class="help-block">Optional real last name.</span>
				</div>
				<br>
				<div class="form-group">
					<label class="sr-only" for="password">Password</label>
					<input type="password" class="form-control" placeholder="Password" id="password" autocomplete="off" name="password" value="{$password}">
					<span class="help-block">Should be at least six characters long.</span>
				</div>
				<div class="form-group">
					<label class="sr-only" for="confirmpassword">Confirm Password</label>
					<input type="password" class="form-control" placeholder="Confirm Password" id="confirmpassword" autocomplete="off" name="confirmpassword" value="{$confirmpassword}">
					<span class="help-block">Please re-enter your password.</span>
				</div>
				<br>
				<div class="form-group">
					<label class="sr-only" for="email">E-mail Address</label>
					<input type="email" class="form-control" placeholder="Email" autocomplete="off" id="email" name="email" value="{$email}">
				</div>
				{$page->smarty->fetch('captcha.tpl')}
				<input type="hidden" class="form-control" id="invitecode" name="invitecode" value="{$invitecode|escape:html_all}">
				<button class="btn btn-default" type="submit" value="Register">Register</button>
			</form>
		</div>
	</div>
	</div>
{/if}