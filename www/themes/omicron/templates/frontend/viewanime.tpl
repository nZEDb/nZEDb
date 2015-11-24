{if isset($nodata) && $nodata !=''}
<div class="header">
	<h2>View > <strong>Anime</strong></h2>
	<p>{$nodata}</p>
</div>
{else}
<div class="header">
	<h2>View > <strong>Anime</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Anime
		</ol>
	</div>
</div>
<h1>
	{$animeTitle}
	{if isset($isadmin)}
		<a class="btn btn-xs btn-warning"
		   title="Edit AniDB data"
		   href="{$smarty.const.WWW_TOP}/admin/anidb-edit.php?id={$animeAnidbid}&amp;
					from={$smarty.server.REQUEST_URI|escape:"url"}">Edit</a>
	{/if}
</h1>
<div>
	{if animePicture != ''}
		<center>
			<img class="shadow img img-polaroid" alt="{$animeTitle} Picture"
				 src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbid}.jpg"/>
		</center>
		<br/>
	{/if}
	<p>
		{if $animeCategories != ''}<b>{$animeCategories}</b><br/>{/if}
		<span class="descinitial">{$animeDescription|escape:"htmlall"|nl2br|magicurl|truncate:"1500":" </span><a class=\"descmore\" href=\"#\"> more...</a>"}
			{if $animeDescription|strlen > 1500}<span
					class="descfull">{$animeDescription|escape:"htmlall"|nl2br|magicurl}</span>{else}</span>{/if}
	</p>
	<p>
		{if $animeRating != ''}<br><b>AniDB Rating: {$animeRating|escape:"htmlall"}</b>{/if}
		{if $animeRelated != ''}<br><i>Related Anime: {$animeRelated|escape:"htmlall"}</i><br/>{/if}
	</p>
</div>
<center>
	<div class="btn-group">
		<a class="btn btn-sm btn-default"
		   href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$animeAnidbid}"
		   title="View AniDB">View AniDB</a>
		<a class="btn btn-sm btn-default"
		   href="{$smarty.const.WWW_TOP}/rss?anidb={$animeAnidbid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS
			feed for this Anime <i class="fa fa-rss"></i></a>
	</div>
</center>
<form id="nzb_multi_operations_form" action="get">
	<div class="well well-small">
		<div class="nzb_multi_operations">
			With Selected:
			<div class="btn-group">
				<input type="button" class="nzb_multi_operations_download btn btn-sm btn-success"
					   value="Download NZBs"/>
				<input type="button" class="nzb_multi_operations_cart btn btn-sm btn-info" value="Add to Cart"/>
				{if isset($sabintegrated)}
					<input type="button" class="nzb_multi_operations_sab btn btn-sm btn-primary" value="Send to Queue"/>
				{/if}
				{if isset($nzbgetintegrated)}
					<input type="button" class="nzb_multi_operations_nzbget btn btn-sm btn-primary"
						   value="Send to NZBGet"/>
				{/if}
			</div>
			{if isset($isadmin)}
				<div class="pull-right">
					Admin:
					<div class="btn-group">
						<input type="button" class="nzb_multi_operations_edit btn btn-sm btn-warning" value="Edit"/>
						<input type="button" class="nzb_multi_operations_delete btn btn-sm btn-danger" value="Delete"/>
					</div>
				</div>
			{/if}
		</div>
	</div>
	<div class="row">
		<div class="col-xlg-12 portlets">
			<div class="panel panel-default">
				<div class="panel-body pagination2">
					<table style="width:100%;" class="data table table-condensed table-striped table-responsive table-hover"
						   id="browsetable">
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
									<td class="check"><input id="chk{$result.guid|substr:0:7}"
															 type="checkbox" class="nzb_check"
															 value="{$result.guid}"/></td>
									<td>
										<a title="View details"
										   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
										<div>
											<div>
												{if $result.nfoid > 0}<span class="label label-default"><a
															href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
															class="text-muted">NFO</a></span>{/if}
												{if $result.haspreview == 1 && $userdata.canpreview == 1}<a
													href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
													name="name{$result.guid}"
													title="Screenshot of {$result.animeTitle|escape:"htmlall"}"
													class="label label-default" rel="preview">Preview</a>{/if}
												<span class="label label-default">{$result.grabs}
													Grab{if $result.grabs != 1}s{/if}</span>
												{if $result.reid > 0}<span class="mediainfo label label-default"
																		   title="{$result.guid}">Media</span>{/if}
											</div>
										</div>
									</td>
									<td><span class="label label-default">{$result.category_name}</span></td>
									<td width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>
									<td>{$result.size|fsize_format:"MB"}</td>
									<td class="icon_nzb"><a
												href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.animeTitle|escape:"htmlall"}"><i
													class="fa fa-download text-muted"
													title="Download NZB"></i></a>
										<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
													class="fa fa-comments-o text-muted"
													title="Comments"></i></a>
										<a href="#" class="icon_cart text-muted"><i
													class="fa fa-shopping-basket" title="Send to my Cart"></i></a>
										{if isset($sabintegrated)}
											<a href="#" class="icon_sab text-muted"><i class="fa fa-send-o"
																					   title="Send to my Queue"></i></a>
										{/if}
										{if $weHasVortex}
											<a href="#" class="icon_vortex text-muted"><i
														class="fa fa-send-o" title="Send to NZBVortex"></i></a>
										{/if}
									</td>
								</tr>
							{/foreach}
					</table>
</form>
{/if}