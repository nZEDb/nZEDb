{if $nodata != ""}
	<h1>View Anime</h1>
	<p>{$nodata}</p>
{else}

<h1>
	{if $isadmin || $ismod}
		<a title="Edit AniDB data" href="{$smarty.const.WWW_TOP}/admin/anidb-edit.php?id={$animeAnidbID}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}">{$animeTitle} </a>
		{if $animeType != ''}<b>({$animeType|escape:"htmlall"})</b>{/if}

	{else}
		{$animeTitle}
	{/if}

	{if $catname != ''} in {$catname|escape:"htmlall"}{/if}
</h1>

<div class="animeseriesheading">
	<div class="col-xs-9">
		<p>
			<br>
				<span class="descinitial">{$animeDescription|escape:"htmlall"|nl2br|magicurl|truncate:"1500":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>
			</br>
		</p>
		<p>
			{if $animeCategories != ''}<b>{$animeCategories}</b><br />{/if}
		</p>
		<p>
			{if $animeDescription|strlen > 1500}<span class="descfull">{$animeDescription|escape:"htmlall"|nl2br|magicurl}</span>{/if}
			{if $animeRating != ''}<br><b>AniDB Rating: {$animeRating|escape:"htmlall"}</b>{/if}
			{if $animeRelated != ''}<br><b>Related Anime:</b> <i>{$animeRelated|escape:"htmlall"}</i>{/if}
			{if $animeSimilar != ''}<br><b>Similar Anime:</b> <i>{$animeSimilar|escape:"htmlall"}</i><br />{/if}
		</p>
	</div>
	<div class="col-xs-3" style="text-align:center">
		{if animePicture != ""}<img class="shadow img-thumbnail" alt="{$animeTitle} Picture" src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbID}.jpg" />{/if}
	</div>

<form id="nzb_multi_operations_form" action="get">
		<div class="container nzb_multi_operations text-right" style="padding-bottom: 4px;">
			View:
			<span><i class="icon-th-list"></i></span>&nbsp;&nbsp;
			<a href="{$smarty.const.WWW_TOP}/browse?t=5070"><i class="icon-align-justify"></i></a>
			&nbsp;&nbsp;
			{if $isadmin || $ismod}
				Admin: <button type="button" class="btn btn-warning btn-sm nzb_multi_operations_edit">Edit</button>
				<button type="button" class="btn btn-danger btn-sm nzb_multi_operations_delete">Delete</button>
			{/if}
		</div>

		{include file='multi-operations.tpl'}
</div>

	<table class="table table-hover table-condensed table-highlight data" id="browsetable">
		{foreach $animeEpisodeTitles as $animeEpno => $animeEpisodeTitle}
			<thead>
				<tr>
					<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2>{$animeEpno}</h2></td>
				</tr>
				<tr>
					<th>Name</th>
					<th style="width:16px"><input id="chkSelectAll{$seasonnum}" type="checkbox" name="{$seasonnum}" class="nzb_check_all_season"><label for="chkSelectAll{$seasonnum}" style="display:none;">Select All</label></th>
					<th style="width:80px;">Category</th>
					<th style="width:60px;text-align:center;">Posted</th>
					<th style="width:80px;text-align:center;">Size</th>
					<th style="width:50px;text-align:center;">Files</th>
					<th style="width:60px;text-align:center;">Stats</th>
					<th style="width:80px;"></th>
				</tr>
			</thead>
			<tbody>
			{foreach $animeEpisodeTitle as $result}
				<tr id="guid{$result.guid}">
					<td>
						<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>

						<div class="resextra">
							<div class="btns pull-left">
								{if $result.nfoid > 0}<span class="label label-default"><a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo " rel="nfo">Nfo</a></span> {/if}
								{if $result.haspreview == 1 && $userdata.canpreview == 1}<span class="label label-default"><a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Screenshot of {$result.searchname|escape:"htmlall"}" class="modal_prev " rel="preview">Preview</a></span> {/if}
								{if $result.tvairdate != ""}<span class="label label-default" title="{$result.tvtitle} Aired on {$result.tvairdate|date_format}">Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span> {/if}
								{if $result.reid > 0}<span class="mediainfo label label-default" title="{$result.guid}">Media</span>{/if}
							</div>

							{if $isadmin || $ismod}
								<div class="admin pull-right">
									<span class="label label-warning"><a class="" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a></span> <span class="label label-danger"><a class=" confirm_action" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Del</a></span>
								</div>
							{/if}
						</div>

					</td>
					<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" name="{$seasonnum}" value="{$result.guid}"></td>
					<td style="text-align:center;"><a title="This anime in {$result.category_name}" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}?t={$result.categoryid}">{$result.category_name}</a></td>
					<td style="text-align:center;" title="{$result.postdate}">{$result.postdate|timeago}</td>
					<td style="text-align:center;">{$result.size|fsize_format:"MB"}{if $result.completion > 0}<br>{if $result.completion < 100}<span class="warning">{$result.completion}%</span>{else}{$result.completion}%{/if}{/if}</td>
					<td style="text-align:center;">
						<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/magnifier.png" alt="{$result.guid}" class="tooltip">
							</div>
						{/if}
					</td>
					<td style="text-align:center;"><a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a><br/>{$result.grabs} grab{if $result.grabs != 1}s{/if}</td>
					<td class="icons" style="text-align:center;">
						<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}">&nbsp;</a></div>
						{if $sabintegrated}<div class="icon icon_sab" title="Send to my Queue"></div>{/if}
						<div class="icon icon_cart" title="Add to Cart"></div>
					</td>
				</tr>
			{/foreach}
			</tbody>
		{/foreach}
	</table>
</form>
{/if}