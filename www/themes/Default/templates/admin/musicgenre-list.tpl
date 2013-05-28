 
<h1>{$page->title}</h1>

<p>
	Disable a music genre to prevent releases in this genre.
</p>

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>id</th>
		<th>title</th>
		<th>disabled</th>
	</tr>
	
	{foreach from=$genrelist item=genre}
	<tr class="{cycle values=",alt"}">
		<td>{$genre.ID}</td>
		<td><a href="{$smarty.const.WWW_TOP}/musicgenre-edit.php?id={$genre.ID}">{$genre.title}</a></td>
		<td>{if $genre.disabled == "1"}Yes{else}No{/if}</td>
	</tr>
	{/foreach}


</table>
