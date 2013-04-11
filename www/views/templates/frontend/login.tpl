 
<h1>Login</h1>

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<form action="login" method="post">
	<input type="hidden" name="redirect" value="{$redirect|escape:"htmlall"}" />
	<table class="data">
		<tr><th><label for="username">Username<br/> or Email</label>:</th>
			<td>
				<input style="width:150px;" id="username" value="{$username}" name="username" type="text"/>
			</td></tr>
		<tr><th><label for="password">Password</label>:</th>
			<td>
				<input style="width:150px;" id="password" name="password" type="password"/>
			</td></tr>
		<tr><th><label for="rememberme">Remember Me</label>:</th><td><input id="rememberme" {if $rememberme == 1}checked="checked"{/if} name="rememberme" type="checkbox"/></td>
		<tr><th></th><td><input type="submit" value="Login"/></td></tr>
	</table>
</form>
<br/>
<a href="{$smarty.const.WWW_TOP}/forgottenpassword">Forgotten your password?</a>
