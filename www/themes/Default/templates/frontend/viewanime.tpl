<h1>
{if $isadmin}
	<a title="Edit AniDB data" href="{$smarty.const.WWW_TOP}/admin/anidb-edit.php?id={$animeAnidbid}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}">{$animeTitle} </a>

{else}
	{$animeTitle}
{/if}

{if $catname != ''} in {$catname|escape:"htmlall"}{/if}
</h1>

<div class="tvseriesheading">
	{if $animeType != ''}<i>({$animeType|escape:"htmlall"})</i>{/if}
	{if animePicture != ""}<img class="shadow" alt="{$animeTitle} Picture" src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbid}.jpg" />{/if}
	<p>
		{if $animeCategories != ''}<b>{$animeCategories}</b><br />{/if}
		<span class="descinitial">{$animeDescription|escape:"htmlall"|nl2br|magicurl|truncate:"1500":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>
		{if $animeDescription|strlen > 1500}<span class="descfull">{$animeDescription|escape:"htmlall"|nl2br|magicurl}</span>{/if}
		{if $animeRating != ''}<br><b>AniDB Rating: {$animeRating|escape:"htmlall"}</b>{/if}
		{if $animeRelated != ''}<br><i>Related Anime: {$animeRelated|escape:"htmlall"}</i><br />{/if}
	</p>

</div>

<form id="nzb_multi_operations_form" action="get">

<div class="nzb_multi_operations">
	<div style="padding-bottom:10px;" >
		<a target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$animeAnidbid}" title="View in AniDB">View in AniDB</a> |
		<a href="{$smarty.const.WWW_TOP}/rss?anidb={$animeAnidbid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS feed for this Anime</a>
	</div>
	<small>With Selected:</small>
	<input type="button" class="nzb_multi_operations_download" value="Download NZBs" />
	<input type="button" class="nzb_multi_operations_cart" value="Add to Cart" />
	{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab" value="Send to my Queue" />{/if}
	{if $isadmin}
	&nbsp;&nbsp;
	<input type="button" class="nzb_multi_operations_edit" value="Edit" />
	<input type="button" class="nzb_multi_operations_delete" value="Del" />
	{/if}
</div>


<table style="width:100%;" class="data highlight icons" id="browsetable">
	{foreach $animeEpisodeTitles as $animeEpno => $animeEpisodeTitle}
		<tr>
			<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2>{$animeEpno}</h2></td>
		</tr>
		<tr>
			<th>Name</th>
			<th></th>
			<th>Category</th>
			<th style="text-align:center;">Posted</th>
			<th>Size</th>
			<th>Files</th>
			<th>Stats</th>
			<th></th>
		</tr>
			{foreach $animeEpisodeTitle as $result}
				<tr class="{cycle values=",alt"}" id="guid{$result.guid}">
					<td>
						<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>

						<div class="resextra">
							<div class="btns">
								{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo rndbtn" rel="nfo">Nfo</a>{/if}
								{if $result.tvairdate != ""}<span class="rndbtn" title="{$result.tvtitle} Aired on {$result.tvairdate|date_format}">Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>{/if}
							</div>

							{if $isadmin}
							<div class="admin">
								<a class="rndbtn" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a> <a class="rndbtn confirm_action" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Del</a>
							</div>
							{/if}
						</div>
					</td>
					<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" name="{$seasonnum}" value="{$result.guid}" /></td>
					<td class="less"><a title="This anime in {$result.category_name}" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}?t={$result.categoryid}">{$result.category_name}</a></td>
					<td class="less mid" width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>
					<td width="40" class="less right">{$result.size|fsize_format:"MB"}{if $result.completion > 0}<br />{if $result.completion < 100}<span class="warning">{$result.completion}%</span>{else}{$result.completion}%{/if}{/if}</td>
					<td class="less mid"><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a></td>
					<td width="40" class="less" nowrap="nowrap"><a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a><br/>{$result.grabs} grab{if $result.grabs != 1}s{/if}</td>
					<td class="icons">
						<div class="icon icon_nzb"><a title="Download NZB" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}">&nbsp;</a></div>
						{if $sabintegrated}<div class="icon icon_sab" title="Send to my Queue"></div>{/if}
						<div class="icon icon_cart" title="Add to Cart"></div>
					</td>
				</tr>
		{/foreach}
	{/foreach}
</table>

</form>
