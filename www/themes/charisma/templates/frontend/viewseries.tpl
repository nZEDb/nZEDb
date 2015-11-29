{if isset($nodata) && $nodata != ""}
	<div class="header">
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ TV Series
			</ol>
		</div>
	</div>
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Sorry!</strong>
		{$nodata}
	</div>
{else}
	<div class="header">
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ TV Series
			</ol>
		</div>
	</div>
	<h1>
		<center>{$seriestitles} ({$show.publisher})</center>
	</h1>
	{if $catname != ''}<span class="text-info h5">Current category shown: {$catname|escape:"htmlall"}</span>{/if}
	<div class="tvseriesheading">
		{if $show.image != 0}
			<center>
				<img class="shadow img img-polaroid" style="max-height:300px;" alt="{$seriestitles} Logo"
					 src="{$smarty.const.WWW_TOP}/covers/tvshows/{$show.id}.jpg"/>
			</center>
			<br/>
		{/if}
		<p>
			<span class="descinitial">{$seriessummary|escape:"htmlall"|nl2br|magicurl}</span>
		</p>
	</div>
	<div class="btn-group">
		<a class="btn btn-sm btn-default"
		   href="{$smarty.const.WWW_TOP}/rss?show={$show.id}{if $category != ''}&amp;t={$category}{/if}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS for TV Show <i class="fa fa-rss"></i></a>
			{if $show.tvdb > 0}
				<a class="btn btn-sm btn-info" target="_blank"
				   href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$show.tvdb}"
				   title="View at TheTVDB">TheTVDB</a>
			{/if}
			{if $show.tvmaze > 0}
				<a class="btn btn-sm btn-info" target="_blank"
				   href="{$site->dereferrer_link}http://tvmaze.com/shows/{$show.tvmaze}"
				   title="View at TVMaze">TVMaze</a>
			{/if}
			{if $show.trakt > 0}
				<a class="btn btn-sm btn-info" target="_blank"
				   href="{$site->dereferrer_link}http://www.trakt.tv/shows/{$show.trakt}"
				   title="View at TraktTv">Trakt</a>
			{/if}
			{if $show.tvrage > 0}
				<a class="btn btn-sm btn-info" target="_blank"
				   href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$show.tvrage}"
				   title="View at TV Rage">TV Rage</a>
			{/if}
			{if $show.tmdb > 0}
				<a class="btn btn-sm btn-info" target="_blank"
				   href="{$site->dereferrer_link}https://www.themoviedb.org/tv/{$show.tmdb}"
				   title="View at TheMovieDB">TMDB</a>
			{/if}
	</div>
	<br/>
	<div class="box-body"
	<form id="nzb_multi_operations_form" action="get">
		<div class="well well-small">
			<div class="nzb_multi_operations">
				With Selected:
				<div class="btn-group">
					<input type="button" class="nzb_multi_operations_download btn btn-sm btn-success"
						   value="Download NZBs"/>
					<input type="button" class="nzb_multi_operations_cart btn btn-sm btn-info"
						   value="Send to my Download Basket"/>
					{if isset($sabintegrated)}
						<input type="button" class="nzb_multi_operations_sab btn btn-sm btn-primary"
							   value="Send to Queue"/>
					{/if}
				</div>
				{if isset($isadmin)}
					<div class="pull-right">
						Admin:
						<div class="btn-group">
							<input type="button" class="nzb_multi_operations_edit btn btn-sm btn-warning"
								   value="Edit"/>
							<input type="button" class="nzb_multi_operations_delete btn btn-sm btn-danger"
								   value="Delete"/>
						</div>
					</div>
				{/if}
			</div>
			<div>
				<a title="Manage your shows" href="{$smarty.const.WWW_TOP}/myshows">My Shows</a> :
				<div class="btn-group">
					{if $myshows.id != ''}
						<a class="btn btn-sm btn-warning"
						   href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
						   class="myshows" rel="edit" name="series{$show.id}"
						   title="Edit Categories for this show">Edit</a>
						<a class="btn btn-sm btn-danger"
						   href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
						   class="myshows" rel="remove" name="series{$show.id}"
						   title="Remove from My Shows">Remove</a>
					{else}
						<a class="btn btn-sm btn-success"
						   href="{$smarty.const.WWW_TOP}/myshows/add/{$show.id}?from={$smarty.server.REQUEST_URI|escape:"url"}"
						   class="myshows" rel="add" name="series{$show.id}" title="Add to My Shows">Add</a>
					{/if}
				</div>
			</div>
		</div>
		<br clear="all"/>
		<a id="latest"></a>

		<div class="row">
			<div class="col-xlg-12 portlets">
				<div class="panel panel-default">
					<div class="panel-body pagination2">
						<div class="tabbable">
							<ul class="nav nav-tabs">
								{foreach $seasons as $seasonnum => $season name = "seas"}
									<li {if $smarty.foreach.seas.first}class="active"{/if}><a
												title="View Season {$seasonnum}" href="#{$seasonnum}"
												data-toggle="tab">{$seasonnum}</a></li>
								{/foreach}
							</ul>
							<div class="tab-content">
								{foreach $seasons as $seasonnum => $season name = "tv"}
									<div class="tab-pane{if $smarty.foreach.tv.first} active{/if} fade in"
										 id="{$seasonnum}">
										<table class="tb_{$seasonnum} data table table-condensed table-bordered table-responsive table-hover"
											   id="browsetable">
											<thead>
											<tr>
												<th>Ep</th>
												<th>Name</th>
												<th><input id="chkSelectAll{$seasonnum}" type="checkbox"
														   name="{$seasonnum}"
														   class="nzb_check_all_season"/><label
															for="chkSelectAll{$seasonnum}"
															style="display:none;">Select
														All</label></th>
												<th>Category</th>
												<th>Posted</th>
												<th>Size</th>
												<th>Action</th>
											</tr>
											</thead>
											{foreach $season as $episodes}
												{foreach $episodes as $result}
													<tr class="{cycle values=",alt"}"
														id="guid{$result.guid}">
														{if $result@total>1 && $result@index == 0}
															<td rowspan="{$result@total}" width="30">
																<h4>{$episodes@key}</h4></td>
														{elseif $result@total == 1}
															<td><h4>{$episodes@key}</h4></td>
														{/if}
														<td>
															<a title="View details"
															   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>

															<div>
																{if $result.nfoid > 0}<span
																		class="label label-default">
																	<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
																	   class="text-muted">NFO</a>
																	</span>{/if}
																{if $result.image == 1 && $userdata.canpreview == 1}
																<a
																		href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
																		name="name{$result.guid}"
																		title="View Screenshot"
																		class="modal_prev label label-default"
																		rel="preview">Preview</a>{/if}
																<span class="label label-default">{$result.grabs}
																	Grab{if $result.grabs != 1}s{/if}</span>
																{if $result.firstaired != ""}<span
																	class="label label-success"
																	title="{$result.title} Aired on {$result.firstaired|date_format}">
																	Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>{/if}
																{if $result.reid > 0}<span
																	class="mediainfo label label-default"
																	title="{$result.guid}">Media</span>{/if}
															</div>
														</td>
														<td class="check" width="10"><input
																	id="chk{$result.guid|substr:0:7}"
																	type="checkbox"
																	class="nzb_check" name="{$seasonnum}"
																	value="{$result.guid}"/></td>
														<td>
															<span class="label label-default">{$result.category_name}</span>
														</td>
														<td width="40"
															title="{$result.postdate}">{$result.postdate|timeago}</td>
														<td>
															{$result.size|fsize_format:"MB"}
														</td>
														<td class="icons" style='width:100px;'>
															<a title="Download Nzb"
															   href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}"><i
																		class="fa fa-cloud-download text-muted"></i></a>
															<a class="fa fa-shopping-basket icon_cart text-muted"
															   href="#"
															   title="Send to my Download Basket">
															</a>
															{if isset($sabintegrated)}
																<a class="fa fa-share-o icon_sab text-muted"
																   href="#"
																   title="Send to my Queue">
																</a>
															{/if}
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
									</div>
								{/foreach}
							</div>
						</div>
	</form>
{/if}
