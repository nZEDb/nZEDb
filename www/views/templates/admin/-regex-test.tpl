 
<h1>{$page->title}</h1>

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<form action="{$SCRIPT_NAME}?action=submit" method="POST">

<table class="input">

<tr>
	<td>Group:</td>
	<td>
		<select name="groupname">
		{html_options values=$gid output=$gname selected=$gselected}
		</select>
	</td>
</tr>

<tr>
	<td>Regex:</td>
	<td>
		<input id="regex" name="regex" class="long" value="{$gregex|escape:html}" />
	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="checkbox" name="unreleased"{if $gunreleased == 'on'}checked="checked"{/if} /> Ignore binaries that are released, duplicates, or already matched by a regex
	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="submit" value="Test Regex" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="{$smarty.const.WWW_TOP}/regex-edit.php?action=addtest&regex={$gregex|urlencode}&groupname={$gname.$gselected}">Add Regex</a>
	</td>
</tr>
</table>

</form>

{if $matches}
{$pager}
<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>ID</th>
		<th>name</th>
		<th>req</th>
		<th>parts</th>
		<th>count</th>
		<th>cat</th>
	</tr>
	
	{foreach from=$matches item=match}
	<tr class="{cycle values=",alt"}">
		<td>{$match.bininfo.binID}</td>
		<td>{$match.name|escape:html}<br /><small>{$match.bininfo.binName|escape:html}</small></td>
		<td>{$match.reqid}</td>
		<td>{$match.parts}</td>
		<td>{$match.count}</td>
		<td>{$match.catname}</td>
	</tr>
	{/foreach}

</table>
<br />{$pager}
{/if}