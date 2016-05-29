{include file='elements/ads.tpl' ad=$site->adbrowse}
{* {if $covergrp != ''}
<div class="accordion" id="searchtoggle">
	<div class="accordion-group">
		<div class="accordion-heading">
			<a class="accordion-toggle" data-toggle="collapse" data-parent="#searchtoggle" href="#searchfilter">
				<i class="fa fa-search"></i>
				Search Filter
			</a>
		</div>
		<div id="searchfilter" class="accordion-body collapse">
			<div class="accordion-inner">
				{include file='search-filter.tpl'}
			</div>
		</div>
	</div>
</div>
{/if} *}
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		{include file='elements/admin-buttons-browse.tpl'}
		{include file='multi-operations.tpl'}
		<table class="table table-striped table-bordered table-condensed table-hover data" id="browsetable">
			<thead>
				<tr>
					<th>
						<div class="icon">
							<input id="chkSelectAll" type="checkbox" class="nzb_check_all">
						</div>
					</th>
					<th style="vertical-align:top;">name
						<a title="Sort Descending" href="{$orderbyname_desc}"><i class="fa fa-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyname_asc}"><i class="fa fa-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">category<br>
						<a title="Sort Descending" href="{$orderbycat_desc}"><i class="fa fa-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbycat_asc}"><i class="fa fa-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">posted<br>
						<a title="Sort Descending" href="{$orderbyposted_desc}"><i class="fa fa-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyposted_asc}"><i class="fa fa-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">size<br>
						<a title="Sort Descending" href="{$orderbysize_desc}"><i class="fa fa-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbysize_asc}"><i class="fa fa-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">files<br>
						<a title="Sort Descending" href="{$orderbyfiles_desc}"><i class="fa fa-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyfiles_asc}"><i class="fa fa-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">stats<br>
						<a title="Sort Descending" href="{$orderbystats_desc}"><i class="fa fa-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbystats_asc}"><i class="fa fa-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">action</th>
				</tr>
			</thead>
			<tbody>
			{foreach $results as $result}
				<tr class="{if $lastvisit|strtotime<$result.adddate|strtotime}success{/if}" id="guid{$result.guid}">
					<td class="check" style="width:26px;text-align:center;white-space:nowrap;">
						<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.guid}">
					</td>
					<td class="item" style="width:100%;text-align:left;">
						<label for="chk{$result.guid|substr:0:7}">
							<a
								class="title"
								title="View details"
								href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
							>{$result.searchname|escape:"htmlall"|truncate:70}</a>{if $result.failed > 0} <i class="fa fa-exclamation-circle" style="color: red" title="This release has failed to download for some users"></i>{/if}
						</label>
						<div class="resextra">
							{if $result.passwordstatus == 1}
								<span class="fa fa-stack" title="Potentially Passworded"><i class="fa fa-square-o fa-stack-base"></i><i class="fa fa-unlock-alt"></i></span>
							{elseif $result.passwordstatus == 2}
								<span class="fa fa-stack" title="Broken Post"><i class="fa fa-square-o fa-stack-base"></i><i class="fa fa fa-chain-broken"></i></span>
							{elseif $result.passwordstatus == 10}
								<span class="fa fa-stack" title="Passworded Archive"><i class="fa fa-square-o fa-stack-base"></i><i class="fa fa-lock"></i></span>
							{/if}
							{if $result.videostatus > 0}
								<a
									class="label label-default model_prev"
									href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
									title="This release has a video preview"
									rel="preview"
								><i class="fa fa-youtube-play"></i></a>
							{/if}
							{if $result.nfoid > 0}
								<a
									class="label label-default modal_nfo"
									href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
									title="View Nfo" rel="nfo"
								><i class="fa fa-info"></i></a>
							{/if}
							{if $result.imdbid > 0}
								<a
									class="label label-default modal_imdb"
									href="#" name="name{$result.imdbid}"
									title="View movie info"
									rel="movie"
								><i class="fa fa-film"></i></a>
							{/if}
							{if $result.musicinfo_id > 0}
								<a
									class="label label-default modal_music"
									href="#"
									name="name{$result.musicinfo_id}"
									title="View music info"
									rel="music"
								><i class="fa fa-music"></i></a>
							{/if}
							{if $result.consoleinfo_id > 0}
								<a
									class="label label-default modal_console"
									href="#"
									name="name{$result.consoleinfo_id}"
									title="View console info"
									rel="console"
								><i class="fa fa-power-off"></i></a>
							{/if}
							{if $result.haspreview == 1 && $userdata.canpreview == 1}
								<a
									class="label label-default modal_prev"
									href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Screenshot of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="fa fa-camera"></i></a>
							{/if}
							{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
								<a
									class="label label-default modal_prev"
									href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Sample of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="fa fa-picture-o"></i></a>
							{/if}
							{if $result.videos_id > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/series/{$result.videos_id}"
									title="View all episodes"
								><i class="fa fa-bookmark"></i></a>
							{/if}
							{if $result.anidbid > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}"
									title="View all anime"
								><i class="fa fa-font"></i></a>
							{/if}
							{if $result.firstaired != ""}
								<span
									class="label label-default seriesinfo"
									title="{$result.guid}"
								>Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>
							{/if}
							{if $result.reid > 0}
								<span
									class="label label-default mediainfo"
									title="{$result.guid}"
								><i class="fa fa-list-alt"></i></span>
							{/if}
							{if $result.predb_id > 0}
								<span
									class="label label-default preinfo rndbtn"
									title="{$result.predb_id}"
								><i class="fa fa-eye"></i></span>
							{/if}
							{if !empty($result.group_name)}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}"
									title="Browse {$result.group_name}"
								><i class="fa fa-share-alt"></i></a>
							{/if}
							{release_flag($result.searchname, browse)}
							{if $result.failed > 0}<span class="label label-default">
								<i class ="fa fa-thumbs-o-up"></i> {$result.grabs} Grab{if $result.grabs != 1}s{/if} / <i class ="fa fa-thumbs-o-down"></i> {$result.failed} Failed Download{if $result.failed != 1}s{/if}
								</span>
							{/if}
						</div>
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;">
						<small
							><a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categories_id}">
								<b>{$result.category_name}</b>
							</a>
						</small>
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;" title="{$result.postdate}">
						{$result.postdate|timeago}
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;">
						{$result.size|fsize_format:"MB"}
						{if $result.completion > 0}<br>
							{if $result.completion < 100}
								<span class="label label-warning">{$result.completion}%</span>
							{else}
								<span class="label label-success">{$result.completion}%</span>
							{/if}
						{/if}
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;">
						<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
						<i class="fa fa-file"></i>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/magnifier.png" alt="{$result.guid}">
							</div>
						{/if}
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;">
						<a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments}</a>
						<i class="fa fa-comments-o"></i>
						<br/>
						{$result.grabs}
						<i class="fa fa-download"></i>
					</td>
					<td class="icons" style="width:80px;text-align:center;white-space:nowrap;">
						<div class="icon icon_nzb">
							<a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}"></a>
						</div>
						{if $sabintegrated}
							<div class="icon icon_sab" title="Send to my Queue"></div>
						{/if}
						<div class="icon icon_cart" title="Add to Cart"></div>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		{if $results|@count > 10}
			<div class="nzb_multi_operations">
				{include file='multi-operations.tpl'}
			</div>
		{/if}
	</form>
{else}
	<div class="alert alert-warning" style="vertical-align:middle;">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong> ಠ_ಠ </strong>There doesn't seem to be any releases found.
	</div>
{/if}
