<div class="header">
	<h2>NZB > <strong>Details</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ NZB
		</ol>
	</div>
</div>
<div class="box-body">
	<div class="row">
		<div class="col-xlg-12 portlets">
			<div class="panel panel-default">
				<div class="panel-body pagination2">
					<h1>{$release.searchname|escape:"htmlall"} {if $failed != NULL && $failed > 0}<span class="btn btn-default btn-xs" title="This release has failed to download for some users">
							<i class ="fa fa-thumbs-o-up"></i> {$release.grabs} Grab{if $release.grabs != 1}s{/if} / <i class ="fa fa-thumbs-o-down"></i> {$failed} Failed Download{if $failed != 1}s{/if}</span>{/if}</h1>
					{if isset($isadmin)}
						<a class="label label-warning"
						   href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$release.id}&amp;from={$smarty.server.REQUEST_URI}"
						   title="Edit release">Edit</a>
						<a class="label label-danger"
						   href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$release.id}&amp;from={$smarty.server.HTTP_REFERER}"
						   title="Delete release">Delete</a>
					{/if}
					{if $movie && $release.videos_id <= 0}
						<a class="label label-default" target="_blank"
						   href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$release.imdbid}/"
						   title="View at IMDB">IMDB</a>
						<a target="_blank"
						   href="{$site->dereferrer_link}http://trakt.tv/search/imdb/tt{$release.imdbid}/"
						   name="trakt{$release.imdbid}" title="View Trakt page"
						   class="label label-default" rel="trakt">TRAKT</a>
						{if $movie.tmdbid != ''}
							<a class="label label-default" target="_blank"
							   href="{$site->dereferrer_link}http://www.themoviedb.org/movie/{$movie.tmdbid}"
							   title="View at TMDb">TMDb</a>
						{/if}
					{/if}
					{if $anidb && $release.anidbid > 0}
						<a class="label label-default" href="{$serverroot}anime/{$release.anidbid}"
						   title="View all releases from this anime">View all episodes</a>
						<a class="label label-default"
						   href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$anidb.anidbid}"
						   title="View at AniDB" target="_blank">AniDB</a>
						<a class="label label-default"
						   href="{$smarty.const.WWW_TOP}/rss?anidb={$release.anidbid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Anime
							RSS Feed</a>
					{/if}
					{if $show && $release.videos_id > 0}
						<a href="{$smarty.const.WWW_TOP}/myshows/add/{$release.videos_id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
						   class="label label-success">Add to My Shows</a>
						<a class="label label-default" href="{$serverroot}series/{$release.videos_id}"
						   title="View all releases for this series">View all episodes</a>
							{if $show.tvdb > 0}
							<a class="label label-default" target="_blank"
							   href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$show.tvdb}"
							   title="View at TheTVDB">TheTVDB</a>
							{/if}
							{if $show.tvmaze > 0}
							<a class="label label-default" target="_blank"
							   href="{$site->dereferrer_link}http://tvmaze.com/shows/{$show.tvmaze}"
							   title="View at TVMaze">TVMaze</a>
								{/if}
							{if $show.trakt > 0}
							<a class="label label-default" target="_blank"
							   href="{$site->dereferrer_link}http://www.trakt.tv/shows/{$show.trakt}"
							   title="View at TraktTv">Trakt</a>
								{/if}
							{if $show.tvrage > 0}
							<a class="label label-default" target="_blank"
							   href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$show.tvrage}"
							   title="View at TV Rage">TV Rage</a>
								{/if}
							{if $show.tmdb > 0}
							<a class="label label-default" target="_blank"
							   href="{$site->dereferrer_link}https://www.themoviedb.org/tv/{$show.tmdb}"
							   title="View at TheMovieDB">TMDB</a>
						{/if}
					{/if}
					{if $con && $con.url != ""}<a href="{$site->dereferrer_link}{$con.url}/"
												  class="label label-default" target="_blank">Amazon</a>{/if}
					{if $boo && $boo.url != ""}<a href="{$site->dereferrer_link}{$boo.url}/"
													class="label label-default" target="_blank">Amazon</a>{/if}
					{if $music && $music.url != ""}<a href="{$site->dereferrer_link}{$music.url}/"
													  class="label label-default" target="_blank">
							Amazon</a>{/if}
					{if $xxx}
						{if $xxx.classused === "ade"}<a class="label label-default" target="_blank"
														href="{$site->dereferrer_link}{$xxx.directurl}"
														title="View at Adult DVD Empire">ADE</a>
						{elseif $xxx.classused === "pop"}<a class="label label-default" target="_blank"
															href="{$site->dereferrer_link}{$xxx.directurl}"
															title="View at Popporn">PopPorn</a>
						{elseif $xxx.classused === "aebn"}<a class="label label-default" target="_blank"
															 href="{$site->dereferrer_link}{$xxx.directurl}"
															 title="View at Adult Entertainment Broadcast Network">
								AEBN</a>
						{elseif $xxx.classused === "hm"}<a class="label label-default" target="_blank"
														   href="{$site->dereferrer_link}{$xxx.directurl}"
														   title="View at Hot Movies">HotMovies</a>
						{/if}
					{/if}
					<p>
						{if $movie && $release.videos_id <= 0 && $movie.plot != ''}<span
								class="descinitial">{$movie.plot|escape:"htmlall"|truncate:500:"...":true}</span>
							{if $movie.plot|strlen > 500}
								<a class="descmore" href="#">more...</a>
								<span class="descfull">{$movie.plot|escape:"htmlall"|nl2br|magicurl}</span>{/if}{/if}
						{if $show && $release.videos_id > 0 && $show.summary != ""}<span
								class="descinitial">{$show.summary|escape:"htmlall"|nl2br|magicurl|truncate:500:"...":true}</span>
							{if $show.summary|strlen > 500}
								<a class="descmore" href="#">more...</a>
								<span class="descfull">{$show.summary|escape:"htmlall"|nl2br|magicurl}</span>{/if}{/if}
						{if $xxx}
							{if $xxx.tagline != ''}<br/>{$xxx.tagline|stripslashes|escape:"htmlall"}{/if}
							{if $xxx.plot != ''}{if $xxx.tagline != ''} - {else}
								<br/>
							{/if}{$xxx.plot|stripslashes|escape:"htmlall"}{/if}
						{/if}
						{if $anidb && $release.anidbid > 0 && $anidb.description != ""}{$anidb.description|escape:"htmlall"|nl2br|magicurl|truncate:500:"...":true}{/if}
						{if $music && $music.review != ""}{$music.review|escape:"htmlall"|nl2br|magicurl|truncate:500:"...":true}{/if}
						{if $boo && $boo.review != ""}{$boo.review|escape:"htmlall"|nl2br|magicurl|truncate:500:"...":true}{/if}
						{if $con &&$con.review != ""}{$con.review|escape:"htmlall"|nl2br|magicurl|truncate:500:"...":true}{/if}
					</p>
					<div class="box col-md-12">
						<div class="box-body">
							<div class="tabbable">
								<ul class="nav nav-tabs nav-primary">
									<li class="active"><a href="#pane1"
														  data-toggle="tab">Info</a></li>
									{if $movie && $release.videos_id <= 0}{if $movie.trailer != ""}
										<li><a href="#pane2" data-toggle="tab">Trailer</a></li>
									{/if}{/if}
									{if isset($xxx.trailers) && $xxx.trailers != ''}
										<li><a href="#pane2" data-toggle="tab">Trailer</a></li>
									{/if}
									{if isset($nfo.nfo) && $nfo.nfo != ''}
										<li><a href="#pane3" data-toggle="tab">NFO</a></li>
									{/if}
									{if isset($similars) && $similars|@count > 1}
										<li><a href="#pane4" data-toggle="tab">Similar</a></li>
									{/if}
									{if $release.jpgstatus == 1 && $userdata.canpreview == 1}
										<li><a href="#pane6" data-toggle="tab">Sample</a></li>
									{/if}
									<li><a href="#comments" data-toggle="tab">Comments</a></li>
									{if ($release.haspreview == 1 && $userdata.canpreview == 1) || ($release.haspreview == 2 && $userdata.canpreview == 1)}
										<li><a href="#pane7" data-toggle="tab">Preview</a></li>
									{/if}
									{if $reVideo.releaseid|@count > 0 || $reAudio|@count > 0}
										<li><a href="#pane8" data-toggle="tab">MediaInfo</a></li>
									{/if}
									{if isset($xxx.backdrop) && $xxx.backdrop == 1}
										<li><a href="#pane9" data-toggle="tab">Back Cover</a></li>
									{/if}
									{if isset($game.backdrop) && $game.backdrop == 1}
									<li><a href="#pane10" data-toggle="tab">Screenshot</a></li>
									{/if}
								</ul>
								<div class="tab-content">
									<div id="pane1" class="tab-pane active">
										<div class="row small-gutter-left">
											<div class="col-md-3 small-gutter-left">
												{if $movie && $release.videos_id <= 0 && $movie.cover == 1}
													<img src="{$smarty.const.WWW_TOP}/covers/movies/{$movie.imdbid}-cover.jpg"
														 width="185"
														 alt="{$movie.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $show && $release.videos_id > 0 && $show.image != "0"}
													<img src="{$smarty.const.WWW_TOP}/covers/tvshows/{$release.videos_id}.jpg"
														 width="185"
														 alt="{$show.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $anidb && $release.anidbid > 0 && $anidb.picture != ""}
													<img src="{$smarty.const.WWW_TOP}/covers/anime/{$anidb.anidbid}.jpg"
														 width="185"
														 alt="{$anidb.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $con && $con.cover == 1}
													<img src="{$smarty.const.WWW_TOP}/covers/console/{$con.id}.jpg"
														 width="185"
														 alt="{$con.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $game && $game.cover == 1}
													<img src="{$smarty.const.WWW_TOP}/covers/games/{$game.id}.jpg"
														 width="185"
														 alt="{$con.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $music && $music.cover == 1}
													<img src="{$smarty.const.WWW_TOP}/covers/music/{$music.id}.jpg"
														 width="185"
														 alt="{$music.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $boo && $boo.cover == 1}
													<img src="{$smarty.const.WWW_TOP}/covers/book/{$boo.id}.jpg"
														 width="185"
														 alt="{$boo.title|escape:"htmlall"}"
														 data-toggle="modal"
														 data-target="#modal-image"/>
												{/if}
												{if $xxx && $xxx.cover == 1}
													<a href="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-cover.jpg"
													   class="modal-image"><img
																class="modal-image"
																src="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-cover.jpg"
																width="185"
																alt="{$xxx.title|escape:"htmlall"}"
																data-toggle="modal"
																data-target="#modal-image"/></a>
												{/if}
												<br/><br/>
												<div class="btn-group btn-group-vertical">
													<a class="btn btn-primary btn-sm btn-success btn-transparent"
													   href="{$smarty.const.WWW_TOP}/getnzb/{$release.guid}/{$release.searchname|escape:"htmlall"}"><i
																class="fa fa-cloud-download"></i>
														Download</a>
													<button type="button"
															class="btn btn-primary btn-sm btn-info btn-transparent cartadd">
														<i class="icon icon_cart fa fa-shopping-basket guid"
														   id="guid{$release.guid}"></i> Add to
														Cart
													</button>
													{if isset($sabintegrated)}
														<button type="button"
																class="btn btn-primary btn-sm btn-transparent sabsend">
														<i class="icon_sab fa fa-arrow-right"
														   id="guid{$release.guid}"></i> Send to
														Queue
														</button>{/if}
													<p id="demo"></p>
												</div>
											</div>
											<div class="col-md-9 small-gutter-left">
												<table cellpadding="0" cellspacing="0"
													   width="100%">
													<tbody>
													<tr valign="top">
														<td>
															<table class="data table table-condensed table-striped table-responsive table-hover">
																<tbody>
																{if $movie && $release.videos_id <= 0}
																	<tr>
																		<th width="140">Name
																		</th>
																		<td>{$movie.title|escape:"htmlall"}</td>
																	</tr>
																{/if}
																{if $show && $release.videos_id > 0}
																	<tr>
																		<th width="140">Name
																		</th>
																		<td>{$release.title|escape:"htmlall"}</td>
																	</tr>
																{/if}
																{if $xxx}
																	<tr>
																		<th width="140">Name
																		</th>
																		<td>{$xxx.title|stripslashes|escape:"htmlall"}</td>
																	</tr>
																	<tr>
																		<th width="140">
																			Starring
																		</th>
																		<td>{$xxx.actors}</td>
																	</tr>
																	{if isset($xxx.director) && $xxx.director != ""}
																		<tr>
																			<th width="140">
																				Director
																			</th>
																			<td>{$xxx.director}</td>
																		</tr>
																	{/if}
																	{if isset($xxx.genres) && $xxx.genres != ""}
																		<tr>
																			<th width="140">
																				Genre
																			</th>
																			<td>{$xxx.genres}</td>
																		</tr>
																	{/if}
																{/if}
																{if $movie && $release.videos_id <= 0}
																	<tr>
																		<th width="140">
																			Starring
																		</th>
																		<td>{$movie.actors}</td>
																	</tr>
																	<tr>
																		<th width="140">
																			Director
																		</th>
																		<td>{$movie.director}</td>
																	</tr>
																	<tr>
																		<th width="140">Genre
																		</th>
																		<td>{$movie.genre}</td>
																	</tr>
																	<tr>
																		<th width="140">Year &
																			Rating
																		</th>
																		<td>{$movie.year}
																			- {if $movie.rating == ''}N/A{/if}{$movie.rating}
																			/10
																		</td>
																	</tr>
																{/if}
																{if $show && $release.videos_id > 0}
																	{if $release.firstaired != ""}
																		<tr>
																			<th width="140">
																				Aired
																			</th>
																			<td>{$release.firstaired|date_format}</td>
																		</tr>
																	{/if}
																	{if $show.publisher != ""}
																		<tr>
																			<th width="140">
																				Network
																			</th>
																			<td>{$show.publisher}</td>
																		</tr>
																	{/if}
																	{if $show.countries_id != ""}
																		<tr>
																			<th width="140">
																				Country
																			</th>
																			<td>{$show.countries_id}</td>
																		</tr>
																	{/if}
																{/if}
																{if $music}
																	<tr>
																		<th width="140">Name
																		</th>
																		<td>{$music.title|escape:"htmlall"}</td>
																	</tr>
																	<tr>
																		<th width="140">Genre
																		</th>
																		<td>{$music.genres|escape:"htmlall"}</td>
																	</tr>
																	{if $music.releasedate != ""}
																		<tr>
																			<th width="140">
																				Release Date
																			</th>
																			<td>{$music.releasedate|date_format}</td>
																		</tr>
																	{/if}
																	{if $music.publisher != ""}
																		<tr>
																			<th width="140">
																				Publisher
																			</th>
																			<td>{$music.publisher|escape:"htmlall"}</td>
																		</tr>
																	{/if}
																{/if}
																{if $boo}
																	<tr>
																		<th width="140">Name
																		</th>
																		<td>{$boo.title|escape:"htmlall"}</td>
																	</tr>
																	<tr>
																		<th width="140">Author
																		</th>
																		<td>{$boo.author|escape:"htmlall"}</td>
																	</tr>
																	{if $boo.ean != ""}
																		<tr>
																			<th width="140">
																				EAN
																			</th>
																			<td>{$boo.ean|escape:"htmlall"}</td>
																		</tr>
																	{/if}
																	{if $boo.isbn != ""}
																		<tr>
																			<th width="140">
																				ISBN
																			</th>
																			<td>{$boo.isbn|escape:"htmlall"}</td>
																		</tr>
																	{/if}
																	{if $boo.pages != ""}
																		<tr>
																			<th width="140">
																				Pages
																			</th>
																			<td>{$boo.pages|escape:"htmlall"}</td>
																		</tr>
																	{/if}
																	{if $boo.dewey != ""}
																		<tr>
																			<th width="140">
																				Dewey
																			</th>
																			<td>{$boo.dewey|escape:"htmlall"}</td>
																		</tr>
																	{/if}
																	{if $boo.publisher != ""}
																		<tr>
																			<th width="140">
																				Publisher
																			</th>
																			<td>{$boo.publisher|escape:"htmlall"}</td>
																		</tr>
																	{/if}
																	{if $boo.publishdate != ""}
																		<tr>
																			<th width="140">
																				Released
																			</th>
																			<td>{$boo.publishdate|date_format}</td>
																		</tr>
																	{/if}
																{/if}
																<tr>
																	<th width="140">Group</th>
																	<td>
																		<a title="Browse {$release.group_name}"
																		   href="{$smarty.const.WWW_TOP}/browse?g={$release.group_name}">{$release.group_name|replace:"alt.binaries":"a.b"}</a>
																	</td>
																</tr>
																<tr>
																	<th width="140">Size /
																		Completion
																	</th>
																	<td>{$release.size|fsize_format:"MB"}{if $release.completion > 0}&nbsp;({if $release.completion < 100}
																			<span class="warning">{$release.completion}
																			%</span>{else}{$release.completion}%{/if}){/if}
																	</td>
																</tr>
																<tr>
																	<th width="140">Grabs</th>
																	<td>{$release.grabs}
																		time{if $release.grabs==1}{else}s{/if}</td>
																</tr>
																{if $failed != NULL && $failed > 0}
																<tr>
																	<th width="140">Failed Download</th>
																	<td>{$failed}
																		time{if $failed==1}{else}s{/if}</td>
																</tr>
																{/if}
																<tr>
																	<th width="140">Password
																	</th>
																	<td>{if $release.passwordstatus == 0}None{elseif $release.passwordstatus == 2}Passworded Rar Archive{elseif $release.passwordstatus == 1}Contains Cab/Ace/Rar Inside Archive{else}Unknown{/if}</td>
																</tr>
																<tr>
																	<th width="140">Category
																	</th>
																	<td>
																		<a title="Browse by {$release.category_name}"
																		   href="{$smarty.const.WWW_TOP}/browse?t={$release.categoryid}">{$release.category_name}</a>
																	</td>
																</tr>
																<tr>
																	<th width="140">Files</th>
																	<td>
																		<a title="View file list"
																		   href="{$smarty.const.WWW_TOP}/filelist/{$release.guid}">{$release.totalpart}
																			file{if $release.totalpart==1}{else}s{/if}</a>
																	</td>
																</tr>
																<tr>
																	<th width="140">RAR Contains
																	</th>
																	<td>
																		<strong>Files:</strong><br/>
																		{foreach from=$releasefiles item=rf}
																			<code>{$rf.name}</code>
																			<br/>
																			{if $rf.passworded != 1}
																				<i class="fa fa-unlock"></i>
																				<span class="label label-success">No Password</span>
																			{else}
																				<i class="fa fa-lock"></i>
																				<span class="label label-danger">Passworded</span>
																			{/if}
																			<span class="label label-default">{$rf.size|fsize_format:"MB"}</span>
																			<span class="label label-default">{$rf.createddate|date_format}</span>
																			<br/>
																		{/foreach}
																	</td>
																</tr>
																<tr>
																	<th width="140">Poster</th>
																	<td>{$release.fromname|escape:"htmlall"}</td>
																</tr>
																<tr>
																	<th width="140">Posted</th>
																	<td>{$release.postdate|date_format:"%b %e, %Y %T"}
																		({$release.postdate|daysago}
																		)
																	</td>
																</tr>
																<tr>
																	<th width="140">Added</th>
																	<td>{$release.adddate|date_format:"%b %e, %Y %T"}
																		({$release.adddate|daysago}
																		)
																	</td>
																</tr>
																{if isset($isadmin)}
																	<tr>
																		<th width="140">Release
																			Info
																		</th>
																		<td>{if $release.regexid != ""}Regex Id (
																				<a href="{$smarty.const.WWW_TOP}/admin/regex-list.php?group={$release.group_name|escape:"url"}#{$release.regexid}">{$release.regexid}</a>
																				)
																			{/if}
																			{if $release.gid != ""}
																				Global Id ({$release.gid})
																			{/if}
																		</td>
																	</tr>
																{/if}
																</tbody>
															</table>
														</td>
													</tr>
													</tbody>
												</table>
											</div>
										</div>
									</div>
									<div id="pane2" class="tab-pane">
										{if $xxx}
											{if $xxx.trailers != ''}
												{$xxx.trailers}
											{/if}
										{/if}
										{if $movie && $release.videos_id <= 0}
											{if $movie.trailer != ''}
												{$movie.trailer}
											{/if}
										{/if}
									</div>
									<div id="pane3" class="tab-pane">
										<pre id="nfo">{$nfo.nfo}</pre>
									</div>
									<div id="pane4" class="tab-pane">
										{if isset($similars) && $similars|@count > 1}
							Similar:
							<ul>
							{foreach from=$similars item=similar}
											<li>
												<a title="View similar NZB details"
												   href="{$smarty.const.WWW_TOP}/details/{$similar.guid}/{$similar.searchname|escape:"htmlall"}">{$similar.searchname|escape:"htmlall"}</a>
												<br/>
											</li>
										{/foreach}
							</ul>
								<br/>
								<a title="Search for similar Nzbs" href="{$smarty.const.WWW_TOP}/search/{$searchname|escape:"htmlall"}">Search for similar NZBs...</a><br/>
							</td>
						</tr>
						{/if}
									</div>
									<div id="comments" class="tab-pane">
										{if $comments|@count > 0}
											<table class="tdata table table-condensed table-striped table-responsive table-hover">
												<tr class="{cycle values=",alt"}">
													<th width="80">User</th>
													<th>Comment</th>
												</tr>
												{foreach from=$comments|@array_reverse:true item=comment}
													<tr>
														<td class="less" title="{$comment.createddate}">
															{if !$privateprofiles || $isadmin || $ismod}
																<a title="View {$comment.username}'s profile" href="{$smarty.const.WWW_TOP}/profile?name={$comment.username}">{$comment.username}</a>
															{else}
																{$comment.username}
															{/if}
															<br/>{$comment.createddate|daysago}
														</td>
														{if isset($comment.shared) && $comment.shared == 2}
															<td style="color:#6B2447">{$comment.text|escape:"htmlall"|nl2br}</td>
														{else}
															<td>{$comment.text|escape:"htmlall"|nl2br}</td>
														{/if}
												{/foreach}
											</table>
										{else}
											<div class="alert alert-info" role="alert">
												No comments yet...
											</div>
										{/if}
										<form action="" method="post">
											<label for="txtAddComment">Add Comment:</label><br/>
											<textarea id="txtAddComment" name="txtAddComment" rows="6" cols="60"></textarea>
											<br/>
											<input class="btn" type="submit" value="Submit"/>
										</form>
									</div>
									{if $release.jpgstatus == 1 && $userdata.canpreview == 1}
										<div id="pane6" class="tab-pane">
											<img src="{$smarty.const.WWW_TOP}/covers/sample/{$release.guid}_thumb.jpg"
												 alt="{$release.searchname|escape:"htmlall"}"
												 data-toggle="modal"
												 data-target="#modal-image"/>
										</div>
									{/if}
									{if ($release.haspreview == 1 && $userdata.canpreview == 1) || ($release.haspreview == 2 && $userdata.canpreview == 1)}
										<div id="pane7" class="tab-pane">
											<img src="{$smarty.const.WWW_TOP}/covers/preview/{$release.guid}_thumb.jpg"
												 alt="{$release.searchname|escape:"htmlall"}"
												 data-toggle="modal"
												 data-target="#modal-image"/>
										</div>
									{/if}
									{if $reVideo.releaseid|@count > 0 || $reAudio|@count > 0}
										<div id="pane8" class="tab-pane">
											<table style="width:100%;"
												   class="data table table-condensed table-striped table-responsive table-hover">
												<tr>
													<th width="15%"></th>
													<th>Property</th>
													<th class="right">Value</th>
												</tr>
												{if $reVideo.containerformat != ""}
													<tr>
														<td style="width:15%;"><strong>Overall</strong></td>
														<td>Container Format</td>
														<td class="right">{$reVideo.containerformat}</td>
													</tr>
												{/if}
												{if $reVideo.overallbitrate != ""}
													<tr>
														<td></td>
														<td>Bitrate</td>
														<td class="right">{$reVideo.overallbitrate}</td>
													</tr>
												{/if}
												{if $reVideo.videoduration != ""}
													<tr>
														<td><strong>Video</strong></td>
														<td>Duration</td>
														<td class="right">{$reVideo.videoduration}</td>
													</tr>
												{/if}
												{if $reVideo.videoformat != ""}
													<tr>
														<td></td>
														<td>Format</td>
														<td class="right">{$reVideo.videoformat}</td>
													</tr>
												{/if}
												{if $reVideo.videocodec != ""}
													<tr>
														<td></td>
														<td>Codec</td>
														<td class="right">{$reVideo.videocodec}</td>
													</tr>
												{/if}
												{if $reVideo.videowidth != "" && $reVideo.videoheight != ""}
													<tr>
														<td></td>
														<td>Width x Height</td>
														<td class="right">{$reVideo.videowidth}
															x{$reVideo.videoheight}</td>
													</tr>
												{/if}
												{if $reVideo.videoaspect != ""}
													<tr>
														<td></td>
														<td>Aspect</td>
														<td class="right">{$reVideo.videoaspect}</td>
													</tr>
												{/if}
												{if $reVideo.videoframerate != ""}
													<tr>
														<td></td>
														<td>Framerate</td>
														<td class="right">{$reVideo.videoframerate} fps</td>
													</tr>
												{/if}
												{if $reVideo.videolibrary != ""}
													<tr>
														<td></td>
														<td>Library</td>
														<td class="right">{$reVideo.videolibrary}</td>
													</tr>
												{/if}
												{foreach from=$reAudio item=audio}
													<tr>
														<td><strong>Audio {$audio.audioid}</strong></td>
														<td>Format</td>
														<td class="right">{$audio.audioformat}</td>
													</tr>
													{if $audio.audiolanguage != ""}
														<tr>
															<td></td>
															<td>Language</td>
															<td class="right">{$audio.audiolanguage}</td>
														</tr>
													{/if}
													{if $audio.audiotitle != ""}
														<tr>
															<td></td>
															<td>Title</td>
															<td class="right">{$audio.audiotitle}</td>
														</tr>
													{/if}
													{if $audio.audiomode != ""}
														<tr>
															<td></td>
															<td>Mode</td>
															<td class="right">{$audio.audiomode}</td>
														</tr>
													{/if}
													{if $audio.audiobitratemode != ""}
														<tr>
															<td></td>
															<td>Bitrate Mode</td>
															<td class="right">{$audio.audiobitratemode}</td>
														</tr>
													{/if}
													{if $audio.audiobitrate != ""}
														<tr>
															<td></td>
															<td>Bitrate</td>
															<td class="right">{$audio.audiobitrate}</td>
														</tr>
													{/if}
													{if $audio.audiochannels != ""}
														<tr>
															<td></td>
															<td>Channels</td>
															<td class="right">{$audio.audiochannels}</td>
														</tr>
													{/if}
													{if $audio.audiosamplerate != ""}
														<tr>
															<td></td>
															<td>Sample Rate</td>
															<td class="right">{$audio.audiosamplerate}</td>
														</tr>
													{/if}
													{if $audio.audiolibrary != ""}
														<tr>
															<td></td>
															<td>Library</td>
															<td class="right">{$audio.audiolibrary}</td>
														</tr>
													{/if}
												{/foreach}
												{if $reSubs.subs != ""}
													<tr>
														<td><strong>Subtitles</strong></td>
														<td>Languages</td>
														<td class="right">{$reSubs.subs|escape:"htmlall"}</td>
													</tr>
												{/if}
											</table>
										</div>
									{/if}
									{if isset($xxx.backdrop) && $xxx.backdrop == 1}
										<div id="pane9" class="tab-pane">
											<img src="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-backdrop.jpg"
												 alt="{$xxx.title|escape:"htmlall"}"
												 data-toggle="modal"
												 data-target="#modal-image"/>
										</div>
									{/if}
									{if isset($game.backdrop) && $game.backdrop == 1}
										<div id="pane10" class="tab-pane">
											<img src="{$smarty.const.WWW_TOP}/covers/games/{$game.id}-backdrop.jpg"
												 alt="{$game.title|escape:"htmlall"}"
												 data-toggle="modal"
												 data-target="#modal-image"/>
										</div>
									{/if}
								</div>
							</div>
							<!-- /.tab-content -->
						</div>
						<!-- /.tabbable -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="modal fade modal-image" id="modal-image" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i
							class="icons-office-52"></i></button>
			</div>
			<div class="modal-body">
				{if $movie && $release.videos_id <= 0 && $movie.cover == 1}
					<img src="{$smarty.const.WWW_TOP}/covers/movies/{$movie.imdbid}-cover.jpg"
						 alt="{$movie.title|escape:"htmlall"}">
				{/if}
				{if $show && $release.videos_id > 0 && $show.image != "0"}
					<img src="{$smarty.const.WWW_TOP}/covers/tvshows/{$release.videos_id}.jpg"
						 alt="{$show.title|escape:"htmlall"}"/>
				{/if}
				{if $anidb && $release.anidbid > 0 && $anidb.picture != ""}
					<img src="{$smarty.const.WWW_TOP}/covers/anime/{$anidb.anidbid}.jpg"
						 alt="{$anidb.title|escape:"htmlall"}"/>
				{/if}
				{if $con && $con.cover == 1}
					<img src="{$smarty.const.WWW_TOP}/covers/console/{$con.id}.jpg"
						 alt="{$con.title|escape:"htmlall"}"/>
				{/if}
				{if $music && $music.cover == 1}
					<img src="{$smarty.const.WWW_TOP}/covers/music/{$music.id}.jpg"
						 alt="{$music.title|escape:"htmlall"}"/>
				{/if}
				{if $boo && $boo.cover == 1}
					<img src="{$smarty.const.WWW_TOP}/covers/book/{$boo.id}.jpg"
						 alt="{$boo.title|escape:"htmlall"}"/>
				{/if}
				{if $xxx && $xxx.backdrop == 1}
					<a href="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-backdrop.jpg"
					   class="modal-image_back"><img class="modal-image_back"
													 src="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-backdrop.jpg"
													 alt="{$xxx.title|escape:"htmlall"}"/></a>
				{elseif $xxx && $xxx.cover == 1}
					<a href="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-cover.jpg"
					   class="modal-image"><img class="modal-image"
												src="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-cover.jpg"
												alt="{$xxx.title|escape:"htmlall"}"/></a>
				{/if}
			</div>
		</div>
	</div>
</div>
