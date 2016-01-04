<h1>{$page->title}</h1>
<p>This page lists regex used for getting names for releases from usenet subjects.</p>
<div id="message"></div>

<form name="groupsearch" action="" style="margin-bottom:5px;">
	<label for="group">Search a group:</label>
	<input id="group" type="text" name="group" value="{$group}" size="15" />
	&nbsp;&nbsp;
	<input type="submit" value="Go" />
</form>
{if $regex}

	<div>{$pager}</div>
	<table style="margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th style="width:20px;">id</th>
			<th>group</th>
			<th style="width:25px;">edit</th>
			<th>description</th>
			<th style="width:40px;">delete</th>
			<th>ordinal</th>
			<th>status</th>
			<th>regex</th>
		</tr>
		{foreach from=$regex item=row}
			<tr id="row-{$row.id}" class="{cycle values=",alt"}">
				<td>{$row.id}</td>
				<td>{$row.group_regex}</td>
				<td title="Edit this regex"><a href="{$smarty.const.WWW_TOP}/release_naming_regexes-edit.php?id={$row.id}">Edit</a></td>
				<td>{$row.description|truncate:50:"...":true}</td>
				<td title="Delete this regex"><a href="javascript:ajax_release_naming_regex_delete({$row.id})" onclick="return confirm('Are you sure? This will delete the regex from this list.');" >Delete</a></td>
				<td>{$row.ordinal}</td>
				{if $row.status==1}
					<td style="color:#00CC66">Active</td>
				{else}
					<td style="color:#FF0000">Disabled</td>
				{/if}
				<td title="Edit this regex"><a href="{$smarty.const.WWW_TOP}/release_naming_regexes-edit.php?id={$row.id}">{$row.regex|escape:html|truncate:50:"...":true}</a></td>
			</tr>
		{/foreach}
	</table>
	<div style="margin-top: 15px">{$pager}</div>
{/if}