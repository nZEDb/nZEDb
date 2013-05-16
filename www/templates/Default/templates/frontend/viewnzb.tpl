
<h1>{$release.searchname|escape:"htmlall"}</h1>

{$site->addetail}	

{if $rage && $release.rageID > 0 && $rage.imgdata != ""}<img class="shadow" src="{$smarty.const.WWW_TOP}/getimage?type=tvrage&amp;id={$rage.ID}" width="180" alt="{$rage.releasetitle|escape:"htmlall"}" style="float:right;" />{/if}
{if $movie && $release.rageID < 0 && $movie.cover == 1}<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/movies/{$movie.imdbID}-cover.jpg" width="180" alt="{$movie.title|escape:"htmlall"}" style="float:right;" />{/if}
{if $anidb && $release.anidbID > 0 && $anidb.picture != ""}<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/anime/{$anidb.anidbID}.jpg" width="180" alt="{$anidb.title|escape:"htmlall"}" style="float:right;" />{/if}
{if $con && $con.cover == 1}<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/console/{$con.ID}.jpg" width="160" alt="{$con.title|escape:"htmlall"}" style="float:right;" />{/if}
{if $boo && $boo.cover == 1}<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/book/{$boo.ID}.jpg" width="160" alt="{$boo.title|escape:"htmlall"}" style="float:right;" />{/if}
{if $music && $music.cover == 1}<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/music/{$music.ID}.jpg" width="160" alt="{$music.title|escape:"htmlall"}" style="float:right;" />{/if}

