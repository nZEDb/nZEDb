<div class="header">
	<h2>Edit > <strong>My Movies</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Cart
		</ol>
	</div>
</div>
<div class="row">
	<div class="col-lg-12 portlets">
		<div class="panel panel-default">
			<div class="panel-body pagination2">
				<p>
					Use this page to manage movies added to your personal list. If the movie becomes available it will
					be added to an <a
							href="{$smarty.const.WWW_TOP}/rss?t=-4&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss
						Feed</a> you can use to automatically download. To add more movies use the <a
							href="{$smarty.const.WWW_TOP}/mymovies">My Movies</a> search feature.
				</p>
				{if $movies|@count > 0}
					<table class="table table-condensed table-striped table-sortable responsive" id="browsetable">
						<tr>
							<th></th>
							<th>name</th>
							<th>added</th>
							<th class="mid">options</th>
						</tr>
						{foreach from=$movies item=movie}
							<tr>
								<td>
									<div>
										<img src="{$smarty.const.WWW_TOP}/covers/movies/{if $movie.cover == 1}{$movie.imdbid}-cover.jpg{else}no-cover.jpg{/if}"
											 width="120" border="0" alt="{$movie.title|escape:"htmlall"}"/>
										<div>
											<a class="label label-default" target="_blank"
											   href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$movie.imdbid}"
											   title="View Imdb">IMDB</a>
										</div>
									</div>
								</td>
								<td>
									<h2>{$movie.title|escape:"htmlall"} ({$movie.year})</h2>
									{if $movie.tagline != ''}<b>{$movie.tagline}</b><br/>{/if}
									{if $movie.plot != ''}{$movie.plot}<br/><br/>{/if}
									{if $movie.genre != ''}<b>Genre:</b>{$movie.genre}<br/>{/if}
									{if $movie.director != ''}<b>Director:</b>{$movie.director}<br/>{/if}
									{if $movie.actors != ''}<b>Starring:</b>{$movie.actors}<br/><br/>{/if}
								</td>
								<td title="Added on {$movie.createddate}">{$movie.createddate|date_format}</td>
								<td><a href="{$smarty.const.WWW_TOP}/mymoviesedit?del={$movie.imdbid}" rel="remove"
									   class="label label-danger" title="Remove from my movies">Remove</a></td>
							</tr>
						{/foreach}
					</table>
				{else}
				{/if}
			</div>
		</div>
	</div>
</div>