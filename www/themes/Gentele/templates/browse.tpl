<div class="header">
	{assign var="catsplit" value=">"|explode:$catname}
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
									{if isset($covgroup) && $covgroup != ''}View:
										<a href="{$smarty.const.WWW_TOP}/{$covgroup}?t={$category}">Covers
										</a>
										|
										<b>List</b>
										<br/>
									{/if}
									With Selected:
									<div class="btn-group">
										<button type="button"
												class="nzb_multi_operations_download btn btn-sm btn-success"
												data-toggle="tooltip" data-placement="top" title
												data-original-title="Download NZBs">
											<i class="fa fa-cloud-download"></i></button>
										<button type="button"
												class="nzb_multi_operations_cart btn btn-sm btn-info"
												data-toggle="tooltip" data-placement="top" title
												data-original-title="Send to my Download Basket">
											<i class="fa fa-shopping-basket"></i></button>

										{if isset($sabintegrated) && $sabintegrated !=""}
											<button type="button"
													class="nzb_multi_operations_sab btn btn-sm btn-primary"
													data-toggle="tooltip" data-placement="top" title
													data-original-title="Send to Queue">
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
							</div>
							<div class="col-md-4">
								{$pager}
							</div>
						</div>
						<hr>
						<table class="data table table-striped responsive-utilities jambo-table bulk-action">
							<thead>
							<tr class="headings">
								<th><input id="check-all" type="checkbox" class="flat-all"/></th>
								<th>Name
									<a title="Sort Descending" href="{$orderbyname_desc}"><i
												class="fa-icon-caret-down text-muted"> </i></a>
									<a title="Sort Ascending" href="{$orderbyname_asc}"><i
												class="fa-icon-caret-up text-muted"> </i></a>
								</th>
								<th class="column-title" style="display: table-cell;">Category</th>
								<th class="column-title" style="display: table-cell;">Posted</th>
								<th class="column-title" style="display: table-cell;">Size</th>
								<th class="column-title no-link last" style="display: table-cell;">Action</th>
								<th class="bulk-actions" colspan="7">
									<a class="antoo" style="color:#fff; font-weight:500;">Bulk Actions ( <span
												class="action-cnt"> </span> ) <i class="fa fa-chevron-down"></i></a>
								</th>
							</tr>
							</thead>
							<tbody>
							{foreach $results as $result}
								<tr id="guid{$result.guid}">
									<td><input id="chk{$result.guid|substr:0:7}"
											   type="checkbox" name="table_records" class="flat"
											   value="{$result.guid}"/></td>
									<td>
										<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}"
										   class="title">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>{if !empty($result.failed)}
										<i class="fa fa-exclamation-circle" style="color: red"
										   title="This release has failed to download for some users"></i>{/if}
										<br/>
													<span class="label label-primary">{$result.grabs}
														Grab{if $result.grabs != 1}s{/if}</span>
										{if $result.nfoid > 0}<span><a
													href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
													class="modal_nfo label label-primary" rel="nfo">NFO</a></span>{/if}
										{if $result.jpgstatus == 1 && $userdata.canpreview == 1}<span><a
													href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
													name="name{$result.guid}" class="modal_prev label label-primary"
													rel="preview">Sample</a></span>{/if}
										{if $result.haspreview == 1 && $userdata.canpreview == 1}<span><a
													href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
													name="name{$result.guid}" class="modal_prev label label-primary"
													rel="preview">Preview</a></span>{/if}
										{if $result.videos_id > 0}<span><a
													href="{$smarty.const.WWW_TOP}/series/{$result.videos_id}"
													class="label label-primary" rel="series">View TV</a></span>{/if}
										{if isset($result.firstaired) && $result.firstaired != ""}<span
											class="label label-primary" title="{$result.guid}">
											Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>{/if}
										{if $result.anidbid > 0}<span><a class="label label-primary"
																		 href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}">View
												Anime</a></span>{/if}
										{if !empty($result.failed)}<span class="label label-primary">
											<i class="fa fa-thumbs-o-up"></i>
											{$result.grabs} Grab{if $result.grabs != 1}s{/if} /
											<i class="fa fa-thumbs-o-down"></i>
											{$result.failed} Failed Download{if $result.failed != 1}s{/if}</span>{/if}
									</td>
									<td><span class="label label-primary">{$result.category_name}</span>
									</td>
									<td>{$result.postdate|timeago}</td>
									<td>{$result.size|fsize_format:"MB"}</td>
									<td>
										<a href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}" class="icon_nzb text-muted" style="background-image: none"><i
													class="fa fa-cloud-download text-muted"
													data-toggle="tooltip" data-placement="top" title
													data-original-title="Download NZB"></i></a>
										<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
													class="fa fa-comments-o text-muted"
													data-toggle="tooltip" data-placement="top" title
													data-original-title="Comments"></i></a>
										<a href="#" class="icon_cart text-muted" style="background-image: none"><i
													class="fa fa-shopping-basket" data-toggle="tooltip"
													data-placement="top" title
													data-original-title="Send to my download basket"></i></a>
										{if isset($sabintegrated) && $sabintegrated !=""}
											<a href="#" class="icon_sab text-muted" style="background-image: none"><i class="fa fa-share"
																					   data-toggle="tooltip"
																					   data-placement="top" title
																					   data-original-title="Send to my Queue"></i></a>
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
										<button type="button"
												class="nzb_multi_operations_download btn btn-sm btn-success"
												data-toggle="tooltip" data-placement="top" title
												data-original-title="Download NZBs">
											<i class="fa fa-cloud-download"></i></button>
										<button type="button"
												class="nzb_multi_operations_cart btn btn-sm btn-info"
												data-toggle="tooltip" data-placement="top" title
												data-original-title="Send to my Download Basket">
											<i class="fa fa-shopping-basket"></i></button>

										{if isset($sabintegrated) && $sabintegrated !=""}
											<button type="button"
													class="nzb_multi_operations_sab btn btn-sm btn-primary"
													data-toggle="tooltip" data-placement="top" title
													data-original-title="Send to Queue">
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
