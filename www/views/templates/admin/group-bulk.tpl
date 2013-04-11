 
<h1>{$page->title}</h1>

{if $groupmsglist}
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

{else}
<p>Regex of groups to add to the site.</p>

<form action="{$SCRIPT_NAME}?action=submit" method="POST">
<table class="input">

<tr>
	<td width="90">Group List:</td>
	<td>
		<textarea id="groupfilter" name="groupfilter"></textarea>
		<div class="hint">e.g. alt.binaries.cd.image.linux|alt.binaries.warez.linux</div>	
	</td>
</tr>
<tr>
	<td><label for="active">Active</label>:</td>
	<td>
		{html_radios id="active" name='active' values=$yesno_ids output=$yesno_names selected=1 separator='<br />'}
		<div class="hint">Inactive groups will not have headers downloaded for them.</div>		
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