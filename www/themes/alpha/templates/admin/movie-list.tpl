<h1>{$page->title}</h1> 

{if $movielist}
{$pager}

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>IMDB ID</th>
		<th>TMDb ID</th>
		<th>Title</th>
		<th>Cover</th>
		<th>Backdrop</th>
		<th>Created</th>
		<th></th>
	</tr>
	
	{foreach from=$movielist item=movie}
	<tr class="{cycle values=",alt"}">
		<td class="less"><a href="http://www.imdb.com/title/tt{$movie.imdbID}" title="View in IMDB">{$movie.imdbID}</a></td>
		<td class="less"><a href="http://www.themoviedb.org/movie/{$movie.tmdbID}" title="View in TMDb">{$movie.tmdbID}</a></td>
		<td><a title="Edit" href="{$smarty.const.WWW_TOP}/movie-edit.php?id={$movie.imdbID}">{$movie.title} ({$movie.year})</a></td>
		<td class="less">{$movie.cover}</td>
		<td class="less">{$movie.backdrop}</td>
		<td class="less">{$movie.createddate|date_format}</td>
		<td class="less"><a title="Update" href="{$smarty.const.WWW_TOP}/movie-add.php?id={$movie.imdbID}&amp;update=1">Update</a></td>
	</tr>
	{/foreach}

</table>
{else}
<p>No Movies available.</p>
{/if}
