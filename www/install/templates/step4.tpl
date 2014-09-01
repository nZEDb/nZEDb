{if $page->isSuccess()}
	<div style="text-align:center">
		<p>The news server setup is correct, you may continue to the next step.</p>
		<form action="step5.php"><input type="submit" value="Step five: Save Settings" /></form>
	</div>
{else}
	<form action="?" method="post">
		<p>If you have a primary news server (NNTP), please provide the following information (make sure the information is correct, some servers do not check if the password is good):</p>
		<table border="0" style="width:100%;margin-top:10px;" class="data highlight">
			<tr class="">
				<td><label for="server">Server:</label></td>
				<td>
					<input type="text" name="server" id="server" value="{$cfg->NNTP_SERVER}" />
					<div class="hint">e.g. eu.news.astraweb.com</div>
				</td>
			</tr>
			<tr class="alt">
				<td><label for="user">Username:</label></td>
				<td><input type="text" name="user" id="user" value="{$cfg->NNTP_USERNAME}" /></td>
			</tr>
			<tr class="">
				<td><label for="pass">Password:</label></td>
				<td>
					<input type="text" name="pass" id="pass" value="{$cfg->NNTP_PASSWORD}" />
				</td>
			</tr>
			<tr class="alt">
				<td><label for="port">Port:</label></td>
				<td>
					<input type="text" name="port" id="port" value="{$cfg->NNTP_PORT}" />
					<div class="hint">e.g. 119 or 443,563 for SSL</div>
				</td>
			</tr>
			<tr>
				<td><label for="ssl">SSL?:</label></td>
				<td>
					<input type="checkbox" name="ssl" id="ssl" value="1" {if $cfg->NNTP_SSLENABLED=="true"}checked="checked"{/if} />
				</td>
			</tr>
			<tr class="alt">
				<td><label for="socket_timeout">Socket Timeout:</label></td>
				<td>
					<input type="text" name="socket_timeout" id="socket_timeout" value="{$cfg->NNTP_SOCKET_TIMEOUT}" />
					<div class="hint">Seconds to time out when we lost connection to usenet. Default is 120 seconds.</div>
				</td>
			</tr>
		</table>
		<p>(optional) If you have an alternate news server (NNTP), please provide the following information (make sure the information is correct, some servers do not check if the password is good):</p>
		<table border="0" style="width:100%;margin-top:10px;" class="data highlight">
			<tr class="">
				<td><label for="servera">Server:</label></td>
				<td>
					<input type="text" name="servera" id="servera" value="{$cfg->NNTP_SERVER_A}" />
					<div class="hint">e.g. eu.news.astraweb.com</div>
				</td>
			</tr>
			<tr class="alt">
				<td><label for="usera">Username:</label></td>
				<td><input type="text" name="usera" id="usera" value="{$cfg->NNTP_USERNAME_A}" /></td>
			</tr>
			<tr class="">
				<td><label for="passa">Password:</label></td>
				<td>
					<input type="text" name="passa" id="passa" value="{$cfg->NNTP_PASSWORD_A}" />
				</td>
			</tr>
			<tr class="alt">
				<td><label for="porta">Port:</label></td>
				<td>
					<input type="text" name="porta" id="porta" value="{$cfg->NNTP_PORT_A}" />
					<div class="hint">e.g. 119 or 443,563 for SSL</div>
				</td>
			</tr>
			<tr>
				<td><label for="ssla">SSL?:</label></td>
				<td>
					<input type="checkbox" name="ssla" id="ssla" value="1" {if $cfg->NNTP_SSLENABLED_A=="true"}checked="checked"{/if} />
				</td>
			</tr>
			<tr class="alt">
				<td><label for="socket_timeouta">Socket Timeout:</label></td>
				<td>
					<input type="text" name="socket_timeouta" id="socket_timeouta" value="{$cfg->NNTP_SOCKET_TIMEOUT_A}" />
					<div class="hint">Seconds to time out when we lost connection to usenet. Default is 120 seconds.</div>
				</td>
			</tr>
		</table>
		<div style="padding-top:20px; text-align:center;">
			{if $cfg->error}
				<div>
					The following error was encountered:<br />
					<span class="error">&bull; {$cfg->error}</span><br /><br /><br />
				</div>
			{/if}
			<input type="submit" value="Test Primary Connection" />
		</div>
	</form>
{/if}