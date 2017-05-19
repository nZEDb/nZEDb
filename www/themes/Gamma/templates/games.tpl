<h2>Browse Games</h2>

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
								{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
							</div>
							<br>
							<br>View: <strong>Covers</strong> | <a href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a></br>
							<br/>
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
				<th width="130">
					<input type="checkbox" class="nzb_check_all" />
				</th>
				<th width="140" >title<br/>
					<a title="Sort Descending" href="{$orderbytitle_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbytitle_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th>genre<br/>
					<a title="Sort Descending" href="{$orderbygenre_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbygenre_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th>release date<br/>
					<a title="Sort Descending" href="{$orderbyreleasedate_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyreleasedate_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th>posted<br/>
					<a title="Sort Descending" href="{$orderbyposted_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyposted_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th>size<br/>
					<a title="Sort Descending" href="{$orderbysize_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbysize_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th>files<br/>
					<a title="Sort Descending" href="{$orderbyfiles_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbyfiles_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
				<th>stats<br/>
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
							<div class="movcover">
								<div style="text-align: center;">
									<a class="title" title="View details">
										<img class="shadow img img-polaroid" src="{$smarty.const.WWW_TOP}/covers/games/{if isset($result.cover) && $result.cover == 1}{$result.gamesinfo_id}.jpg{else}no-cover.jpg{/if}"
											 width="120" border="0" alt="{$result.title|escape:"htmlall"}"/>
									</a>
								</div>
							</div>
							<div class="movextra">
								<div style="text-align: center;">
									{if $result.classused == "GiantBomb"}<a class="rndbtn badge"
																	 target="_blank"
																	 href="{$site->dereferrer_link}{$result.url}"
																	 name="giantbomb{$result.gamesinfo_id}"
																	 title="View GiantBomb page">
											GiantBomb</a>{/if}
									{if $result.classused == "Steam"}<a class="rndbtn badge fa fa-steam"
																		target="_blank"
																		href="{$site->dereferrer_link}{$result.url|escape:"htmlall"}"
																		name="steam{$result.gamesinfo_id}"
																		title="View Steam page">
											Steam</a>{/if}
								</div>
							</div>
						</div>
					</td>
					<td colspan="3" class="left">
						<h4>{$result.title|escape:"htmlall"} - {$result.platform|escape:"htmlall"}</h4>

						{if isset($result.genre)&& $result.genre != ''}
							<b>{$result.genre}</b>
							<br />
						{/if}

						{if isset($result.esrb) && $result.esrb != ''}
							<b>ESRB Rating:</b> {$result.esrb}<br />
							<br />
						{/if}

						{if isset($result.publisher) && $result.publisher != ''}
							<b>Publisher:</b> {$result.publisher}
							<br />
						{/if}

						{if isset($result.releasedate) && $result.releasedate != ''}
							<b>Release Date:</b> {$result.releasedate|date_format:"%b %e, %Y"}
							<br />
						{/if}

						{if isset($result.review) && $result.review != ''}
							<b>Review:</b> {$result.review|escape:"htmlall"}
							<br /> <br />
						{/if}

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
											<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">&nbsp;{$mname[$m@index]|escape:"htmlall"}</a>
											<ul class="inline">
												<li width="100px">Posted {$mpostdate[$m@index]|timeago}</li>
												<li width="80px">{$msize[$m@index]|fsize_format:"MB"}</li>
												<li width="50px"><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}</a> <i class="fa fa-file"></i></li>
												<li width="50px"><a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/#comments">{$mcomments[$m@index]}</a> <i class="fa fa-comments-o"></i></li>
												<li width="50px">{$mgrabs[$m@index]} <i class="fa fa-cloud-download"></i></li>
												<li width="50px">{if isset($mnfo[$m@index]) && $mnfo[$m@index] > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}" title="View Nfo" class="modal_nfo fa fa-info-sign" rel="nfo"></a>{/if}</li>
												<li width="50px"><a href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}" title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}" class="fa fa-group"></a></li>
												<li width="80px">{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}<a href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg" name="name{$mguid[$m@index]}" title="Screenshot" class="modal_prev label" rel="preview">Preview</a>{/if}</li>
												<li width="80px">{if $mhaspreview[$m@index]}<a href="#" onclick="return false;" class="mediainfo label" title="{$mguid[$m@index]}">Media</a>{/if}</li>
											</ul>
										</td>
										<td class="icons" style='width:100px;'>
											<ul class="inline">
												<li>
													<a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}"></a>
												</li>
												<li>
													<a href="#" class="icon icon_cart fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;" title="Send to my Download Basket">
													</a>
												</li>
												{if $sabintegrated}
													<li>
														<a class="icon icon_sab fa fa-share" style="text-decoration: none; color: #008ab8;"  href="#" title="Send to queue"></a>
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
									{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
								</div>
								&nbsp;&nbsp;&nbsp;&nbsp;<a title="Switch to List view" href="{$smarty.const.WWW_TOP}/browse?t={$category}"><i class="fa fa-lg fa-list-ol"></i></a>
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
{else}
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Sorry!</strong> Either some PC API key is wrong or there is nothing in this section.
	</div>
{/if}
