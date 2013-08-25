{if $page->isSuccess()}
	<div align="center">
		<p>The admin user has been setup, you may continue to the next step.</p>
		<form action="step6.php"><input type="submit" value="Step Six: Set NZB File Path" /></form>
	</div>
{else}

<p>You must setup an admin user.</p>
<p>The username must start with a letter followed by a letter or number.</p>
<p>The password must be at least 6 characters long.</p>
<p>Please provide the following information:</p>
<form action="?" method="post">
	<table width="100%" border="0" style="margin-top:10px;" class="data highlight">
		<tr class="alt">
			<td><label for="user">Username:</label></td>
			<td><input autocomplete="off" type="text" name="user" id="user" value="{$cfg->ADMIN_USER}" /></td>
		</tr>
		<tr class="">
			<td><label for="pass">Password:</label></td>
			<td><input autocomplete="off" type="text" name="pass" id="pass" value="{$cfg->ADMIN_PASS}" /></td>
		</tr>
		<tr class="alt">
			<td><label for="email">Email:</label> </td>
			<td><input autocomplete="off" type="text" name="email" id="email" value="{$cfg->ADMIN_EMAIL}" /></td>
		</tr>
	</table>

	<div style="padding-top:20px; text-align:center;">
			{if $cfg->error}
			<div>
				The following error(s) were encountered:<br />
				{if $cfg->ADMIN_USER == ''}<span class="error">&bull; Invalid username</span><br />{/if}
				{if $cfg->ADMIN_PASS == ''}<span class="error">&bull; Invalid password</span><br />{/if}
				{if $cfg->ADMIN_EMAIL == ''}<span class="error">&bull; Invalid email</span><br />{/if}
				<br />
			</div>
			{/if}
			<input type="submit" value="Create Admin User" />
	</div>
</form>

{/if}
