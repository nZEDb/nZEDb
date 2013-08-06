<h1>{$page->title}</h1> 

{if $consolelist}
{$pager}

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>ID</th>
		<th>Title</th>
		<th>Platform</th>
		<th>Created</th>
	</tr>
	
	{foreach from=$consolelist item=console}
	<tr class="{cycle values=",alt"}">
		<td class="less">{$console.ID}</td>
		<td><a title="Edit" href="{$smarty.const.WWW_TOP}/console-edit.php?id={$console.ID}">{$console.title}</a></td>
		<td>{$console.platform}</td>
		<td>{$console.createddate|date_format}</td>
	</tr>
	{/foreach}

</table>
{else}
<p>No games available.</p>
{/if}
