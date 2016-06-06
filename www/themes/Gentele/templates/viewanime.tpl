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
		<div class="well well-sm">
			{$animeTitle}
			{if isset($isadmin)}
				<a class="btn btn-xs btn-warning" title="Edit AniDB data"
				   href="{$smarty.const.WWW_TOP}/admin/anidb-edit.php?id={$animeAnidbid}&amp; from={$smarty.server.REQUEST_URI|escape:"url"}">Edit</a>
			{/if}
		</div>
	</h1>
	<div class="well well-sm">
		{if animePicture != ''}
			<div style="text-align: center;">
				<img class="shadow img img-polaroid" alt="{$animeTitle} Picture"
					 src="{$smarty.const.WWW_TOP}/covers/anime/{$animeAnidbid}.jpg"/>
			</div>
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
	<div style="text-align: center;">
		<div class="btn-group">
			<a class="btn btn-sm btn-default"
			   href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$animeAnidbid}"
			   title="View AniDB">View AniDB</a>
			<a class="btn btn-sm btn-default"
			   href="{$smarty.const.WWW_TOP}/rss?anidb={$animeAnidbid}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS
				feed for this Anime <i class="fa fa-rss"></i></a>
		</div>
	</div>
	<form id="nzb_multi_operations_form" action="get">
		<div class="well well-sm">
			<div class="nzb_multi_operations">
				With Selected:
				<div class="btn-group">
					<button type="button"
							class="nzb_multi_operations_download btn btn-sm btn-success"
							data-toggle="tooltip" data-placement="top" title data-original-title="Download NZBs">
						<i class="fa fa-cloud-download"></i></button>
					<button type="button"
							class="nzb_multi_operations_cart btn btn-sm btn-info"
							data-toggle="tooltip" data-placement="top" title
							data-original-title="Send to my Download Basket">
						<i class="fa fa-shopping-basket"></i></button>

					{if isset($sabintegrated) && $sabintegrated !=""}
						<button type="button"
								class="nzb_multi_operations_sab btn btn-sm btn-primary"
								data-toggle="tooltip" data-placement="top" title data-original-title="Send to Queue">
							<i class="fa fa-share"></i></button>
					{/if}
					{if isset($isadmin)}
						<input type="button"
							   class="nzb_multi_operations_edit btn btn-sm btn-warning"
							   value="Edit"/>
						<input type="button"
							   class="nzb_multi_operations_delete btn btn-sm btn-danger"
							   value="Delete"/>
					{/if}
				</div>
			</div>
			<div class="row">
				<div class="col-xlg-12 portlets">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							<table style="width:100%;" class="data table table-striped responsive-utilities jambo-table"
								   id="browsetable">
								<tr>
									<th><input id="check-all" type="checkbox" class="flat-all"/> Select All</th>
									<th>Name</th>
									<th>Category</th>
									<th>Posted</th>
									<th>Size</th>
									<th>Action</th>
								</tr>
								{foreach $animeEpisodeTitles as $result}
									<tr class="{cycle values=",alt"}">
										<td>
											<input
												id="guid{$result.guid}"
												type="checkbox"
												class="flat"
												value="{$result.guid}"/>
										</td>
										<td>
											<a title="View details"
											   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
											<div>
												<div>
													{if $result.nfoid > 0}<span><a
																href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
																class="modal_nfo label label-primary text-muted">NFO</a>
														</span>{/if}
													{if $result.haspreview == 1 && $userdata.canpreview == 1}<a
														href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
														name="name{$result.guid}"
														title="Screenshot of {$result.animeTitle|escape:"htmlall"}"
														class="label label-primary" rel="preview">Preview</a>{/if}
													<span class="label label-primary">{$result.grabs}
														Grab{if $result.grabs != 1}s{/if}</span>
													{if $result.reid > 0}<span class="mediainfo label label-primary"
																			   title="{$result.guid}">Media</span>{/if}
												</div>
											</div>
										</td>
										<td><span class="label label-primary">{$result.category_name}</span></td>
										<td width="40" title="{$result.postdate}">{$result.postdate|timeago}</td>
										<td>{$result.size|fsize_format:"MB"}</td>
										<td>
											<a href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}">
												<i
													id="guid{$result.guid}"
													class="icon_nzb fa fa-cloud-download text-muted"
													data-toggle="tooltip"
													data-placement="top" title
													data-original-title="Download NZB">
												</i>
											</a>
											<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
														class="fa fa-comments-o text-muted" data-toggle="tooltip"
														data-placement="top" title
														data-original-title="Comments"></i></a>
											<a href="#">
												<i
													id="guid{$result.guid}"
													class="icon cart text-muted fa fa-shopping-basket"
													data-toggle="tooltip"
													data-placement="top" title
													data-original-title="Send to my Download Basket">
												</i>
											</a>
											{if isset($sabintegrated) && $sabintegrated !=""}
												<a href="#" class="icon_sab text-muted"><i class="fa fa-share"
																						   data-toggle="tooltip"
																						   data-placement="top" title
																						   data-original-title="Send to My Queue"></i></a>
											{/if}
										</td>
									</tr>
								{/foreach}
							</table>
	</form>
{/if}
