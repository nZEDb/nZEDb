<h1>Register</h1>
{if $error != ''}
	<div class="error">{$error}</div>
{/if}
{if $showregister != "0"}
	<form method="post" action="register?action=submit{$invite_code_query}">
		<table style="width:500px;" class="data">
			<tr><th width="75px;"><label for="username">Username: <em>*</em></label></th>
				<td>
					<input autocomplete="off" id="username" name="username" value="{$username}" type="text"/>
					<div class="hint">Should be at least three characters and start with a letter.</div>
				</td>
			</tr>
			<tr><th width="75px;"><label for="firstname">First Name:</label></th>
				<td>
					<input autocomplete="off" id="firstname" name="firstname" value="{$firstname}" type="text"/>
					<div class="hint">Optional real first name.</div>
				</td>
			</tr>
			<tr><th width="75px;"><label for="lastname">Last Name:</label></th>
				<td>
					<input autocomplete="off" id="lastname" name="lastname" value="{$lastname}" type="text"/>
					<div class="hint">Optional real last name.</div>
				</td>
			</tr>
			<tr><th><label for="password">Password: <em>*</em></label></th>
				<td>
					<input id="password" autocomplete="off" name="password" value="{$password}" type="password"/>
					<input id="invitecode" name="invitecode" type="hidden" value="{$invitecode|escape:html_all}" />
					<div class="hint">Should be at least six characters long.</div>
				</td>
			</tr>
			<tr><th><label for="confirmpassword">Confirm Password: <em>*</em></label></th><td><input autocomplete="off" id="confirmpassword" name="confirmpassword" value="{$confirmpassword}" type="password"/></td></tr>
			<tr><th><label for="email">Email: <em>*</em></label></th><td><input autocomplete="off" id="email" name="email" value="{$email}" type="text" /></td></tr>
		</table>
		<table style="width:500px; margin-top:10px;" class="data">
			<tr><th width="75px;"></th><td>{$page->smarty->fetch('captcha.tpl')}<input class="rndbtn" type="submit" value="Register"/><br /><br /><div style="float:left;" class="hint"><em>*</em> Indicates mandatory field.</div></td></tr>
		</table>
	</form>
{/if}