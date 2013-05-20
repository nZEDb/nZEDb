 
<h1>Register</h1>

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

{if $showregister != "0"}
	<form method="post" action="register?action=submit">

		<table style="width:500px;" class="data">
			<tr><th width="75px;"><label for="username">Username</label>: <em>*</em></th>
				<td>
					<input autocomplete="off" id="username" name="username" value="{$username}" type="text"/>
					<div class="hint">Should be at least three characters and start with a letter.</div>
				</td>
			</tr>
			<tr><th><label for="password">Password</label>: <em>*</em></th>
				<td>
					<input id="password" autocomplete="off" name="password" value="{$password}" type="password"/>
					<input id="invitecode" name="invitecode" type="hidden" value="{$invitecode|escape:html_all}" />
					<div class="hint">Should be at least six characters long.</div>
				</td>
			</tr>
			<tr><th><label for="confirmpassword">Confirm Password</label>: <em>*</em></th><td><input autocomplete="off" id="confirmpassword" name="confirmpassword" value="{$confirmpassword}" type="password"/></td></tr>
			<tr><th><label for="email">Email</label>: <em>*</em></th><td><input autocomplete="off" id="email" name="email" value="{$email}" type="text" /></td></tr>
		</table>

		<table style="width:500px; margin-top:10px;" class="data">
			<tr><th width="75px;"></th><td><input type="submit" value="Register"/><div style="float:right;" class="hint"><em>*</em> Indicates mandatory field.</div></td></tr>
		</table>
		
	</form>
{/if}