{if $nodata != ""}
	<h1>View TV Series</h1>
	<p>{$nodata}</p>
{else}

	{$pager = false}

	<div class="tvseriesheading container-fluid">
		<div class="row">
			<div class="col-xs-9">
				<h1>
					{if $isadmin || $ismod}
						<a title="Edit Video data" href="{$smarty.const.WWW_TOP}/admin/show-edit.php?id={$show.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}">{$show.title} </a>
					{else}
						{$show.title}
					{/if}

					{if $catname != ''} in {$catname|escape:"htmlall"}{/if}
				</h1>
			</div>
			<form id="col-xs-3 nzb_multi_operations_form" action="get">
				View:
				<span><i class="icon-th-list"></i></span>&nbsp;&nbsp;
				<a href="{$smarty.const.WWW_TOP}/browse?t={$category}"><i class="icon-align-justify"></i></a>
				&nbsp;&nbsp;
				{if $isadmin || $ismod}
					Admin: <button type="button" class="btn btn-warning btn-sm nzb_multi_operations_edit">Edit</button>
					<button type="button" class="btn btn-danger btn-sm nzb_multi_operations_delete">Delete</button>
				{/if}
		</div>
		<div class="row">
			<div class="col-xs-9 text-left" style="margin:10px 0px 0px 0px">
				<p>
					<span class="descinitial">{$seriessummary|escape:"htmlall"|nl2br|magicurl|truncate:"1500":" <a class=\"descmore\" href=\"#\">more...</a>"}</span>   {if $seriessummary|strlen > 1500}<span class="descfull">{$seriessummary|escape:"htmlall"|nl2br|magicurl}</span>{/if}
				</p>
			</div>
			<div class="col-xs-3 text-center">
				{if $show.image != 0}<img class="shadow img-thumbnail" alt="{$show.title} Logo" src="{$smarty.const.WWW_TOP}/covers/tvshows/{$show.id}.jpg">{/if}
			</div>
		</div>
		<div class="row">
			<div class="col-xs-6 text-left" style="margin:5px 0px 0px 0px">
			<b>My Shows</b>:
			{if $myshows.id != ''}
				&nbsp;[ <a href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="edit" name="series{$show.id}" title="Edit">Edit</a> ]
				&nbsp;[ <a href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="remove" name="series{$show.id}" title="Remove from My Shows">Remove</a> ]
			{else}
				&nbsp;[ <a href="{$smarty.const.WWW_TOP}/myshows/add/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="add" name="series{$show.id}" title="Add to My Shows">Add</a> ]
			{/if}
			</div>
			<div class="col-xs-6 text-right" style="margin:5px 0px 0px 0px">
				{include file='multi-operations.tpl'}
			</div>
		</div>

	<a id="latest"></a>
	<table class="table table-hover table-condensed table-highlight data" id="browsetable">
		{foreach $seasons as $seasonnum => $season}
		<thead>
		<tr>
			{if empty($seasonnum)}{$seasonnum = 'Packs'}{/if}
			<th colspan="10"><h2>Season {$seasonnum}</h2></th>
		</tr>
		<tr>
			<th>Ep</th>
			<th>Name</th>
			<th style="width:16px"><input id="chkSelectAll{$seasonnum}" type="checkbox" name="{$seasonnum}" class="nzb_check_all_season"><label for="chkSelectAll{$seasonnum}" style="display:none;">Select All</label></th>
			<th style="width:60px;">Category</th>
			<th style="width:60px;text-align:center;">Posted</th>
			<th style="width:80px;text-align:center;">Size</th>
			<th style="width:50px;text-align:center;">Files</th>
			<th style="width:60px;text-align:center;">Stats</th>
			<th style="width:80px;"></th>
		</tr>
		</thead>
		<tbody>
		{foreach $season as $episodes}
			{foreach $episodes as $result}
				<tr id="guid{$result.guid}">
					{if $result@total>1 && $result@index == 0}
						<td rowspan="{$result@total}">{$episodes@key}</td>
					{else if $result@total == 1}
						<td>{$episodes@key}</td>
					{/if}
					<td>
						<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>

						<div class="resextra">
							<div class="btns pull-left">
								{if $result.nfoid > 0}<span class="label label-default"><a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo " rel="nfo">Nfo</a></span> {/if}
								{if $result.haspreview == 1 && $userdata.canpreview == 1}<span class="label label-default"><a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Screenshot of {$result.searchname|escape:"htmlall"}" class="modal_prev " rel="preview">Preview</a></span> {/if}
								{if $result.firstaired != ""}<span class="label label-default" title="{$result.title} Aired on {$result.firstaired|date_format}">Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span> {/if}
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
					<td style="text-align:center;"><a title="This series in {$result.category_name}" href="{$smarty.const.WWW_TOP}/series/{$result.videos_id}?t={$result.categoryid}">{$result.category_name}</a></td>
					<td style="text-align:center;" title="{$result.postdate}">{$result.postdate|timeago}</td>
					<td style="text-align:center;">{$result.size|fsize_format:"MB"}{if $result.completion > 0}<br>{if $result.completion < 100}<span class="warning">{$result.completion}%</span>{else}{$result.completion}%{/if}{/if}</td>
					<td style="text-align:center;">
						<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/magnifier.png" alt="{$result.guid}" class="tooltip">
							</div>
						{/if}
					</td>
					<td style="text-align:center;"><a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a><br/>{$result.grabs} grab{if $result.grabs != 1}s{/if}</td>
					<td class="icons text-center">
						<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}">&nbsp;</a></div>
						{if $sabintegrated}<div class="icon icon_sab" title="Send to my Queue"></div>{/if}
						<div class="icon icon_cart" title="Add to Cart"></div>
					</td>
				</tr>
			{/foreach}
		{/foreach}
		{/foreach}
		</tbody>
	</table>

	</form>
{/if}
