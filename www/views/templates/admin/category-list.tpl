 
<h1>{$page->title}</h1>

<p>
	Make a category inactive to remove it from the menu. This does not prevent binaries being matched into an appropriate category. Disable preview prevents ffmpeg being used for releases in the category.
</p>

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>id</th>
		<th>title</th>
		<th>parent</th>
		<th>active</th>
		<th>disable preview</th>
	</tr>
	
	{foreach from=$categorylist item=category}
	<tr class="{cycle values=",alt"}">
		<td>{$category.ID}</td>
		<td><a href="{$smarty.const.WWW_TOP}/category-edit.php?id={$category.ID}">{$category.title}</a></td>
		<td>
			{if $category.parentID != null}
				{$category.parentName}
			{else}
				n/a
			{/if}
		</td>
		<td>{if $category.status == "1"}Active{elseif $category.status == "2"}Disabled{else}Hidden{/if}</td>
		<td>{if $category.disablepreview == "1"}Yes{else}No{/if}</td>
	</tr>
	{/foreach}


</table>
