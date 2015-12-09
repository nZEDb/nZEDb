<h1>{$page->title}</h1>

<p>
Use this page to manage movies added to your personal list. If the movie becomes available it will be added to an <a href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss Feed</a> you can use to automatically download. To add more movies use the <a href="{$smarty.const.WWW_TOP}/mymovies">My Movies</a> search feature.
</p>

{if $movies|@count > 0}

<table class="data highlight Sortable" id="browsetable">
	<tr>

		<th></th>
		<th>name</th>
		<th>category</th>
		<th>added</th>
 <th class="mid">options</th>
	</tr>

	{foreach from=$movies item=movie}
		<tr class="{cycle values=",alt"}">

			<td class="mid">

				<div class="movcover">
					<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/movies/{if $movie.cover == 1}{$movie.imdbid}-cover.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$movie.title|escape:"htmlall"}" />
					<div class="movextra">
						<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$movie.imdbid}" title="View IMDB">IMDB</a>
					</div>
				</div>
			</td>


			<td>
				<h2>{$movie.title|escape:"htmlall"} ({$movie.year})</h2>
				{if $movie.tagline != ''}<b>{$movie.tagline}</b><br />{/if}
				{if $movie.plot != ''}{$movie.plot}<br /><br />{/if}
				{if $movie.genre != ''}<b>Genre:</b> {$movie.genre}<br />{/if}
				{if $movie.director != ''}<b>Director:</b> {$movie.director}<br />{/if}
				{if $movie.actors != ''}<b>Starring:</b> {$movie.actors}<br /><br />{/if}
			</td>
			<td class="less">{if $movie.categoryNames != ''}{$movie.categoryNames|escape:"htmlall"}{else}All{/if}</td>
			<td class="less" title="Added on {$movie.createddate}">{$movie.createddate|date_format}</td>
			<td class="mid"><a href="{$smarty.const.WWW_TOP}/mymoviesedit?del={$movie.imdbid}" rel="remove" title="Remove from my movies">Remove</a></td>
		</tr>
	{/foreach}

</table>

{else}

{/if}
