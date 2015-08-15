<div class="header">
	{assign var="catsplit" value=">"|explode:$catname}
	<h2>{$catsplit[0]} > <strong>{if isset($catsplit[1])} {$catsplit[1]}{/if}</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ {if isset($catsplit[0])} {$catsplit[0]}{/if} / {if isset($catsplit[1])} {$catsplit[1]}{/if}
		</ol>
	</div>
</div>
{$site->adbrowse}
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		<div class="box-body"
		<div class="row">
			<div class="col-xlg-12 portlets">
				<div class="panel panel-default">
					<div class="panel-body pagination2">
						<div class="row">
							<div class="col-md-8">
								{if isset($shows)}
									<p>
										<a href="{$smarty.const.WWW_TOP}/series"
										   title="View available TV series">Series List</a> |
										<a title="Manage your shows" href="{$smarty.const.WWW_TOP}/myshows">Manage
											My Shows</a> |
										<a title="All releases in your shows as an RSS feed"
										   href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss
											Feed</a>
									</p>
								{/if}
								<div class="nzb_multi_operations">
									{if $covgroup != ''}View:
										<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">Covers
										</a>
										|
										<b>List</b>
										<br/>
									{/if}
									With Selected:
									<div class="btn-group">
										<input type="button"
											   class="nzb_multi_operations_download btn btn-sm btn-success"
											   value="Download NZBs"/>
										<input type="button"
											   class="nzb_multi_operations_cart btn btn-sm btn-info"
											   value="Add to Cart"/>
										{if isset($sabintegrated)}
											<input type="button"
												   class="nzb_multi_operations_sab btn btn-sm btn-primary"
												   value="Send to Queue"/>
										{/if}
										{if isset($nzbgetintegrated)}
											<input type="button"
												   class="nzb_multi_operations_nzbget btn btn-sm btn-primary"
												   value="Send to NZBGet"/>
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
							</div>
							<div class="col-md-4">
								{$pager}
							</div>
						</div>
						<hr>
						<table class="data table table-condensed table-striped table-responsive table-hover" cellspacing="0"
							   cellpadding="0" style="table-layout: auto;">
							<thead>
							<tr>
								<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all"/></th>
								<th>Name
									<a title="Sort Descending" href="{$orderbyname_desc}">
										<i class="fa-icon-caret-down text-muted"> </i>
									</a>
									<a title="Sort Ascending" href="{$orderbyname_asc}">
										<i class="fa-icon-caret-up text-muted"> </i>
									</a>
								</th>
								<th>Category</th>
								<th>Posted</th>
								<th>Size</th>
								<th>Action</th>
							</tr>
							</thead>
							<tbody>
							{foreach from=$results item=result}
								<tr id="guid{$result.guid}">
									<td class="check"><input id="chk{$result.guid|substr:0:7}"
															 type="checkbox" class="nzb_check"
															 value="{$result.guid}"/></td>
									<td>
										<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
										   class="title">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
										<br/>
													<span class="label label-default">{$result.grabs}
														Grab{if $result.grabs != 1}s{/if}</span>
										{if $result.nfoid > 0}<span><a
													href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
													class="modal_nfo label label-default" rel="nfo">NFO</a></span>{/if}
										{if $result.jpgstatus == 1 && $userdata.canpreview == 1}<span><a
													href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
													name="name{$result.guid}" class="modal_prev label label-default" rel="preview">Sample</a></span>{/if}
										{if $result.haspreview == 1 && $userdata.canpreview == 1}<span><a
													href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
													name="name{$result.guid}" class="modal_prev label label-default" rel="preview">Preview</a></span>{/if}
										{if $result.rageid > 0}<span class="label label-default"><a
													href="{$smarty.const.WWW_TOP}/series/{$result.rageid}"
													class="text-muted">View TV</a></span>{/if}
										{if $result.anidbid > 0}<span class="label label-default"><a
													href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}"
													class="text-muted">View Anime</a></span>{/if}
									</td>
									<td><span class="label label-default">{$result.category_name}</span>
									</td>
									<td>{$result.postdate|timeago}</td>
									<td>{$result.size|fsize_format:"MB"}</td>
									<td class="icon_nzb"><a
												href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}"><i
													class="fa fa-download text-muted"
													title="Download NZB"></i></a>
										<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
													class="fa fa-comments-o text-muted"
													title="Comments"></i></a>
										<a href="#" class="icon_cart text-muted"><i
													class="fa fa-shopping-cart" title="Send to my Cart"></i></a>
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
							</tbody>
						</table>
						<hr>
						<div class="row">
							<div class="col-md-8">
								<div class="nzb_multi_operations">
									{if isset($section) && $section != ''}View:
										<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}">Covers</a>
										|
										<b>List</b>
										<br/>
									{/if}
									With Selected:
									<div class="btn-group">
										<input type="button"
											   class="nzb_multi_operations_download btn btn-sm btn-success"
											   value="Download NZBs"/>
										<input type="button"
											   class="nzb_multi_operations_cart btn btn-sm btn-info"
											   value="Add to Cart"/>
										{if isset($sabintegrated)}
											<input type="button"
												   class="nzb_multi_operations_sab btn btn-sm btn-primary"
												   value="Send to Queue"/>
										{/if}
										{if isset($nzbgetintegrated)}
											<input type="button"
												   class="nzb_multi_operations_nzbget btn btn-sm btn-primary"
												   value="Send to NZBGet"/>
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
							</div>
							<div class="col-md-4">
								{$pager}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</form>
{/if}