<table class="data" id="detailstable" >
	{if $isadmin || $ismod}
	<tr><th>Admin:</th><td><a class="rndbtn" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$release.ID}&amp;from={$smarty.server.REQUEST_URI}" title="Edit Release">Edit</a><a class="rndbtn confirm_action" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$release.ID}&amp;from={$smarty.server.HTTP_REFERER}" title="Delete Release">Delete</a></td></tr>
	{/if}
	<tr><th>Name:</th><td>{$release.name|escape:"htmlall"}</td></tr>
	
	{if $rage && $release.rageID > 0}
		<tr><th>Tv Info:</th><td>
			<strong>{if $release.tvtitle != ""}{$release.tvtitle|escape:"htmlall"} - {/if}{$release.seriesfull|replace:"S":"Season "|replace:"E":" Episode "}</strong><br />
			{if $rage.description != ""}<span class="descinitial">{$rage.description|escape:"htmlall"|nl2br|magicurl|truncate:"350":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>{if $rage.description|strlen > 350}<span class="descfull">{$rage.description|escape:"htmlall"|nl2br|magicurl}</span>{/if}<br /><br />{/if}
			{if $rage.genre != ""}<strong>Genre:</strong> {$rage.genre|escape:"htmlall"|replace:"|":", "}<br />{/if}
			{if $release.tvairdate != ""}<strong>Aired:</strong> {$release.tvairdate|date_format}<br/>{/if}
			{if $rage.country != ""}<strong>Country:</strong> {$rage.country}{/if}
			<div style="margin-top:10px;">
				<a class="rndbtn" title="View all episodes from this series" href="{$smarty.const.WWW_TOP}/series/{$release.rageID}">All Episodes</a> 
				<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$release.rageID}" title="View at TV Rage">TV Rage</a>
				<a class="rndbtn" href="{$smarty.const.WWW_TOP}/rss?rage={$release.rageID}&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}" title="Rss feed for this series">Series Rss Feed</a>
			</div>
			</td>
		</tr>
	{/if}
	
	{if $movie && $release.rageID < 0}
	<tr><th>Movie Info:</th><td>
		<strong>{$movie.title|stripslashes|escape:"htmlall"} ({$movie.year}) {if $movie.rating !== ''}{$movie.rating}/10{/if}</strong>
		{if $movie.tagline != ''}<br />{$movie.tagline|stripslashes|escape:"htmlall"}{/if}
		{if $movie.plot != ''}{if $movie.tagline != ''} - {else}<br />{/if}{$movie.plot|stripslashes|escape:"htmlall"}{/if}
		<br /><br />{if $movie.director != ""} <strong>Director:</strong> {$movie.director}<br />{/if}
		<strong>Genre:</strong> {$movie.genre}
		<br /><strong>Starring:</strong> {$movie.actors}
		<div style="margin-top:10px;">
			<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$release.imdbID}/" title="View at IMDB">IMDB</a>
			{if $movie.tmdbID != ''}<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}http://www.themoviedb.org/movie/{$movie.tmdbID}" title="View at TMDb">TMDb</a>{/if}
		</div>
	</td></tr>
	{/if}
	
	{if $anidb && $anidb.anidbID > 0}
		<tr><th>Anime Info:</th><td>
			<strong>{if $release.tvtitle != ""}{$release.tvtitle|escape:"htmlall"}{/if}</strong><br />
			{if $anidb.description != ""}<span class="descinitial">{$anidb.description|escape:"htmlall"|nl2br|magicurl|truncate:"350":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>{if $anidb.description|strlen > 350}<span class="descfull">{$anidb.description|escape:"htmlall"|nl2br|magicurl}</span>{/if}<br /><br />{/if}
			{if $anidb.categories != ""}<strong>Categories:</strong> {$anidb.categories|escape:"htmlall"|replace:"|":", "}<br />{/if}
			{if $release.tvairdate != "0000-00-00 00:00:00"}<strong>Aired:</strong> {$release.tvairdate|date_format}<br/>{/if}
			<div style="margin-top:10px;">
				<a class="rndbtn" title="View all episodes from this anime" href="{$smarty.const.WWW_TOP}/anime/{$release.anidbID}">All Episodes</a> 
				<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$anidb.anidbID}" title="View at AniDB">AniDB</a>
				<a class="rndbtn" href="{$smarty.const.WWW_TOP}/rss?anidb={$release.anidbID}&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}" title="RSS feed for this anime">Anime RSS Feed</a>
			</div>
			</td>
		</tr>
	{/if}
	
	{if $con}
	<tr><th>Console Info:</th><td>
		<strong>{$con.title|escape:"htmlall"} ({$con.releasedate|date_format:"%Y"})</strong><br />
		{if $con.review != ""}<span class="descinitial">{$con.review|escape:"htmlall"|nl2br|magicurl|truncate:"350":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>{if $con.review|strlen > 350}<span class="descfull">{$con.review|escape:"htmlall"|nl2br|magicurl}</span>{/if}<br /><br />{/if}
		{if $con.esrb != ""}<strong>ESRB:</strong> {$con.esrb|escape:"htmlall"}<br />{/if}
		{if $con.genres != ""}<strong>Genre:</strong> {$con.genres|escape:"htmlall"}<br />{/if}
		{if $con.publisher != ""}<strong>Publisher:</strong> {$con.publisher|escape:"htmlall"}<br />{/if}
		{if $con.platform != ""}<strong>Platform:</strong> {$con.platform|escape:"htmlall"}<br />{/if}
		{if $con.releasedate != ""}<strong>Released:</strong> {$con.releasedate|date_format}{/if}
		<div style="margin-top:10px;">
			<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}{$con.url}/" title="View game at Amazon">Amazon</a>
		</div>
	</td></tr>
	{/if}
	
	{if $boo}
	<tr><th>Book Info:</th><td>
		<strong>{$boo.title|escape:"htmlall"}{if $boo.publishdate != "0000-00-00 00:00:00"} ({$boo.publishdate|date_format:"%Y"}){/if}</strong><br />
		{if $boo.author != ""}<strong>Author:</strong> {$boo.author|escape:"htmlall"}<br />{/if}
		{if $boo.genre != "null"}<strong>Genre:</strong> {$boo.genre|escape:"htmlall"}<br />{/if}
		{if $boo.publisher != ""}<strong>Publisher:</strong> {$boo.publisher|escape:"htmlall"}<br />{/if}
		{if $boo.publishdate != ""}<strong>Released:</strong> {$boo.publishdate|date_format}<br />{/if}
		{if $boo.pages != ""}<strong>Pages:</strong> {$boo.pages|escape:"htmlall"}<br />{/if}
		{if $boo.salesrank != ""}<strong>Amazon Rank:</strong> {$boo.salesrank|escape:"htmlall"}<br />{/if}
		{if $boo.asin != ""}<strong>ASIN:</strong> {$boo.asin|escape:"htmlall"}<br />{/if}
		{if $boo.isbn != ""}<strong>ISBN:</strong> {$boo.isbn|escape:"htmlall"}<br />{/if}
		{if $boo.ean != ""}<strong>EAN:</strong> {$boo.ean|escape:"htmlall"}<br />{/if}
		{if $boo.overview != "null"}<strong>Overview:</strong> {$boo.overview|escape:"htmlall"}{/if}
		<div style="margin-top:10px;">
			<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}{$boo.url}/" title="View book at Amazon">Amazon</a>
		</div>
	</td></tr>
	{/if}
	
	{if $music}
	<tr><th>Music Info:</th><td>
		<strong>{$music.title|escape:"htmlall"} {if $music.year != ""}({$music.year}){/if}</strong><br />
		{if $music.review != ""}<span class="descinitial">{$music.review|nl2br|magicurl|truncate:"350":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>{if $music.review|strlen > 350}<span class="descfull">{$music.review|escape:"htmlall"|nl2br|magicurl}</span>{/if}<br /><br />{/if}
		{if $music.genres != ""}<strong>Genre:</strong> {$music.genres|escape:"htmlall"}<br />{/if}
		{if $music.publisher != ""}<strong>Publisher:</strong> {$music.publisher|escape:"htmlall"}<br />{/if}
		{if $music.releasedate != ""}<strong>Released:</strong> {$music.releasedate|date_format}<br />{/if}
		<div style="margin-top:10px;">
			<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}{$music.url}/" title="View record at Amazon">Amazon</a>
		</div>
	</td></tr>
	{if $music.tracks != ""}
	<tr><th>Track Listing:</th><td>
		<ol class="tracklist">
			{assign var="tracksplits" value="|"|explode:$music.tracks}
			{foreach from=$tracksplits item=tracksplit}
			<li>{$tracksplit|trim|escape:"htmlall"}</li>
			{/foreach}		
		</ol>
	</td></tr>
	{/if}
	{/if}
	
	<tr><th>Group:</th><td title="{$release.group_name}"><a title="Browse {$release.group_name}" href="{$smarty.const.WWW_TOP}/browse?g={$release.group_name}">{$release.group_name|replace:"alt.binaries":"a.b"}</a></td></tr>
	<tr><th>Category:</th><td><a title="Browse by {$release.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$release.categoryID}">{$release.category_name}</a></td></tr>
	{if $nfo.ID|@count > 0}
	<tr><th>Nfo:</th><td><a href="{$smarty.const.WWW_TOP}/nfo/{$release.guid}" title="View Nfo">View Nfo</a></td></tr>
	{/if}

	{if $reVideo.releaseID|@count > 0}
	<tr><th>Media Info:</th>
		<td style="padding:0;">
			<table style="width:100%;" class="innerdata highlight">
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
					<td><strong>Audio {$audio.audioID}</strong></td>
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

	{if $release.haspreview == 1 && $userdata.canpreview == 1}
	<tr><th>Preview:</th><td><img width="450" src="{$smarty.const.WWW_TOP}/covers/preview/{$release.guid}_thumb.jpg" alt="{$release.searchname|escape:"htmlall"} screenshot" /></td></tr>
	{/if}

	<tr><th>Size:</th><td>{$release.size|fsize_format:"MB"}{if $release.completion > 0}&nbsp;({if $release.completion < 100}<span class="warning">{$release.completion}%</span>{else}{$release.completion}%{/if}){/if}</td></tr>
	<tr><th>ID:</th><td>{$release.ID}</td></tr>
	<tr><th>Grabs:</th><td>{$release.grabs} time{if $release.grabs==1}{else}s{/if}</td></tr>
	<tr><th>Files:</th><td><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$release.guid}">{$release.totalpart} file{if $release.totalpart==1}{else}s{/if}</a></td></tr>
	{if $releasefiles|@count > 0}
	<tr><th>Rar Contains:</th>
		<td style="padding:0;">
			<table style="width:100%;" class="innerdata highlight">
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
		</td>
	</tr>
	{/if}
	
	{if $site->checkpasswordedrar > 0}
	<tr><th>Password:</th>
		<td>
			{if $release.passwordstatus == 0}None{elseif $release.passwordstatus == 1}Possible Passworded Archive{elseif $release.passwordstatus == 2}Passworded{else}Unknown{/if}
		</td>
	</tr>
	{/if}
	<tr><th>Poster:</th><td>{$release.fromname|escape:"htmlall"}</td></tr>
	<tr><th>Posted:</th><td title="{$release.postdate}">{$release.postdate|date_format} ({$release.postdate|daysago})</td></tr>
	<tr><th>Added:</th><td title="{$release.adddate}">{$release.adddate|date_format} ({$release.adddate|daysago})</td></tr>
	<tr id="guid{$release.guid}"><th>Download:</th><td>
		<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$release.guid}/{$release.searchname|escape:"htmlall"}">&nbsp;</a></div>
		<div class="icon icon_cart" title="Add to Cart"></div>
		{if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
	</td></tr>

	{if $similars|@count > 1}
	<tr>
		<th>Similar:</th>
		<td>
			{foreach from=$similars item=similar}
				<a title="View similar Nzb details" href="{$smarty.const.WWW_TOP}/details/{$similar.guid}/{$similar.searchname|escape:"htmlall"}">{$similar.searchname|escape:"htmlall"}</a><br/>
			{/foreach}
			<br/>
			<a title="Search for similar Nzbs" href="{$smarty.const.WWW_TOP}/search/{$searchname|escape:"htmlall"}">Search for similar NZBs...</a><br/>
		</td>
	</tr>
	{/if}
	{if $isadmin}
	{/if}
</table>

<div class="comments">
	<a id="comments"></a>
	<h2>Comments</h2>
	
	{if $comments|@count > 0}
	
		<table style="margin-bottom:20px;" class="data Sortable">
			<tr class="{cycle values=",alt"}">
			<th width="80">User</th>
			<th>Comment</th>
			</tr>
		{foreach from=$comments item=comment}
			<tr>
				<td class="less" title="{$comment.createddate}"><a title="View {$comment.username}'s profile" href="{$smarty.const.WWW_TOP}/profile?name={$comment.username}">{$comment.username}</a><br/>{$comment.createddate|date_format}</td>
				<td>{$comment.text|escape:"htmlall"|nl2br}</td>
			</tr>
		{/foreach}
		</table>
	
	{/if}
	
	<form action="" method="post">
		<label for="txtAddComment">Add Comment</label>:<br/>
		<textarea id="txtAddComment" name="txtAddComment" rows="6" cols="60"></textarea>
		<br/>
		<input type="submit" value="submit"/>
	</form>

</div>
