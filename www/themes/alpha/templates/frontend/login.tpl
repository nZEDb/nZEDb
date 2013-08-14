{if $error != ''}
	<div class="alert alert-danger">{$error}</div>
{/if}
<div class="container">
  <div class="row">
    <div class="col-lg-4 col-lg-offset-4">
<div class="well">
      <form class="form-signin" action="login" method="post">
      	<input type="hidden" name="redirect" value="{$redirect|escape:"htmlall"}">
        <h2 class="form-signin-heading">Please Sign In</h2>
        <div class="form-group">
        <input type="text" class="form-control" placeholder="Username or Email" id="username" value="{$username}" name="username">
        </div>
        <div class="form-group">
        <input type="password" class="form-control" placeholder="Password" id="password" name="password">
        </div>
        <div class="checkbox">
        <label>
          <input type="checkbox" id="rememberme" {if $rememberme == 1}checked="checked" {/if}name="rememberme"> Remember me
        </label>
        </div>
        <button class="btn btn-success" type="submit" value="Login">Login</button>
        <button class="btn btn-link" type="button"><a class="text-right" href="{$smarty.const.WWW_TOP}/forgottenpassword">Forgotten your password?</a></button>
      </form>
    </div>
  </div>
        </div>
</div>

{*
<form action="login" method="post">
	<input type="hidden" name="redirect" value="{$redirect|escape:"htmlall"}" />
	<table class="data">
		<tr><th><label for="username">Username<br/> or Email:</label></th>
			<td>
				<input style="width:150px;" id="username" value="{$username}" name="username" type="text"/>
			</td></tr>
		<tr><th><label for="password">Password:</label></th>
			<td>
				<input style="width:150px;" id="password" name="password" type="password"/>
			</td></tr>
		<tr><th><label for="rememberme">Remember Me:</label></th><td><input id="rememberme" {if $rememberme == 1}checked="checked"{/if} name="rememberme" type="checkbox"/></td>
		<tr><th></th><td><input type="submit" value="Login"/></td></tr>
	</table>
</form>
<br/>
<a href="{$smarty.const.WWW_TOP}/forgottenpassword">Forgotten your password?</a>
*}