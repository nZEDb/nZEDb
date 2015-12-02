{if isset($nodata) && $nodata !=''}
<div class="header">
	<h2>View > <strong>Anime</strong></h2>
	<p>{$nodata}</p>
</div>
{else}
<h1>
{if $isadmin}
	<a title="Edit AniDB data" href="{$smarty.const.WWW_TOP}/admin/anidb-edit.php?id={$animeAnidbid}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}">{$animeTitle} </a>
{else}
	{$animeTitle}
{/if}

{if $catname != ''} in {$catname|escape:"htmlall"}{/if}
</h1>

<div class="tvseriesheading">
	{if animePicture != ""}<img class="shadow" alt="{$animeTitle} Picture" src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbid}.jpg" />{/if}
	<p> {if $animeType != ''}<i>({$animeType|escape:"htmlall"})</i>{/if}<br>
		{if $animeCategories != ''}<b>{$animeCategories}</b><br />{/if}<br>
		<span class="descinitial">{$animeDescription|escape:"htmlall"|nl2br|magicurl|truncate:"1500":" </span><a class=\"descmore\" href=\"#\">more...</a>"}<br>
		{if $animeDescription|strlen > 1500}<span class="descfull">{$animeDescription|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}
		{if $animeRating != ''}<br><b>AniDB Rating: {$animeRating|escape:"htmlall"}</b>{/if}
		{if $animeRelated != ''}<br><i>Related Anime: {$animeRelated|escape:"htmlall"}</i><br />{/if}
	</p>

</div>

<form id="nzb_multi_operations_form" action="get">

<div class="nzb_multi_operations">
	<div style="padding-bottom:10px;" >
		<a target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$animeAnidbID}" title="View AniDB">View AniDB</a> |
		{if $animeTvdbID > 0}<a target="_blank" href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$animeTvdbID}" title="View TheTVDB">View TheTVDB</a> | {/if}
		{if $animeImdbID > 0}<a target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$animeImdbID}" title="View IMDb">View IMDb</a> | {/if}
		<a href="{$smarty.const.WWW_TOP}/rss?anidb={$animeAnidbID}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS feed for this Anime</a>
	</div>
	<small>With Selected:</small>
		<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
		<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
		{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
		{if isset($nzbgetintegrated)}<input type="button" class="nzb_multi_operations_nzbget btn btn-small btn-primary" value="Send to NZBGet" />{/if}
	{if $isadmin}
	&nbsp;&nbsp;
		<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
		<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
		<input type="button" class="nzb_multi_operations_rebuild" value="Reb" />
	{/if}
</div>


<table style="width:100%;" class="data highlight icons" id="browsetable">
		{assign var="msplits" value=","|explode:$result.grp_release_id}
		{assign var="mguid" value=","|explode:$result.grp_release_guid}
		{assign var="mnfo" value=","|explode:$result.grp_release_nfoid}
		{assign var="mgrp" value=","|explode:$result.grp_release_grpname}
		{assign var="mname" value="#"|explode:$result.grp_release_name}
		{assign var="mpostdate" value=","|explode:$result.grp_release_postdate}
		{assign var="msize" value=","|explode:$result.grp_release_size}
		{assign var="mtotalparts" value=","|explode:$result.grp_release_totalparts}
		{assign var="mcomments" value=","|explode:$result.grp_release_comments}
		{assign var="mgrabs" value=","|explode:$result.grp_release_comments}
		{assign var="mpass" value=","|explode:$result.grp_release_password}
		{assign var="minnerfiles" value=","|explode:$result.grp_rarinnerfilecount}
		{assign var="mhaspreview" value=","|explode:$result.grp_haspreview}
		{assign var="mcat" value=","|explode:$result.grp_release_categoryid}
		{assign var="mcatname" value=","|explode:$result.grp_release_categoryName}

	{foreach $animeEpisodeTitles as $animeEpno => $animeEpisodeTitle}
		<tr>
			<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2>{$animeEpno}</h2></td>
		</tr>
		<tr>
			<th></th>
			<th>Name</th>
			<th>Category</th>
			<th style="text-align:center;">Posted</th>
			<th>Size</th>
			<th>Files</th>
			<th>Stats</th>
			<th></th>
		</tr>
			{foreach $animeEpisodeTitle as $result}
				<tr class="{cycle values=",alt"}" id="guid{$result.guid}">
					<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" name="{$seasonnum}" value="{$result.guid}" /></td>
					<td>
						<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
					<div class="resextra">
						<div class="btns">
							{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo rndbtn badge" rel="nfo">Nfo</a>{/if}
							{if $result.haspreview == 1 && $userdata.canpreview == 1}<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$mguid[$m@index]}" title="Screenshot" class="modal_prev rndbtn badge" rel="preview">Preview</a>{/if}
							{if $result.reid > 0}<span class="mediainfo rndbtn badge" title="{$result.guid}">Media</span>{/if}
							{if $isadmin}
								<div class="admin" align="right">
										<div class="btn-group">
												<a class="rndbtn btn btn-mini btn-warning" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a>
												<a class="rndbtn confirm_action btn btn-mini btn-danger" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Delete</a>
										</div>
								</div>
							{/if}
					<td class="less"><a title="This anime in {$result.category_name}" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}?t={$result.categoryid}">{$result.category_name}</a></td>
					<td class="less mid" width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>
					<td width="40" class="less right">{$result.size|fsize_format:"MB"}{if $result.completion > 0}<br />{if $result.completion < 100}<span class="warning">{$result.completion}%</span>{else}{$result.completion}%{/if}{/if}</td>
					<td class="less mid"><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a></td>
					<td width="40" class="less nowrap"><a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a><br/></td>

					<td class="icons style='width:100px;'>
						<ul class="inline">
							<li>
								<a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"url"}"></a>
							</li>
							<li>
								<a href="#" class="icon icon_cart fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;" title="Send to my Download Basket">
								</a>
							</li>
							{if $sabintegrated}
							<li>
								<a class="icon icon_sab fa fa-share" style="text-decoration: none; color: #008ab8;"  href="#" title="Send to queue">
								</a>
							</li>
							{/if}
							{if isset($nzbgetintegrated)}
							<li>
								<a class="icon icon_nzb fa fa-cloud-downloadget" href="#" title="Send to NZBGet">
								</a>
							</li>
							{/if}
						</ul>
					</td>
				</tr>
		{/foreach}
	{/foreach}
</table>

</form>
{/if}
