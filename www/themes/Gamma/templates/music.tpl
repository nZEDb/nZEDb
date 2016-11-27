<h2>Browse {$catname}</h2>

<div class="well well-sm">
	<div style="text-align: center;">
		{include file='search-filter.tpl'}
	</div>
</div>

{$site->adbrowse}

{if $results|@count > 0}

	<form id="nzb_multi_operations_form" action="get">
		<div class="well well-sm">
			<div class="nzb_multi_operations">
				<table width="100%">
					<tr>
						<td width="30%">
							With Selected:
							<div class="btn-group">
								<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
								<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
								{if isset($sabintegrated) && $sabintegrated !=""}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
							</div>
							View: <strong>Covers</strong> | <a
									href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
						</td>
						<td width="50%">
							<div style="text-align: center;">
								{$pager}
							</div>
						</td>
						<td width="20%">
							<div class="pull-right">
								{if isset($isadmin)}
									Admin:
									<div class="btn-group">
										<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
										<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
									</div>
									&nbsp;
								{/if}
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<table style="width:100%;" class="data highlight icons table" id="coverstable">
			<tr>
				<th width="130" style="padding-top:0px; padding-bottom:0px;">
					<input type="checkbox" class="nzb_check_all" />
				</th>

				<th style="padding-top:0px; padding-bottom:0px;">artist<br/>
					<a title="Sort Descending" href="{$orderbyartist_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyartist_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">year<br/>
					<a title="Sort Descending" href="{$orderbyyear_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyyear_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">genre<br/>
					<a title="Sort Descending" href="{$orderbygenre_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbygenre_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">posted<br/>
					<a title="Sort Descending" href="{$orderbyposted_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyposted_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">size<br/>
					<a title="Sort Descending" href="{$orderbysize_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbysize_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">files<br/>
					<a title="Sort Descending" href="{$orderbyfiles_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyfiles_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">stats<br/>
					<a title="Sort Descending" href="{$orderbystats_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbystats_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
			</tr>

			{foreach $results as $result}
				<tr class="{cycle values=",alt"}">
					<td class="mid">
						<div class="movcover">
							<img class="shadow img-polaroid" src="{$smarty.const.WWW_TOP}/covers/music/{if isset($result.cover) && $result.cover == 1}{$result.musicinfo_id}.jpg{else}no-cover.jpg{/if}" style="max-width: 120px; /*width: auto;*/" width="120" border="0" alt="{$result.title|escape:"htmlall"}" />
							<div class="movextra">
								<div style="text-align: center;">
									{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}" title="View Nfo" class="rndbtn modal_nfo badge" rel="nfo">Nfo</a>{/if}
									{if $result.url != ""}<a class="rndbtn badge badge-amaz" target="_blank" href="{$site->dereferrer_link}{$result.url}" name="amazon{$result.musicinfo_id}" title="View amazon page">Amazon</a>{/if}
									<a class="rndbtn badge" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}" title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
								</div>
							</div>
						</div>
					</td>
					<td colspan="3" class="left">
						<h4>
							<a class="title">{$result.artist}{" - "}{$result.title}</a>
						</h4>
						{if !empty($result.genre)}<b>Genre:</b> <a href="{$smarty.const.WWW_TOP}/music/?genre={$result.genreid}">{$result.genre|escape:"htmlall"}</a><br />{/if}
						{if !empty($result.publisher)}<b>Publisher:</b> {$result.publisher|escape:"htmlall"}<br />{/if}
						{if !empty($result.releasedate)}<b>Released:</b> {$result.releasedate|date_format}<br />{/if}
						{if isset($result.haspreview) && $result.haspreview == 2 && $userdata.canpreview == 1}<b>Preview:</b> <a href="#" name="audio{$mguid[$m@index]}" title="Listen to {$result.title|escape:"htmlall"}" class="audioprev rndbtn" rel="audio">Listen</a><audio id="audprev{$mguid[$m@index]}" src="{$smarty.const.WWW_TOP}/covers/audio/{$mguid[$m@index]}.mp3" preload="none"></audio>{/if}
						<div class="movextra">
							<table class="table" style="margin-bottom:0px; margin-top:10px">
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
								{foreach $msplits as $m}
									<tr id="guid{$mguid[$m@index]}" {if $m@index > 0}class="mlextra"{/if}>
										<td>
											<div class="icon"><input type="checkbox" class="nzb_check" value="{$mguid[$m@index]}" /></div>
										</td>
										<td>
											<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$mname[$m@index]|escape:"htmlall"}</a>
											<ul class="inline">
												<li width="100px">Posted {$mpostdate[$m@index]|timeago}</li>
												<li width="80px">{$msize[$m@index]|fsize_format:"MB"}</li>
												<li width="50px"><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}</a> <i class="fa fa-file"></i></li>
												<li width="50px"><a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/#comments">{$mcomments[$m@index]}</a> <i class="fa fa-comments-o"></i></li>
												<li width="50px">{$mgrabs[$m@index]} <i class="fa fa-cloud-download"></i></li>
												<li width="50px">{if isset($mnfo[$m@index]) && $mnfo[$m@index] > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}" title="View Nfo" class="modal_nfo fa fa-info-sign" rel="nfo"></a>{/if}</li>
												<li width="50px"><a href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}" title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}" class="fa fa-group"></a></li>
											</ul>
										</td>
										<td class="icons" style='width:150px;'>
											<ul class="inline">
												<li>
													<a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}"></a>
												</li>
												<li>
													<a href="#" class="icon icon_cart fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;" title="Send to my Download Basket">
													</a>
												</li>
												{if isset($sabintegrated) && $sabintegrated !=""}
													<li>
														<a class="icon icon_sab fa fa-share" style="text-decoration: none; color: #008ab8;"  href="#" title="Send to queue"></a>
													</li>
												{/if}
												{if $weHasVortex}
													<li>
														<a class="icon icon_nzb fa fa-cloud-downloadvortex" href="#" title="Send to NZBVortex">
															<img src="{$smarty.const.WWW_THEMES}/shared/images/icons/vortex/bigsmile.png">
														</a>
													</li>
												{/if}
											</ul>
										</td>
									</tr>
									{if $m@index == 1 && $m@total > 2}
										<tr>
											<td colspan="5">
												<a class="mlmore" href="#">{$m@total-1} more...</a>
											</td>
										</tr>
									{/if}
								{/foreach}
							</table>
						</div>
					</td>
				</tr>
			{/foreach}
		</table>
		{if $results|@count > 10}
			<div class="well well-sm">
				<div class="nzb_multi_operations">
					<table width="100%">
						<tr>
							<td width="30%">
								With Selected:
								<div class="btn-group">
									<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
									<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
									{if isset($sabintegrated) && $sabintegrated !=""}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
								</div>
								View: <strong>Covers</strong> | <a
										href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
							</td>
							<td width="50%">
								<div style="text-align: center;">
									{$pager}
								</div>
							</td>
							<td width="20%">
								<div class="pull-right">
									{if isset($isadmin)}
										Admin:
										<div class="btn-group">
											<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
											<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
										</div>
										&nbsp;
									{/if}
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		{/if}
	</form>
{/if}
