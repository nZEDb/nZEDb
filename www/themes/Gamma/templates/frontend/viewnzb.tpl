<h2>{$release.searchname|escape:"htmlall"}</h2>
{$site->addetail}
<div id="content">
	<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
		<li class="active"><a href="#info" data-toggle="tab">Info</a></li>
		{if $reVideo.releaseid|@count > 0 || $reAudio|@count > 0}
			<li><a href="#mediainfo" data-toggle="tab">Media info</a></li>
		{/if}
		{if $release.jpgstatus == 1 && $userdata.canpreview == 1}
			<li><a href="#thumbnail" data-toggle="tab">Thumbnail</a></li>
		{else}
			{if $release.haspreview == 1 && $userdata.canpreview == 1}
				<li><a href="#preview" data-toggle="tab">Preview</a></li>
			{/if}
		{/if}
		{if ($release.videostatus == 1 && $userdata.canpreview == 1)}
			<li><a href="#sample" data-toggle="tab">Sample</a></li>
		{/if}
		{if isset($xxx.backdrop) && $xxx.backdrop == 1}
			<li><a href="#backcover" data-toggle="tab">Back Cover</a></li>
		{/if}
		{if isset($game.backdrop) && $game.backdrop == 1}
			<li><a href="#screenshot" data-toggle="tab">Screenshot</a></li>
		{/if}
		<li><a href="#comments" data-toggle="tab">Comments</a></li>
	</ul>
	<div id="my-tab-content" class="tab-content">
		<div class="tab-pane active" id="info">
			{if $show && $release.videos_id > 0 && $show.image != '0'}<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;" src="{$smarty.const.WWW_TOP}/covers/tvshows/{$release.videos_id}.jpg" width="180" alt="{$show.title|escape:"htmlall"}" />{/if}
			{if $movie && $release.videos_id == 0 && $movie.cover == 1}<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;" src="{$smarty.const.WWW_TOP}/covers/movies/{$movie.imdbid}-cover.jpg" width="180" alt="{$movie.title|escape:"htmlall"}" />{/if}
			{if $anidb && $release.anidbid > 0 && $anidb.picture != ""}<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;" src="{$smarty.const.WWW_TOP}/covers/anime/{$anidb.anidbid}.jpg" width="180" alt="{$anidb.title|escape:"htmlall"}" />{/if}
			{if $con && $con.cover == 1}<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;" src="{$smarty.const.WWW_TOP}/covers/console/{$con.id}.jpg" width="160" alt="{$con.title|escape:"htmlall"}" />{/if}
			{if $music && $music.cover == 1}<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;" src="{$smarty.const.WWW_TOP}/covers/music/{$music.id}.jpg" width="160" alt="{$music.title|escape:"htmlall"}" />{/if}
			{if $boo && $boo.cover == 1}<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;" src="{$smarty.const.WWW_TOP}/covers/book/{$boo.id}.jpg" width="160" alt="{$boo.title|escape:"htmlall"}" />{/if}
			{if $game && $game.cover == 1}
				<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;"  src="{$smarty.const.WWW_TOP}/covers/games/{$game.id}.jpg" width="160" alt="{$con.title|escape:"htmlall"}"/>
			{/if}
			{if $xxx && $xxx.cover == 1}
				<img class="shadow img-polaroid pull-right" style="margin-right:50px; margin-top:80px;"  src="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-cover.jpg" width="160" alt="{$xxx.title|escape:"htmlall"}"/>
			{/if}
			{if $isadmin}
				<div class="well well-small pull-right">
					Admin :
					<div class="btn-group">
						<a href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$release.id}&amp;from={$smarty.server.REQUEST_URI}" class="btn btn-small btn-warning" >Edit</a>
						<a href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$release.id}&amp;from={$smarty.server.HTTP_REFERER}" class=" btn btn-small btn-danger" >Delete</a>
					</div>
				</div>
			{/if}
			<dl class="dl-horizontal" style="margin-right:300px;">
				<dt>Name</dt>
				<dd>{$release.name|escape:"htmlall"}</dd>
				{if $show && $release.videos_id > 0}
				<dt>Show:</dt>
				<dd><strong>{if $show.title != ""}{$show.title|escape:"htmlall"}</strong></dd>
				{if $show.summary != ""}
					<dt>Descrition</dt>
					<dd><span class="descinitial">{$show.summary|escape:"htmlall"|nl2br|magicurl|truncate:"350":"</span><a class=\"descmore\" href=\"#\"> more...</a>"}{if $show.summary|strlen > 350}<span class="descfull">{$show.summary|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}</dd>
				{/if}
				{if $release.firstaired != ""}
					<dt>Aired</dt>
					<dd>{$release.firstaired|date_format}</dd>
				{/if}
				{if $show.countries_id != ""}
					<dt>Country</dt>
					<td>{$show.countries_id}</td>
				{/if}
				{/if}
			</dl>
			<div style="margin-left:180px; margin-bottom:5px;">
				<a class="label" title="View all episodes from this series" href="{$smarty.const.WWW_TOP}/series/{$show.id}">All Episodes</a>
				{if $show.trakt > 0}<a class="label label-info" target="_blank" href="{$site->dereferrer_link}http://www.trakt.tv/shows/{$show.trakt}" title="View on Trakt">Trakt</a>{/if}
				{if $show.tvdb > 0}<a class="label" target="_blank" href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$show.tvdb}" title="View at TheTVDB">TheTVDB</a>{/if}
			</div>
			{/if}
			{if $movie && $release.videos_id == 0}
				<dl class="dl" style="margin-right:300px;">
					<dt>Movie Info</dt>
					<dd>{$movie.title|escape:"htmlall"}</dd>
					<dt>Year</dt>
					<dd>{$movie.year}</dd>
					<dt>Rating</dt>
					<dd><strong>{if $movie.rating == ''}N/A{/if}{$movie.rating}/10</strong></dd>
					{if $movie.tagline != ''}
						<dt>Tagline</dt>
						<dd>{$movie.tagline|escape:"htmlall"}</dd>
					{/if}
					{if $movie.plot != ''}
						<dt>Plot</dt>
						<dd>{$movie.plot|escape:"htmlall"}</dd>
					{/if}
					{if $movie.director != ""}
						<dt>Director</dt>
						<dd>{$movie.director}</dd>
					{/if}
					<dt>Genre</dt>
					<dd>{$movie.genre}</dd>
					<dt>Starring</dt>
					<dd>{$movie.actors}</dd>
				</dl>
				<div style="margin-left: 180px;">
					<a class="rndbtn badge badge-imdb" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$release.imdbid}/" title="View at IMDB">IMDB</a>
					{if $movie.tmdbid != ''}
						<a class="rndbtn badge badge-inverse" target="_blank" href="{$site->dereferrer_link}http://www.themoviedb.org/movie/{$movie.tmdbid}" title="View at TMDb">TMDb</a>
					{/if}
					<a class="rndbtn badge" href="{$smarty.const.WWW_TOP}/movies?imdb={$release.imdbid}" title="View all versions">Movie View</a>
				</div>
			{/if}
			{if $anidb && $release.anidbid > 0}
				<dl class="dl-horizontal" style="margin-right:300px;">
					<dt>Anime Info</dt>
					<dd>{if $release.tvtitle != ""}{$release.tvtitle|escape:"htmlall"}{/if}</dd>
					{if $anidb.description != ""}
						<dt>Description</dt>
						<dd><span class="descinitial">{$anidb.description|escape:"htmlall"|nl2br|magicurl|truncate:"350":"</span><a class=\"descmore\" href=\"#\"> more...</a>"}{if $anidb.description|strlen > 350}<span class="descfull">{$anidb.description|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}</dd>
					{/if}
					{if $anidb.categories != ""}
						<dt>Categories</dt>
						<dd>{$anidb.categories|escape:"htmlall"|replace:"|":", "}</dd>
					{/if}
					{if $release.tvairdate != "0000-00-00 00:00:00"}
						<dt>Aired</dt>
						<dd>{$release.tvairdate|date_format}</dd>
					{/if}
					{if $episode && $release.episodeinfoid > 0}
						{if $episode.overview != ""}
							<dt>Overview</dt>
							<dd>{$episode.overview}</dd>
						{/if}
						{if $episode.rating > 0}
							<dt>Rating</dt>
							<dd>{$episode.rating}</dd>
						{/if}
						{if $episode.director != ""}
							<dt>Director</dt>
							<dd>{$episode.director|escape:"htmlall"|replace:"|":", "}</dd>
						{/if}
						{if $episode.gueststars != ""}
							<dt>Guest Stars</dt>
							<dd>{$episode.gueststars|escape:"htmlall"|replace:"|":", "}</dd>
						{/if}
						{if $episode.writer != ""}
							<dt>Writer</dt>
							<dd>{$episode.writer|escape:"htmlall"|replace:"|":", "}</dd>
						{/if}
					{/if}

					<div style="margin-left: 180px;">
						<a class="rndbtn badge" title="View all episodes from this anime" href="{$smarty.const.WWW_TOP}/anime/{$release.anidbid}">All Episodes</a>
						<a class="rndbtn badge badge-inverse" target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$anidb.anidbid}" title="View at AniDB">AniDB</a>
						{if $release.tvdbid > 0}<a class="rndbtn badge" target="_blank" href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$release.tvdbid}&lid=7" title="View at TheTVDB">TheTVDB</a>{/if}
						<a class="rndbtn badge badge-info" href="{$smarty.const.WWW_TOP}/rss?anidb={$release.anidbid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}" title="RSS feed for this anime">Anime RSS Feed</a>
					</div>
				</dl>
			{/if}

			{if $con}

				<dl class="dl-horizontal" style="margin-right:300px;">
					<dt>Console Info</dt>
					<dd>{$con.title|escape:"htmlall"} ({$con.releasedate|date_format:"%Y"})</dd>

					{if $con.review != ""}
						<dt>Review</dt>
						<dd><span class="descinitial">{$con.review|escape:"htmlall"|nl2br|magicurl|truncate:"350":"</span><a class=\"descmore\" href=\"#\">more...</a>"}{if $con.review|strlen > 350}<span class="descfull">{$con.review|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}</dd>
					{/if}

					{if $con.esrb != ""}
						<dt>ESRB</dt>
						<dd>{$con.esrb|escape:"htmlall"}</dd>
					{/if}

					{if $con.genres != ""}
						<dt>Genre</dt>
						<dd>{$con.genres|escape:"htmlall"}</dd>
					{/if}

					{if $con.publisher != ""}
						<dt>Publisher</dt>
						<dd>{$con.publisher|escape:"htmlall"}</dd>
					{/if}

					{if $con.platform != ""}
						<dt>Platform</dt>
						<dd>{$con.platform|escape:"htmlall"}</dd>
					{/if}

					{if $con.releasedate != ""}
						<dt>Released</dt>
						<dd>{$con.releasedate|date_format}</dd>
					{/if}

					{if $con.url != ""}
						<dt></dt>
						<dd><a class="rndbtn badge badge-amaz" target="_blank" href="{$site->dereferrer_link}{$con.url}/" title="View game at Amazon">Amazon</a></dd>
					{/if}
				</dl>

			{/if}

			{if $boo}
				<dl class="dl-horizontal" style="margin-right:300px;">
					<dt>Book Info</dt>
					<dd>{$boo.author|escape:"htmlall"} - {$boo.title|escape:"htmlall"}</dd>

					{if $boo.review != ""}
						<dt>Review</dt>
						<dd><span class="descinitial">{$boo.review|escape:"htmlall"|nl2br|magicurl|truncate:"350":"</span><a class=\"descmore\" href=\"#\"> more...</a>"}{if $boo.review|strlen > 350}<span class="descfull">{$boo.review|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}</dd>
					{/if}

					{if $boo.ean != ""}
						<dt>EAN</dt>
						<dd>{$boo.ean|escape:"htmlall"}</dd>
					{/if}

					{if $boo.isbn != ""}
						<dt>ISBN</dt>
						<dd>{$boo.isbn|escape:"htmlall"}</dd>
					{/if}

					{if $boo.pages != ""}
						<dt>Pages</dt>
						<dd>{$boo.pages|escape:"htmlall"}</dd>
					{/if}

					{if $boo.dewey != ""}
						<dt>Dewey</dt>
						<dd>{$boo.dewey|escape:"htmlall"}</dd>
					{/if}

					{if $boo.publisher != ""}
						<dt>Publisher</dt>
						<dd>{$boo.publisher|escape:"htmlall"}</dd>
					{/if}

					{if $boo.publishdate != ""}
						<dt>Publish Date</dt>
						<dd>{$boo.publishdate|date_format}</dd>
					{/if}

					{if $boo.url != ""}
						<br/>
						<div style="margin-left: 180px;">
							<a class="rndbtn badge badge-amaz" target="_blank" href="{$site->dereferrer_link}{$boo.url}/" title="View book at Amazon">Amazon</a>
						</div>
					{/if}
				</dl>
			{/if}

			{if $music}
				<dl class="dl-horizontal" style="margin-right:300px;">
					<dt>Music Info</dt>
					<dd>{$music.title|escape:"htmlall"} {if $music.year != ""}({$music.year}){/if}</dd>

					{if $music.review != ""}
						<dt>Review</dt>
						<dd><span class="descinitial">{$music.review|nl2br|magicurl|truncate:"350":"</span><a class=\"descmore\" href=\"#\">more...</a>"}{if $music.review|strlen > 350}<span class="descfull">{$music.review|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}</dd>
					{/if}

					{if $music.genres != ""}
						<dt>Genre</dt>
						<dd>{$music.genres|escape:"htmlall"}</dd>
					{/if}

					{if $music.publisher != ""}
						<dt>Publisher</dt>
						<dd>{$music.publisher|escape:"htmlall"}</dd>
					{/if}

					{if $music.releasedate != ""}
						<dt>Released</dt>
						<dd>{$music.releasedate|date_format}</dd>
					{/if}

					{if $music.url != ""}
						<dt></dt>
						<dd><a class="rndbtn badge badge-amaz" target="_blank" href="{$site->dereferrer_link}{$music.url}/" title="View record at Amazon">Amazon</a></dd>
					{/if}

					{if $music.tracks != ""}
						<dt>Track Listing</dt>
						<dd>
							<ol>
								{assign var="tracksplits" value="|"|explode:$music.tracks}
								{foreach from=$tracksplits item=tracksplit}
									<li>{$tracksplit|trim|escape:"htmlall"}</li>
								{/foreach}
							</ol>
						</dd>
					{/if}
				</dl>
			{/if}
			<dl class="dl-horizontal" style="margin-right:300px;">
				<dt>Group</dt>
				<dd><a title="Browse {$release.group_name}" href="{$smarty.const.WWW_TOP}/browse?g={$release.group_name}">{$release.group_name|replace:"alt.binaries":"a.b"}</a></dd>

				<dt>Category</dt>
				<dd><a title="Browse by {$release.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$release.categoryid}">{$release.category_name}</a></dd>
				{if $nfo.releaseid|@count > 0}
					<dt>Nfo</dy>
					<dd><a href="{$smarty.const.WWW_TOP}/nfo/{$release.guid}" title="View Nfo">View Nfo</a></dd>
				{/if}
				{if $release.haspreview == 2 && $userdata.canpreview == 1}
					<dt>Preview</dt>
					<dd><a href="#" name="audio{$release.guid}" title="Listen to {$release.searchname|escape:"htmlall"}" class="audioprev rndbtn" rel="audio">Listen</a><audio id="audprev{$release.guid}" src="{$smarty.const.WWW_TOP}/covers/audio/{$release.guid}.mp3" preload="none"></audio></dd>
				{/if}
			</dl>
			<dl class="dl-horizontal" style="margin-right:300px;">
				<dt>Size:</dt>
				<dd>{$release.size|fsize_format:"MB"}{if $release.completion > 0}&nbsp;{if $release.completion < 100}<span class="badge badge-warning">{$release.completion}%</span>{else}<span class="badge badge-success">{$release.completion}%{/if}</span>{/if}</dt>
				<dt>Files</dt>
				<dd><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$release.guid}">{$release.totalpart}</a> <i class="fa fa-file"></i></dd>
				{if $releasefiles|@count > 0}
					<dt>Rar Contains</dt>
					<dd>
						<table style="width:100%;" class="innerdata highlight table tabel-striped">
							<tr>
								<th>Filename</th>
								<th class="mid">Password</th>
								<th class="mid">Size</th>
								<th class="mid">Date</th>
							</tr>
							{foreach from=$releasefiles item=rf}
								<tr>
									<td>{$rf.name}</td>
									<td class="mid">{if $rf.passworded != 1}No{else}Yes{/if}</td>
									<td class="right">{$rf.size|fsize_format:"MB"}</td>
									<td title="{$rf.createddate}" class="right" >{$rf.createddate|date_format}</td>
								</tr>
							{/foreach}
						</table>
					</dd>
				{/if}
				<dl>
					<dt> Grabs</dt>
					<dd>{$release.grabs} time{if $release.grabs==1}{else}s{/if}</dd>
				</dl>
				{if $failed != NULL && $failed >0}
					<dl>
						<dt> Failed Download</dt>
						<dd>{$failed} time{if $failed==1}{else}s{/if}</dd>
					</dl>
				{/if}
				{if $site->checkpasswordedrar > 0}
					<dt>Password</dt>
					<dd>{if $release.passwordstatus == 0}None{elseif $release.passwordstatus == 2}Passworded Rar Archive{elseif $release.passwordstatus == 1}Contains Cab/Ace/Rar Inside Archive{else}Unknown{/if}</dd>
				{/if}
				<dt>Poster</dt>
				<dd>{$release.fromname|escape:"htmlall"}</dd>
				<dt>Posted</dt>
				<dd>{$release.postdate|date_format} ({$release.postdate|daysago} )</dd>
				<dt>Added</dt>
				<dd>{$release.adddate|date_format} ({$release.adddate|daysago} )</dd>
				<dt style="margin-top:15px; margin-bottom:15px;">Download</dt>
				<dd style="margin-top:15px; margin-bottom:15px;" id="{$release.guid}">
					<a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$release.guid}/{$release.searchname|escape:"url"}"></a>
					<a id="guid{$release.guid}" class="icon icon_cartNZBinfo fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;"  href="#" title="Send to my Download Basket"></a>
					{if $sabintegrated}
						<a id="guid{$release.guid}" class="icon icon_sabNZBinfo fa fa-share"  style="text-decoration: none; color: #008ab8;" href="#" title="Send to queue"></a>
					{/if}
					{if isset($nzbgetintegrated)}
						<a id="guid{$release.guid}" class="icon icon_nzb fa fa-cloud-download nzbgetNZBinfo" href="#" title="Send to my NZBGet"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/icons/nzbgetup.png"/></a>
					{/if}
				</dd>
				<dt>Similar</dt>
				<dd>
					<a class="label" title="Search for similar Nzbs" href="{$smarty.const.WWW_TOP}/search/{$searchname|escape:"url"}">Search for similar</a>
				</dd>
				{if $isadmin}
					<dt>Release Info</dt>
					<dd>
						{if isset($release.requestid) && $release.requestid != ""}
							Request Id ({$release.reqid})
						{/if}
					</dd>
				{/if}
			</dl>
		</div>
		<div class="tab-pane" id="mediainfo">
			{if $reVideo.releaseid|@count > 0 || $reAudio|@count > 0}
				<td style="padding:0;">
					<table style="width:100%;" class="innerdata highlight table table-striped">
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
							<td class="right">{$reVideo.videowidth}x{$reVideo.videoheight}</td>
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
				</td>
			</tr>
			{/if}
		</div>
		<div class="tab-pane" id="preview">
			<img class="shadow" width="100%" src="{$smarty.const.WWW_TOP}/covers/preview/{$release.guid}_thumb.jpg" alt="{$release.searchname|escape:"htmlall"} screenshot" />
		</div>
		<div class="tab-pane" id="thumbnail">
			<img class="shadow" height="100%" src="{$smarty.const.WWW_TOP}/covers/sample/{$release.guid}_thumb.jpg" alt="{$release.searchname|escape:"htmlall"} screenshot" />
		</div>
		<div class="tab-pane" id="sample">
			{if ($release.videostatus == 1 && $userdata.canpreview == 1)}
				<video width="75%" controls>
					<source src="/covers/video/{$release.guid}.ogv" type="video/ogg">
					Your browser does not support the video tag.
				</video>
			{/if}
		</div>
		{if isset($xxx.backdrop) && $xxx.backdrop == 1}
			<div id="backcover" class="tab-pane">
				<img src="{$smarty.const.WWW_TOP}/covers/xxx/{$xxx.id}-backdrop.jpg"
					 alt="{$xxx.title|escape:"htmlall"}"
				/>
			</div>
		{/if}
		{if isset($game.backdrop) && $game.backdrop == 1}
			<div id="screenshot" class="tab-pane">
				<img src="{$smarty.const.WWW_TOP}/covers/games/{$game.id}-backdrop.jpg"
					 alt="{$game.title|escape:"htmlall"}"
				/>
			</div>
		{/if}
		<div class="tab-pane" id="comments">
			<div class="comments">
				{if $comments|@count > 0}

					<table style="margin-bottom:20px;" class="data Sortable table table-striped">
						<tr class="{cycle values=",alt"}">
							<th width="150" style="text-align:right;">User </th>
							<th>Comment</th>
						</tr>
						{foreach from=$comments item=comment}
							<tr>
								<td style="text-align:right;" class="less" title="{$comment.createddate}">{if $comment.sourceid == 0}<a title="View {$comment.username}'s profile" href="{$smarty.const.WWW_TOP}/profile?name={$comment.username}">{$comment.username}</a>{else}{$comment.username}<br/><span style="color: #ce0000;">(syndicated)</span>{/if}<br/>{$comment.createddate|date_format}</td>
								<td style="margin-left:30px;">{$comment.text|escape:"htmlall"|nl2br}</td>
							</tr>
						{/foreach}
					</table>
				{/if}
				<dl class="dl-horizontal" style="margin-right:300px;">
					<form action="" method="post">
						<dt><label for="txtAddComment">Add Comment</label></dt>
						<dd><textarea id="txtAddComment" name="txtAddComment" rows="6" cols="60"></textarea></dd>
						<dt> </dt>
						<dd><input class="btn btn-success" type="submit" value="submit"/></dd>
					</form>
				</dl>
			</div>
		</div>
	</div>
</div>
