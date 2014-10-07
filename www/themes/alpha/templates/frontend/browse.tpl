{if {$site->adbrowse} != ''}
	<div class="container" style="width:500px;">
		<fieldset class="adbanner div-center">
			<legend class="adbanner">Advertisement</legend>
			{$site->adbrowse}
		</fieldset></div>
	<br>
{/if}
{* {if $covergrp != ''}
<div class="accordion" id="searchtoggle">
	<div class="accordion-group">
		<div class="accordion-heading">
			<a class="accordion-toggle" data-toggle="collapse" data-parent="#searchtoggle" href="#searchfilter">
				<i class="icon-search"></i>
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
		<div class="container nzb_multi_operations" style="text-align:right;margin-bottom:5px;">
			{if $covgroup != ''}View:
				<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">
					<i class="icon-th-list"></i>
				</a>
				&nbsp;&nbsp;
				<span>
					<i class="icon-align-justify"></i>
				</span>
			{/if}
			{if $isadmin || $ismod}
				&nbsp;&nbsp;
				Admin:
				<button type="button" class="btn btn-warning btn-sm nzb_multi_operations_edit">Edit</button>
				<button type="button" class="btn btn-danger btn-sm nzb_multi_operations_delete">Delete</button>
			{/if}
		</div>
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
						<a title="Sort Descending" href="{$orderbyname_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyname_asc}"><i class="icon-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">category<br>
						<a title="Sort Descending" href="{$orderbycat_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbycat_asc}"><i class="icon-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">posted<br>
						<a title="Sort Descending" href="{$orderbyposted_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyposted_asc}"><i class="icon-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">size<br>
						<a title="Sort Descending" href="{$orderbysize_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbysize_asc}"><i class="icon-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">files<br>
						<a title="Sort Descending" href="{$orderbyfiles_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyfiles_asc}"><i class="icon-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">stats<br>
						<a title="Sort Descending" href="{$orderbystats_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbystats_asc}"><i class="icon-chevron-up"></i></a>
					</th>
					<th style="vertical-align:top;text-align:center;">action</th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$results item=result}
				<tr class="{if $lastvisit|strtotime<$result.adddate|strtotime}success{/if}" id="guid{$result.guid}">
					<td class="check" style="width:26px;text-align:center;white-space:nowrap;">
						<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.guid}">
					</td>
					<td class="item" style="width:100%;text-align:left;">
						<label for="chk{$result.guid|substr:0:7}">
							<a
								class="title"
								title="View details"
								href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}"
							>{$result.searchname|escape:"htmlall"|wordwrap:80:"\n":true}</a>
						</label>
						<div class="resextra">
							{if $result.passwordstatus == 1}
								<span class="icon-stack" title="Potentially Passworded"><i class="icon-check-empty icon-stack-base"></i><i class="icon-unlock-alt"></i></span>
							{elseif $result.passwordstatus == 2}
								<span class="icon-stack" title="Broken Post"><i class="icon-check-empty icon-stack-base"></i><i class="icon-unlink"></i></span>
							{elseif $result.passwordstatus == 10}
								<span class="icon-stack" title="Passworded Archive"><i class="icon-check-empty icon-stack-base"></i><i class="icon-lock"></i></span>
							{/if}
							{if $result.videostatus > 0}
								<a
									class="label label-default model_prev"
									href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}"
									title="This release has a video preview"
									rel="preview"
								><i class="icon-youtube-play"></i></a>
							{/if}
							{if $result.nfoid > 0}
								<a
									class="label label-default modal_nfo"
									href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
									title="View Nfo" rel="nfo"
								><i class="icon-info-sign"></i></a>
							{/if}
							{if $result.imdbid > 0}
								<a
									class="label label-default modal_imdb"
									href="#" name="name{$result.imdbid}"
									title="View movie info"
									rel="movie"
								><i class="icon-film"></i></a>
							{/if}
							{if $result.musicinfoid > 0}
								<a
									class="label label-default modal_music"
									href="#"
									name="name{$result.musicinfoid}"
									title="View music info"
									rel="music"
								><i class="icon-music"></i></a>
							{/if}
							{if $result.consoleinfoid > 0}
								<a
									class="label label-default modal_console"
									href="#"
									name="name{$result.consoleinfoid}"
									title="View console info"
									rel="console"
								><i class="icon-off"></i></a>
							{/if}
							{if $result.haspreview == 1 && $userdata.canpreview == 1}
								<a
									class="label label-default modal_prev"
									href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Screenshot of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="icon-camera"></i></a>
							{/if}
							{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
								<a
									class="label label-default modal_prev"
									href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
									name="name{$result.guid}"
									title="Sample of {$result.searchname|escape:"htmlall"}"
									rel="preview"
								><i class="icon-picture"></i></a>
							{/if}
							{if $result.rageid > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/series/{$result.rageid}"
									title="View all episodes"
								><i class="icon-bookmark"></i></a>
							{/if}
							{if $result.anidbid > 0}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}"
									title="View all anime"
								><i class="icon-font"></i></a>
							{/if}
							{if $result.tvairdate != ""}
								<span
									class="label label-default seriesinfo"
									title="{$result.guid}"
								>Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>
							{/if}
							{if $result.reid > 0}
								<span
									class="label label-default mediainfo"
									title="{$result.guid}"
								><i class="icon-list-alt"></i></span>
							{/if}
							{if $result.preid > 0}
								<span
									class="label label-default preinfo rndbtn"
									title="{$result.preid}"
								><i class="icon-eye-open"></i></span>
							{/if}
							{if $result.group_name != ""}
								<a
									class="label label-default"
									href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}"
									title="Browse {$result.group_name}"
								><i class="icon-share-alt"></i></a>
							{/if}
							{release_flag($result.searchname, browse)}
						</div>
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;">
						<small
							><a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categoryid}">
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
						<i class="icon-file"></i>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/magnifier.png" alt="{$result.guid}">
							</div>
						{/if}
					</td>
					<td style="width:auto;text-align:center;white-space:nowrap;">
						<a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments}</a>
						<i class="icon-comments-alt"></i>
						<br/>
						{$result.grabs}
						<i class="icon-download-alt"></i>
					</td>
					<td class="icons" style="width:80px;text-align:center;white-space:nowrap;">
						<div class="icon icon_nzb">
							<a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}"></a>
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