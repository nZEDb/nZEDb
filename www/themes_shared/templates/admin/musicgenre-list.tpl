<h1>{$page->title}</h1>
<p>
	Disable a music genre to prevent releases in the genre.
</p>
<div style="position:relative;margin-bottom:5px;">
	<div class="less" id="alldel">
		<div>
			{$pager}
		</div>
		<a href="{$smarty.const.WWW_TOP}/musicgenre-list.php?activeonly=1">Enabled Genres</a> | <a href="{$smarty.const.WWW_TOP}/musicgenre-list.php">All Genres</a>
	</div>
</div>
<table style="margin-top:10px;" class="data Sortable highlight">
	<tr>
		<th>id</th>
		<th>title</th>
		<th>disabled</th>
	</tr>
	{foreach from=$genrelist item=genre}
		<tr class="{cycle values=",alt"}">
			<td>{$genre.id}</td>
			<td><a href="{$smarty.const.WWW_TOP}/musicgenre-edit.php?id={$genre.id}">{$genre.title}</a></td>
			<td>{if $genre.disabled == "1"}Yes{else}No{/if}</td>
		</tr>
	{/foreach}
</table>
<div style="position:relative;margin-top:5px;">
	<div style="position:absolute;">
		{$pager}
	</div>
</div>