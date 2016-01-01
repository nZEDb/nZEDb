<h1>{$page->title}</h1>
<form action="{$SCRIPT_NAME}?action=submit" method="POST">
	<table class="input">
		<tr>
			<td>Name:</td>
			<td>
				<input type="hidden" name="id" value="{$role.id}" />
				{if $role.id != '' && $role.id < 4}{$role.name}<input type="hidden" name="name" value="{$role.name}" />{else}<input name="name" type="text" value="{$role.name}" /><div class="hint">The name of the role</div>{/if}
			</td>
		</tr>
		<tr>
			<td>Api Requests:</td>
			<td>
				<input name="apirequests" type="text" value="{$role.apirequests}" />
				<div class="hint">Number of api requests allowed per 24 hour period</div>
			</td>
		</tr>
		<tr>
			<td>Download Requests:</td>
			<td>
				<input name="downloadrequests" type="text" value="{$role.downloadrequests}" />
				<div class="hint">Number of downloads allowed per 24 hour period</div>
			</td>
		</tr>
		<tr>
			<td>Invites:</td>
			<td>
				<input name="defaultinvites" type="text" value="{$role.defaultinvites}" />
				<div class="hint">Default number of invites to give users on account creation</div>
			</td>
		</tr>
		<tr>
			<td>Can Preview:</td>
			<td>
				{html_radios id="role" name='canpreview' values=$yesno_ids output=$yesno_names selected=$role.canpreview separator='<br />'}
				<div class="hint">Whether the role can preview screenshots</div>
			</td>
		</tr>
		{if $role.id != ''}
			<tr>
				<td>Is Default Role:</td>
				<td>
					{html_radios id="role" name='isdefault' values=$yesno_ids output=$yesno_names selected=$role.isdefault separator='<br />'}
					<div class="hint">Make this the default role for new users</div>
				</td>
			</tr>
		{/if}
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Save" />
			</td>
		</tr>
	</table>
</form>