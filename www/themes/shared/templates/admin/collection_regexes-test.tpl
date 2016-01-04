<h1>{$page->title}</h1>
<p>This page is used for testing regex for grouping usenet collections.<br />Enter the group name to test and a regex. Limit is how many collections to show max on the page, 0 for no limit(slow).</p>

{if $tpg}
	<form name="search" action="" method="post" style="margin-bottom:5px;">
		<label for="group" style="padding-right:1px">Group:</label>
		<input id="group" type="text" name="group" value="{$group|htmlentities}" size="20" /><br />
		<label for="regex" style="padding-right:1px">Regex:</label>
		<input id="regex" type="text" name="regex" value="{$regex|htmlentities}" size="100" /><br/>
		<label for="limit" style="padding-right:7px">Limit:</label>
		<input id="limit" type="text" name="limit" value="{$limit}" size="8" /><br/>
		<input type="submit" value="Test" />
	</form>
	{if $data}

		{foreach from=$data key=hash item=collection}
			<table style="margin-top:10px;" class="data">
				<tr>
					<th>{$hash}<br />Current Files: {count($collection)}</th>
				</tr>
			</table>
			<table style="margin-top:10px;" class="data Sortable highlight">
				<tr>
					<th>name</th>
					<th>current parts</th>
					<th>total parts</th>
					<th>poster</th>
					<th>old hash</th>
				</tr>
				{foreach from=$collection item=row}
					<tr id="row-{$row.new_collection_hash}" class="{cycle values=",alt"}">
						<td>{$row.file_name}</td>
						<td>{$row.file_current_parts}</td>
						<td>{$row.file_total_parts}</td>
						<td>{$row.collection_poster}</td>
						<td>{$row.old_collection_hash}</td>
					</tr>
				{/foreach}
			</table>
		{/foreach}
	{/if}
{else}
	<p>The Table Per Group setting is required to be on to use this page, for performance reasons.</p>
{/if}