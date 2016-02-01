<div class="category" style="padding-bottom:20px;">
	{if $error}
		<h2>{$error}</h2>
	{else}
		<h2 class="main-title">
			<a class="see-more" href="{$smarty.const.WWW_TOP}/{$goto}">see more &raquo;</a>
			The <strong>newest releases</strong> for
			<strong>
				<select name="MySelect" id="MySelect"
						onchange="window.location='{$smarty.const.WWW_TOP}/newposterwall?t=' + this.value;">
					{foreach from=$types item=newtype}
						<option {if $type == $newtype}selected="selected"{/if} value="{$newtype}">
							{$newtype}
						</option>
					{/foreach}
				</select>
			</strong>
		</h2>
		<div class="main-wrapper">
			<div class="main-content">
				<!-- library -->
				<div class="library-wrapper">
					{foreach from=$newest item=result}
						<div
								{if $type == 'Console'}
									class="library-console"
								{elseif $type == 'Movies'}
									class="library-show"
								{elseif $type == 'XXX'}
									class="library-show"
								{elseif $type == 'Audio'}
									class="library-music"
								{elseif $type == 'Books'}
									class="library-show"
								{elseif $type == 'PC'}
									class="library-games"
								{elseif $type == 'TV'}
									class="library-show"
								{elseif $type == 'Anime'}
									class="library-show"
								{/if}
								>
							<div class="poster">
								<a class="titleinfo" title="{$result.guid}"
								   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">
									{if $type == 'Console'}
										<img width="130px" height="195px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/console/{$result.consoleinfoid}.jpg"/>
									{elseif $type == 'Movies'}
										<img width="140px" height="205px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/movies/{$result.imdbid}-cover.jpg"/>
									{elseif $type == 'XXX'}
										<img width="140px" height="205px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/xxx/{$result.xxxinfo_id}-cover.jpg"/>
									{elseif $type == 'Audio'}
										<img width="250px" height="250px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/music/{$result.musicinfoid}.jpg"/>
									{elseif $type == 'Books'}
										<img width="140px" height="205px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/book/{$result.bookinfoid}.jpg"/>
									{elseif $type == 'PC'}
										<img width="130px" height="195px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/games/{$result.gamesinfo_id}.jpg"/>
									{elseif $type == 'TV'}
										<img width="130px" height="150px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/tvshows/{$result.videos_id}.jpg"/>
									{elseif $type == 'Anime'}
										<img width="130px" height="195px" alt=""
											 src="{$smarty.const.WWW_TOP}/covers/anime/{$result.anidbid}.jpg"/>
									{/if}
								</a>
							</div>
							<div class="rating-pod" id="guid{$result.guid}">
								<div class="icons">
									<div class="icon icon_nzb"><a class="divlink" title="Download Nzb"
																  href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}"></a>
									</div>
									<div class="icon icon_cart" title="Add to Cart"></div>
									{if $sabintegrated}
										<div class="icon icon_sab" title="Send to my Queue"></div>
									{/if}
								</div>
								<br>
								<hr>
								<div class="icons">
									{if $type == 'Console'}
										<div class="icon icon_ign">
											<a class="divlink" title="Find on IGN"
											   href="{$site->dereferrer_link}http://ign.com/search?q={$result.searchname|escape:"url"}&page=0&count=10&type=object&objectType=game&filter=games&"
											   target="_blank"></a>
										</div>
										<div class="icon icon_gamespot">
											<a class="divlink" title="Find on Gamespot"
											   href="{$site->dereferrer_link}http://www.gamespot.com/search/?q={$result.searchname|escape:"url"}"
											   target="_blank"></a>
										</div>
										<div class="icon icon_predbme">
											<a class="divlink" title="Find on Predb.me"
											   href="{$site->dereferrer_link}http://predb.me/?cats=games&search={$result.searchname|escape:"url"}"
											   target="_blank"></a>
										</div>
									{elseif $type == 'Movies'}
										<div class="icon icon_imdb">
											<a class="divlink" target="_blank" title="View on IMDB"
											   href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbid}/"></a>
										</div>
										<div class="icon icon_trakt">
											<a class="divlink" target="_blank" title="View on Trakt"
											   href="{$site->dereferrer_link}http://trakt.tv/search/imdb/tt{$result.imdbid}/"></a>
										</div>
										{if $cpapi != '' && $cpurl != ''}
											<div class="icon icon_cp">
												<a class="divlink sendtocouch" target="_blank"
												   title="Send to CouchPotato" href="javascript:;"
												   rel="{$cpurl}/api/{$cpapi}/movie.add/?identifier=tt{$result.imdbid}&title={$result.searchname|escape:"url"}"></a>
											</div>
										{/if}
									{elseif $type == 'XXX'}
										<div class="icon icon_ade">
											<a class="divlink" target="_blank" title="View on AdultDVDEmpire"
												href="{$site->dereferrer_link}http://www.adultdvdempire.com/dvd/search?q={$result.title|escape:"url"}/"></a>
										</div>
										<div class="icon icon_popporn">
											<a class="divlink" target="_blank" title="View on PopPorn"
												href="{$site->dereferrer_link}http://dereferer.org/?http://www.popporn.com/results/index.cfm?v=4&g=0&searchtext={$result.title|escape:"url"}/"></a>
										</div>
										<div class="icon icon_iafd">
											<a class="divlink" target="_blank" title="View on Internet Adult Film Database"
												href="{$site->dereferrer_link}http://www.iafd.com/results.asp?searchtype=title&searchstring={$result.title|escape:"url"}/"></a>
										</div>
									{elseif $type == 'PC'}
										<div class="icon icon_ign">
											<a class="divlink" title="Find on IGN"
											   href="{$site->dereferrer_link}http://ign.com/search?q={$result.searchname|escape:"url"}&page=0&count=10&type=object&objectType=game&filter=games&"
											   target="_blank"></a>
										</div>
										<div class="icon icon_gamespot">
											<a class="divlink" title="Find on Gamespot"
											   href="{$site->dereferrer_link}http://www.gamespot.com/search/?q={$result.searchname|escape:"url"}"
											   target="_blank"></a>
										</div>
										<div class="icon icon_predbme">
											<a class="divlink" title="Find on Predb.me"
											   href="{$site->dereferrer_link}http://predb.me/?cats=games&search={$result.searchname|escape:"url"}"
											   target="_blank"></a>
										</div>

									{elseif $type == 'Audio'}
										<div class="icon icon_discogs">
											<a class="divlink" title="Find on Discogs"
											   href="{$site->dereferrer_link}http://www.discogs.com/search/?q={$result.searchname|regex_replace:"/ ?(\(?\d\d\d\d\)?)? ?(MP3|FLAC)/i":""|escape:"url"}"
											   target="_blank"></a>
										</div>
										<div class="icon icon_allmusic">
											<a class="divlink" title="Find on AllMusic"
											   href="{$site->dereferrer_link}http://www.allmusic.com/search/all/{$result.searchname|regex_replace:"/ ?(\(?\d\d\d\d\)?)? ?(MP3|FLAC)/i":""|escape:"url"}"
											   target="_blank"></a>
										</div>
										<div class="icon icon_lastfm">
											<a class="divlink" title="Find on Last.FM"
											   href="{$site->dereferrer_link}http://www.last.fm/search?q={$result.searchname|regex_replace:"/ ?(\(?\d\d\d\d\)?)? ?(MP3|FLAC)/i":""|escape:"url"}&from=ac/"
											   target="_blank"></a>
										</div>
									{elseif $type == 'Books'}
										<div class="icon icon_amazon">
											<a class="divlink" title="View Amazon Page"
											   href="{$site->dereferrer_link}{$result.url}"
											   target="_blank"></a>
										</div>
										<div class="icon icon_goodreads">
											<a class="divlink" title="Find on Goodreads"
											   href="{$site->dereferrer_link}http://www.goodreads.com/search?query={if $result.author != ""}{$result.author|escape:"url"}{"+-+"}{/if}{$result.booktitle|escape:"url"}"
											   target="_blank"></a>
										</div>
										<div class="icon icon_shelfari">
											<a class="divlink" title="Find on Shelfari"
											   href="{$site->dereferrer_link}http://www.shelfari.com/search/books?Keywords={if $result.author != ""}{$result.author|escape:"url"}{"+-+"}{/if}{$result.booktitle|escape:"url"}"
											   target="_blank"></a>
										</div>
									{elseif $type == 'TV'}
										{if $result.tvdb > 0}
											<div class="icon icon_tvdb">
												<a class="divlink" title="View in TVDB"
												   href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$result.tvdb}"
												   target="_blank"></a>
											</div>
										{/if}
										{if $result.tvrage > 0}
											<div class="icon icon_tvrage">
												<a class="divlink" title="View in TvRage"
												   href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$result.tvrage}"
												   target="_blank"></a>
											</div>
										{/if}
										{if $result.tvmaze > 0}
											<div class="icon icon_tvmaze">
												<a class="divlink" title="View in TVMaze"
												   href="{$site->dereferrer_link}http://www.tvmaze.com/shows/{$result.tvmaze}"
												   target="_blank"></a>
											</div>
										{/if}
										{if $result.tmdb > 0}
											<div class="icon icon_tmdb">
												<a class="divlink" title="View in TMDB"
												   href="{$site->dereferrer_link}http://www.themoviedb.org/tv/{$result.tmdb}"
												   target="_blank"></a>
											</div>
										{/if}
										{if $result.trakt > 0}
											<div class="icon icon_trakt">
												<a class="divlink" title="View in Trakt"
												   href="{$site->dereferrer_link}https://trakt.tv/shows/{$result.trakt}"
												   target="_blank"></a>
											</div>
										{/if}
										{if $result.imdb > 0}
											<div class="icon icon_imdb">
												<a class="divlink" title="View in IMDB"
												   href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdb}"
												   target="_blank"></a>
											</div>
										{/if}
									{elseif $type == 'Anime'}
									<div class="icon icon_anidb">
										<a class="divlink" title="View in AniDB"
										   href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$result.anidbid}"
										   target="_blank"></a>
									</div>
									{/if}
								</div>
							</div>
							<a class="plays" href="#"></a>
						</div>
					{/foreach}
				</div>
			</div>
		</div>
	{/if}
</div>
