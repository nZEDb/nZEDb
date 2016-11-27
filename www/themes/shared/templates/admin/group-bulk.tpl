<h1>{$page->title}</h1>
{if $groupmsglist}
	{if $error}
		<p>ERROR: {$groupmsglist}</p>
	{else}
		<table class="data Sortable">
			<tr>
				<th>group</th>
				<th>msg</th>
			</tr>
			{foreach from=$groupmsglist item=group}
				<tr>
					<td>{$group.group}</td>
					<td>{$group.msg}</td>
				</tr>
			{/foreach}
		</table>
		<p>View <a href="group-list.php">all groups</a>.</p>
	{/if}
{else}
	<p>Enter a regex of groups to add to the site. The regex is case insensitive. Use <a href="http://regexpal.com/">regexpal</a> to test it first.
	<br />
	<strong>WARNING: YOU CAN POTENTIALLY ADD THOUSANDS OF GROUPS TO YOUR DATABASE IF YOU DO THIS WRONG.</strong></p>

	<form action="{$SCRIPT_NAME}?action=submit" method="POST">
		<table class="input">
			<tr>
				<td width="90">Group List:</td>
				<td>
					<textarea id="groupfilter" name="groupfilter"></textarea>
					<div class="hint">ie.: (alt\.binaries\.example|dk\.binaer\.unix|alt\.(test|linux|chat))</div>
				</td>
			</tr>
			<tr>
				<td><label for="active">Active:</label></td>
				<td>
					{html_radios id="active" name='active' values=$yesno_ids output=$yesno_names selected=1 separator='<br />'}
					<div class="hint">Inactive groups will not have headers downloaded for them.</div>
				</td>
			</tr>
			<tr>
				<td><label for="backfill">Backfill:</label></td>
				<td>
					{html_radios id="backfill" name='backfill' values=$yesno_ids output=$yesno_names selected=0 separator='<br />'}
					<div class="hint">Inactive groups will not have backfill headers downloaded for them.</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input type="submit" value="Add Groups" />
				</td>
			</tr>
		</table>
	</form>
{/if}