<h2>{$page->title}</h2>

<div class="alert-info">
	<p>
		Use this page to manage movies added to your personal list. If the movie becomes available it will be added to an <a href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss Feed</a> you can use to automatically download. <br>
		To add more movies use the <a href="{$smarty.const.WWW_TOP}/mymovies">My Movies</a> search feature.
	</p>
</div>

{if $movies|@count > 0}
	<table class="data highlight Sortable table table-striped" id="browsetable">
		<tr>

			<th style="padding-top:0px; padding-bottom:0px;"></th>
			<th style="padding-top:0px; padding-bottom:0px;">name</th>
			<th style="padding-top:0px; padding-bottom:0px;">category</th>
			<th style="padding-top:0px; padding-bottom:0px;">added</th>
			<th class="mid" style="padding-top:0px; padding-bottom:0px;">options</th>
		</tr>

		{foreach from=$movies item=movie}
			<tr class="{cycle values=",alt"}">

				<td class="mid" style="width:140px">

					<div class="movcover">
						<img class="shadow img img-polaroid" src="{$smarty.const.WWW_TOP}/covers/movies/{if $movie.cover == 1}{$movie.imdbid}-cover.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$movie.title|escape:"htmlall"}" />
						<div class="movextra">
							<center>
								<a class="rndbtn badge badge-imdb" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$movie.imdbid}" title="View Imdb">Imdb</a>
							</center>
						</div>
					</div>
				</td>


				<td>
					<h4>{$movie.title|escape:"htmlall"} ({$movie.year})</h4>
					{if isset($movie.tagline) && $movie.tagline != ''}<b>{$movie.tagline}</b><br />{/if}
					{if isset($movie.plot) && $movie.plot != ''}{$movie.plot}<br /><br />{/if}
					{if isset($movie.genre) && $movie.genre != ''}<b>Genre:</b> {$movie.genre}<br />{/if}
					{if isset($movie.director) && $movie.director != ''}<b>Director:</b> {$movie.director}<br />{/if}
					{if isset($movie.actors) && $movie.actors != ''}<b>Starring:</b> {$movie.actors}<br /><br />{/if}
				</td>
				<td class="less" style="padding-top:20px;">{if $movie.categoryNames != ''}{$movie.categoryNames|escape:"htmlall"}{else}All{/if}</td>
				<td class="less" style="width:100px; padding-top:20px;" title="Added on {$movie.createddate}">{$movie.createddate|date_format}</td>
				<td class="mid" style="padding-top:20px;"><a class="btn btn-mini btn-danger" href="{$smarty.const.WWW_TOP}/mymoviesedit?del={$movie.imdbid}" rel="remove" title="Remove from my movies">Remove</a></td>
			</tr>
		{/foreach}

	</table>

{else}

{/if}
