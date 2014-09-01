{if {$site->adbrowse} != ''}
	<div class="container" style="width:500px;">
		<fieldset class="adbanner div-center">
			<legend class="adbanner">Advertisement</legend>
			{$site->adbrowse}
		</fieldset>
	</div>
	<br>
{/if}
<div class="panel">
	<div class="panel-heading">
		<h4 class="panel-title">
			<a
					class="accordion-toggle"
					data-toggle="collapse"
					data-parent="#accordion"
					href="#searchtoggle"
					><i class="icon-search"></i> Search Filter</a>
		</h4>
	</div>
	<div id="searchtoggle" class="panel-collapse collapse">
		<div class="panel-body">
			{include file='search-filter.tpl'}
		</div>
	</div>
</div>
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		<div class="container nzb_multi_operations" style="text-align:right;margin-bottom:5px;">
			View:
			<span><i class="icon-th-list"></i></span>
			&nbsp;&nbsp;
			<a href="{$smarty.const.WWW_TOP}/browse?t={$category}"><i class="icon-align-justify"></i></a>
			{if $isadmin || $ismod}
				&nbsp;&nbsp;
				Admin:
				<input type="button" class="btn btn-warning nzb_multi_operations_edit" value="Edit">
				<input type="button" class="btn btn-danger nzb_multi_operations_delete" value="Delete">
			{/if}
		</div>
		{include file='multi-operations.tpl'}
		<table class="table table-striped table-condensed table-hover data icons" id="coverstable">
			<thead>
				<tr>
					<th><input type="checkbox" class="nzb_check_all"></th>
					<th>title <a title="Sort Descending" href="{$orderbytitle_desc}"><i class="icon-chevron-down"></i></a><a
								title="Sort Ascending" href="{$orderbytitle_asc}"><i class="icon-chevron-up"></i></a></th>
					<th>genre <a title="Sort Descending" href="{$orderbygenre_desc}"><i class="icon-chevron-down"></i></a><a
								title="Sort Ascending" href="{$orderbygenre_asc}"><i class="icon-chevron-up"></i></a></th>
					<th>release date <a title="Sort Descending" href="{$orderbyreleasedate_desc}"><i class="icon-chevron-down"></i></a>
						<a title="Sort Ascending" href="{$orderbyreleasedate_asc}"><i class="icon-chevron-up"></i></a></th>
				</tr>
			</thead>
			<tbody>
			{foreach from=$results item=result}
				<tr>
					<td style="width:150px;padding:10px;text-align:center;">
						<div class="movcover" style="padding-bottom:5px;">
							<a
								class="title thumbnail"
								title="View page"
								target="_blank"
								href="{$site->dereferrer_link}{$result.url}"
								width="130px"
								height="180px"
							><img
									class="shadow"
									 src="{$smarty.const.WWW_TOP}/covers/games/{if $result.cover == 1}{$result.gamesinfo_id}.jpg{else}no-cover.jpg{/if}"
									 width="130px"
									 border="0"
									 alt="{$result.title|escape:"htmlall"}"
							></a>
							<div class="relextra" style="margin-top:5px;">
								{if $result.url != ""}
									{if $result.classused == "gb"}
									<a
										class="label"
										target="_blank"
										href="{$site->dereferrer_link}{$result.url}"
										name="giantbomb{$result.gamesinfo_id}"
										title="View giantbomb page"
									><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/giantbomb.png"></a>
									{/if}
									{if $result.classused == "steam"}
										<a
											class="label"
											target="_blank"
											href="{$site->dereferrer_link}{$result.url}"
											name="Steam{$result.gamesinfo_id}"
											title="View Steam page"
											><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/steam.png"></a>
									{/if}

								{/if}
								<a
									class="label"
									target="_blank"
									href="{$site->dereferrer_link}http://ign.com/search?q={$result.title|escape:"htmlall"}&page=0&count=10&type=object&objectType=game&filter=games&"
									name="ign{$result.id}"
									title="Find game on IGN"
								><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/ign.png"></a>
								<a
									class="label"
									target="_blank"
									href="{$site->dereferrer_link}http://www.gamespot.com/search/?q={$result.title|escape:"htmlall"}"
									name="ign{$result.id}"
									title="Find game on Gamespot"
								><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/gamespot.png"></a>
							</div>
						</div>
					</td>
					<td colspan="8" class="left" id="guid{$result.guid}">
						<h4>{$result.title}</h4>
						{if $result.genre != ""}
							<b>Genre:</b>{$result.genre}<br>
						{/if}
						{if $result.esrb != ""}
							<b>Rating:</b>{$result.esrb}<br>
						{/if}
						{if $result.publisher != ""}
							<b>Publisher:</b>{$result.publisher}<br>
						{/if}
						{if $result.releasedate != ""}
							<b>Released:</b>{$result.releasedate|date_format}<br>
						{/if}
						{if $result.review != ""}<b>Review:</b>{$result.review|escape:'htmlall'}<br>{/if}
						<div class="relextra">
							<table class="table table-condensed table-hover data">
								{assign var="msplits" value=","|explode:$result.grp_release_id}
								{assign var="mguid" value=","|explode:$result.grp_release_guid}
								{assign var="mnfo" value=","|explode:$result.grp_release_nfoid}
								{assign var="mgrp" value=","|explode:$result.grp_release_grpname}
								{assign var="mname" value="#"|explode:$result.grp_release_name}
								{assign var="mpostdate" value=","|explode:$result.grp_release_postdate}
								{assign var="msize" value=","|explode:$result.grp_release_size}
								{assign var="mtotalparts" value=","|explode:$result.grp_release_totalparts}
								{assign var="mcomments" value=","|explode:$result.grp_release_comments}
								{assign var="mgrabs" value=","|explode:$result.grp_release_grabs}
								{assign var="mpass" value=","|explode:$result.grp_release_password}
								{assign var="minnerfiles" value=","|explode:$result.grp_rarinnerfilecount}
								{assign var="mhaspreview" value=","|explode:$result.grp_haspreview}
								<tbody>
								{foreach from=$msplits item=m}
									<tr id="guid{$mguid[$m@index]}" {if $m@index > 1}class="mlextra"{/if}>
										<td style="width: 27px;">
											<input type="checkbox" class="nzb_check" value="{$mguid[$m@index]}">
										</td>
										<td class="name">
											<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}">
												<b>{$mname[$m@index]|escape:"htmlall"}</b>
											</a>
											<br>
											<div class="resextra">
												<div class="pull-left">
													<i class="icon-calendar"></i> Posted {$mpostdate[$m@index]|timeago} |
													<i class="icon-hdd"></i> {$msize[$m@index]|fsize_format:"MB"} |
													<i class="icon-file"></i>
													<a
														title="View file list"
														href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}
														files
													</a> |
													<i class="icon-comments"></i>
													<a
														title="View comments for {$mname[$m@index]|escape:"htmlall"}"
														href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}#comments">{$mcomments[$m@index]}
														cmt{if $mcomments[$m@index] != 1}s{/if}
													</a> |
													<i class="icon-download"></i> {$mgrabs[$m@index]} grab{if $mgrabs[$m@index] != 1}s{/if}
												</div>
												<div class="pull-right">
													{if $mnfo[$m@index] > 0}
														<span class="label label-default">
															<a
																href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}"
																title="View Nfo"
																class="modal_nfo"
																rel="nfo"
															><i class="icon-info-sign"></i></a></span
														> {/if}
													{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}
														<span class="label label-default">
															<a
																href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg"
																name="name{$mguid[$m@index]}"
																title="Screenshot of {$mname[$m@index]|escape:"htmlall"}"
																class="modal_prev" rel="preview"
															><i class="icon-camera"></i></a>
														</span>
													{/if}
													{if $minnerfiles[$m@index] > 0}
														<span class="label label-default">
														<a
																href="#" onclick="return false;"
																class="mediainfo"
																title="{$mguid[$m@index]}"
																><i class="icon-list-alt"></i></a></span
																>
													{/if}
													<span class="label label-default">
														<a
															href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}"
															title="Browse releases in {$mgrp[$m@index]}"
														><i class="icon-share-alt"></i></a></span
													>
													{if $mpass[$m@index] == 1}
														<span class="icon-stack" title="Potentially Passworded"><i class="icon-check-empty icon-stack-base"></i><i class="icon-unlock-alt"></i></span>
													{elseif $mpass[$m@index] == 2}
														<span class="icon-stack" title="Broken Post"><i class="icon-check-empty icon-stack-base"></i><i class="icon-unlink"></i></span>
													{elseif $mpass[$m@index] == 10}
														<span class="icon-stack" title="Passworded Archive"><i class="icon-check-empty icon-stack-base"></i><i class="icon-lock"></i></span>
													{/if}
												</div>
											</div>
										</td>
										<td class="icons" style="width:90px;">
											<div class="icon icon_nzb float-right">
												<a
													title="Download Nzb"
													href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"
												></a>
											</div>
											{if $sabintegrated}
												<div class="icon icon_sab float-right" title="Send to my Queue"></div>
											{/if}
											<div class="icon icon_cart float-right" title="Add to Cart"></div>
											<br>
											{*s{if $isadmin || $ismod}
											<a class="label label-warning" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a>
											<a class="label confirm_action label-danger" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Delete</a>
											{/if}*}
										</td>
									</tr>
									{if $m@index == 1 && $m@total > 2}
										<tr>
											<td colspan="5">
												<a class="mlmore" href="#">{$m@total-2} more...</a>
											</td>
										</tr>
									{/if}
								{/foreach}
								</tbody>
							</table>
						</div>
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
	<div class="alert alert-link" style="vertical-align:middle;">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<div class="pull-left" style="margin-right: 15px;">
			<h2 style="margin-top: 7px;"> ಠ_ಠ </h2>
		</div>
		<p>No games releases have amazon covers.
			<br>This might mean the Administrator's Amazon API keys are wrong, or he has file permission issues, or he has disabled looking up Amazon.
			<br>This could also mean there are no games releases.
			<br>Please try looking in the
			<a href="{$smarty.const.WWW_TOP}/browse?t={$category}" style="font-weight:strong;text-decoration:underline;"
			>list view</a>, which does not require Amazon covers.
		</p>
	</div>
{/if}
