<h1>{$page->title}</h1>
{if $gamelist}
	{$pager}
	<table style="margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th>ID</th>
			<th>Title</th>
			<th>Genre</th>
			<th>Created</th>
		</tr>
		{foreach from=$gamelist item=game}
			<tr class="{cycle values=",alt"}">
				<td class="less">{$game.id}</td>
				<td><a title="Edit" href="{$smarty.const.WWW_TOP}/game-edit.php?id={$game.id}">{$game.title}</a></td>
				<td>{$game.genretitle}</td>
				<td>{$game.createddate|date_format}</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>No games available.</p>
{/if}
