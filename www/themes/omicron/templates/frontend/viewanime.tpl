{if $nodata != ""}
	<div class="header">
		{assign var="catsplit" value=">"|explode:$catname}
		<h2>View > <strong>Anime</strong></h2>
		<p>{$nodata}</p>
	</div>
{else}

<div class="header">
	{assign var="catsplit" value=">"|explode:$catname}
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
					href="{$smarty.const.WWW_TOP}/admin/anidb-edit.php?id={$animeanidbid}&amp;
					from={$smarty.server.REQUEST_URI|escape:"url"}">Edit</a>
				{/if}
</h1>
{if $catname != ''}<span class="text-info h5">Current category shown: {$catname|escape:"htmlall"}</span>{/if}
<div>
	{if animePicture != ""}
		<center>
			<img class="shadow img img-polaroid" alt="{$animeTitle} Picture"
			src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbID}.jpg"/>
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
		   href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$animeAnidbID}"
		   title="View AniDB">View AniDB</a>
		{if $animetvdbid > 0}<a class="btn btn-sm btn-default" target="_blank"
								href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$animetvdbid}"
								title="View TheTVDB">View TheTVDB</a> | {/if}
		{if $animeimdbid > 0}<a class="btn btn-sm btn-default" target="_blank"
								href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$animeimdbid}"
								title="View IMDb">View IMDb</a> | {/if}
		<a class="btn btn-sm btn-default"
		   href="{$smarty.const.WWW_TOP}/rss?anidb={$animeanidbid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS
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
						{foreach $animeEpisodeTitles as $animeEpno => $animeEpisodeTitle}
							<tr>
								<td style="padding-top:15px;" colspan="10"><h3>{$animeEpno}</h2></td>
							</tr>
							<tr>
								<th>Name</th>
								<th></th>
								<th>Category</th>
								<th>Posted</th>
								<th>Size</th>
								<th>Action</th>
							</tr>
							{foreach $animeEpisodeTitle as $result}
								<tr class="{cycle values=",alt"}" id="guid{$result.guid}">
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
													title="Screenshot of {$result.searchname|escape:"htmlall"}"
													class="label label-default" rel="preview">Preview</a>{/if}
												<span class="label label-default">{$result.grabs}
													Grab{if $result.grabs != 1}s{/if}</span>
												{if $result.tvairdate != ""}<span class="label label-success"
																				  title="{$result.tvtitle} Aired on {$result.tvairdate|date_format}">
													Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>{/if}
												{if $result.reid > 0}<span class="mediainfo label label-default"
																		   title="{$result.guid}">Media</span>{/if}
											</div>
										</div>
									</td>
									<td class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox"
															 class="nzb_check" name="{$seasonnum}"
															 value="{$result.guid}"/></td>
									<td><span class="label label-default">{$result.category_name}</span></td>
									<td width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>
									<td>{$result.size|fsize_format:"MB"}</td>
									<td class="icons" style='width:100px;'>
										<a title="Download Nzb"
										   href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}"><i
													class="fa fa-download text-muted"></i></a>
										<a class="fa fa-shopping-cart icon_cart text-muted" href="#"
										   title="Add to Cart">
										</a>
										{if isset($sabintegrated)}
											<a class="icon icon_sab" href="#" title="Send to Sab">
												<img class="icon icon_sab" alt="Send to my Sabnzbd"
													 src="{$smarty.const.WWW_TOP}/themes/omicron/images/icons/sabup.png">
											</a>
										{/if}
										{if $weHasVortex}
											<a class="icon icon_nzbvortex" href="#" title="Send to NZBVortex">
												<img class="icon icon_nzbvortex" alt="Send to my NZBVortex"
													 src="{$smarty.const.WWW_TOP}/themes/omicron/images/icons/vortex/bigsmile.png">
											</a>
										{/if}
										{if isset($nzbgetintegrated)}<a class="icon icon_nzbget" title="Send to NZBGet"
																		href="#"><img class="icon icon_nzbget"
																					  alt="Send to NZBget"
																					  src="{$smarty.const.WWW_TOP}/themes/omicron/images/icons/nzbgetup.png">
											</a>{/if}
										{if isset($isadmin)}
											<br/>
											<a class="label label-warning"
											   href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}"
											   title="Edit Release">Edit</a>
											<a class="label label-danger"
											   href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}"
											   title="Delete Release">Delete</a>
										{/if}
									</td>
								</tr>
							{/foreach}
						{/foreach}
					</table>
</form>
{/if}
