<h1>{$page->title}</h1>
{if $error != ''}
	<div class="error">{$error}</div>
{/if}
<form action="{$SCRIPT_NAME}?action=submit" method="POST">
	<table class="input">
		<tr>
			<td>Name:</td>
			<td>
				<input type="hidden" name="id" value="{$user.id}" />
				<input autocomplete="off" class="long" name="username" type="text" value="{$user.username}" />
			</td>
		</tr>
		<tr>
			<td>First Name:</td>
			<td>
				<input autocomplete="off" class="long" name="firstname" type="text" value="{$user.firstname}" />
			</td>
		</tr>
		<tr>
			<td>Last Name:</td>
			<td>
				<input autocomplete="off" class="long" name="lastname" type="text" value="{$user.lastname}" />
			</td>
		</tr>
		<tr>
			<td>Email:</td>
			<td>
				<input autocomplete="off" class="long" name="email" type="text" value="{$user.email}" />
			</td>
		</tr>
		<tr>
			<td>Password:</td>
			<td>
				<input autocomplete="off" class="long" name="password" type="password" value="" />
				{if $user.id}
					<div class="hint">Only enter a password if you want to change it.</div>
				{/if}
			</td>
		</tr>
		{if $user.id}
			<tr>
				<td>Grabs:</td>
				<td>
					<input class="short" name="grabs" type="text" value="{$user.grabs}" />
				</td>
			</tr>
			<tr>
				<td>Invites:</td>
				<td>
					<input class="short" name="invites" type="text" value="{$user.invites}" />
				</td>
			</tr>
		{/if}
		<tr>
			<td>Movie View:</td>
			<td>
				<input name="movieview" type="checkbox" value="1" {if $user.movieview=="1"}checked="checked"{/if}" />
			</td>
		</tr>
		<tr>
			<td>XXX View:</td>
			<td>
				<input name="xxxview" type="checkbox" value="1" {if $user.xxxview=="1"}checked="checked"{/if}" />
			</td>
		</tr>
		<tr>
			<td>Music View:</td>
			<td>
				<input name="musicview" type="checkbox" value="1" {if $user.musicview=="1"}checked="checked"{/if}" />
			</td>
		</tr>
		<tr>
			<td>Console View:</td>
			<td>
				<input name="consoleview" type="checkbox" value="1" {if $user.consoleview=="1"}checked="checked"{/if}" />
			</td>
		</tr>
		<tr>
			<td>Game View:</td>
			<td>
				<input name="gameview"
					type="checkbox"
					value="1"
					{if $user.gameview=="1"}checked="checked"{/if}" />
			</td>
		</tr>
		<tr>
			<td>Book View:</td>
			<td>
				<input name="bookview" type="checkbox" value="1" {if $user.bookview=="1"}checked="checked"{/if}" />
			</td>
		</tr>
		<tr>
			<td><label for="role">Role:</label></td>
			<td>
				{html_radios id="role" name='role' values=$role_ids output=$role_names selected=$user.role separator='<br />'}
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Save" />
			</td>
		</tr>
	</table>
</form>
