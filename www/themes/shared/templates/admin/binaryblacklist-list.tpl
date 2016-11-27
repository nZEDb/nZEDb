<h1>{$page->title}</h1>
<p>
	Binaries can be prevented from being added to the index at all if they match a regex provided in the blacklist. They can also be included only if they match a regex (whitelist).
	<br>CLICK EDIT OR ON THE BLACKLIST TO ENABLE/DISABLE.
</p>
<div id="message"></div>
<table style="margin-top:10px;" class="data Sortable highlight">
	<tr>
		<th style="width:20px;">id</th>
		<th>group</th>
		<th style="width:25px;">edit</th>
		<th>description</th>
		<th style="width:40px;">delete</th>
		<th>type</th>
		<th>field</th>
		<th>status</th>
		<th>regex</th>
		<th>last activity</th>
	</tr>
	{foreach from=$binlist item=bin}
		<tr id="row-{$bin.id}" class="{cycle values=",alt"}">
			<td>{$bin.id}</td>
			<td>{$bin.groupname|replace:"alt.binaries":"a.b"}</td>
			<td title="Edit this blacklist"><a href="{$smarty.const.WWW_TOP}/binaryblacklist-edit.php?id={$bin.id}">Edit</a></td>
			<td>{$bin.description|truncate:50:"...":true}</td>
			<td title="Delete this blacklist"><a href="javascript:ajax_binaryblacklist_delete({$bin.id})" onclick="return confirm('Are you sure? This will delete the blacklist from this list.');" >Delete</a></td>
			<td>{if $bin.optype==1}Black{else}White{/if}</td>
			<td>{if $bin.msgcol==1}Subject{elseif $bin.msgcol==2}Poster{else}MessageID{/if}</td>
			{if $bin.status==1}
				<td style="color:#00CC66">Active</td>
			{else}
				<td style="color:#FF0000">Disabled</td>
			{/if}
			<td title="Edit this blacklist"><a href="{$smarty.const.WWW_TOP}/binaryblacklist-edit.php?id={$bin.id}">{$bin.regex|escape:html|truncate:50:"...":true}</a></td>
			<td>{$bin.last_activity}</td>
		</tr>
	{/foreach}
</table>
