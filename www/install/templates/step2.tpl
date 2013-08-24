{if $page->isSuccess()}
	<div align="center">
		<p>The database setup is correct, you may continue to the next step.</p>
		<form action="step3.php"><input type="submit" value="Step three: Setup news server connection" /></form> 
	</div>
{else}

<p>We need some information about your database, please provide the following information</p>
<p>(mysql) Note: If your database already exists, <u>it will be overwritten</u> with this version. If not it will be created. For the database system, use mysql, default port is 3306.</p>
<p>(postgresql) You will need to manually create an empty database, see install.txt for instructions. For the database system, use pgsql, default port is 5432.</p>
<form action="?" method="post">
	<table width="100%" border="0" style="margin-top:10px;" class="data highlight">
		<tr class="">
			<td><label for="host">Database System (pgsql or mysql):</label></td>
			<td><input type="text" name="db_system" id="db_system" value="{$cfg->DB_SYSTEM}" /></td>
		</tr>
		<tr class="alt">
			<td><label for="host">Hostname:</label></td>
			<td><input type="text" name="host" id="host" value="{$cfg->DB_HOST}" /></td>
		</tr>
        <tr class="">
            <td><label for="sql_port">Port Number:</label></td>
            <td><input type="text" name="sql_port" id="sql_port" value="{$cfg->DB_PORT}" /></td>
        </tr>
        <tr class="alt">
            <td><label for="sql_socket">Socket Path(optional):</label></td>
            <td><input type="text" name="sql_socket" id="sql_socket" value="{$cfg->DB_SOCKET}" /></td>
        </tr>
		<tr class="">
			<td><label for="user">Username:</label></td>
			<td><input type="text" name="user" id="user" value="{$cfg->DB_USER}" /></td>
		</tr>
		<tr class="alt">
			<td><label for="pass">Password:</label></td>
			<td><input type="text" name="pass" id="pass" value="{$cfg->DB_PASSWORD}" /></td>
		</tr>
		<tr class="">
			<td><label for="db">Database (to connect to):</label></td>
			<td><input type="text" name="db" id="db" value="{$cfg->DB_NAME}" /></td>
		</tr>
	</table>

	<div style="padding-top:20px; text-align:center;">
			{if $cfg->error}
			<div>
				The following error(s) were encountered:<br />
				{if $cfg->dbConnCheck === false}<span class="error">&bull; Unable to connect to database</span><br />{/if}
				{if $cfg->dbNameCheck === false}<span class="error">&bull; Unable to select database</span><br />{/if}
				{if $cfg->pgNameCheck === false}<span class="error">&bull; Unable to determine if you have created the database for postgresql. See install.txt for instructions.</span><br />{/if}
				{if $cfg->dbCreateCheck === false}<span class="error">&bull; Unable to create database and data. Check permissions of your mysql user.</span><br />{/if}
				<br />
			</div>
			{/if}
			<input type="submit" value="Setup Database" />
	</div>
</form>

{/if}
