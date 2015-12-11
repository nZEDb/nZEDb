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

		{if isset($catname) && $catname != ''} in {$catname|escape:"htmlall"}{/if}
	</h1>

	<div class="tvseriesheading">
		<div class="pull-right">
			{if animePicture != ""}<img class="shadow" alt="{$animeTitle} Picture" src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbid}.jpg" />{/if}
		</div>
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
				<a target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$animeAnidbID}" title="View AniDB">View AniDB</a>
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
			{/if}
		</div>
		<table style="width:100%;" class="data highlight icons" id="browsetable">
			<tr>
				<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all"/></th>
				<th>Name</th>
				<th>Category</th>
				<th>Posted</th>
				<th>Size</th>
				<th>Action</th>
			</tr>
			{foreach $animeEpisodeTitles as $result}
				<tr class="{cycle values=",alt"}" id="guid{$result.guid}">
					<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" name="{$seasonnum}" value="{$result.guid}" /></td>
					<td>
						<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
						<div class="resextra">
							<div class="btns">
								{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo rndbtn badge" rel="nfo">Nfo</a>{/if}
								{if $result.haspreview == 1 && $userdata.canpreview == 1}<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$mguid[$m@index]}" title="Screenshot" class="modal_prev rndbtn badge" rel="preview">Preview</a>{/if}
								{if $result.reid > 0}<span class="mediainfo rndbtn badge" title="{$result.guid}">Media</span>{/if}
					<td class="less"><a title="This anime in {$result.category_name}" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}?t={$result.categoryid}">{$result.category_name}</a></td>
					<td class="less mid" width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>
					<td width="40" class="less right">{$result.size|fsize_format:"MB"}</td>
					<td class="icons style='width:100px;'>
						<td class="icon_nzb"><a
							href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.animeTitle|escape:"htmlall"}"><i
								class="fa fa-cloud-download text-muted"
								title="Download NZB"></i></a>
					<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
								class="fa fa-comments-o text-muted"
								title="Comments"></i></a>
					<a href="#" class="icon_cart text-muted"><i
								class="fa fa-shopping-basket" title="Send to my Download Basket"></i></a>
					{if isset($sabintegrated)}
						<a href="#" class="icon_sab text-muted"><i class="fa fa-share"
																   title="Send to my Queue"></i></a>
					{/if}
					{if $weHasVortex}
						<a href="#" class="icon_vortex text-muted"><i
									class="fa fa-share" title="Send to NZBVortex"></i></a>
					{/if}
				</tr>
			{/foreach}
		</table>
	</form>
{/if}
