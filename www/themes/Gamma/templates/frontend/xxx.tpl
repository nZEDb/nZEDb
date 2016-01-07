<h2>Browse {$catname}</h2>

<div class="well well-small">
	<center>
		<form class="form-inline" name="browseby" action="xxx" style="margin:0;">
			<i class="fa fa-film fa-midt"></i>
			<input class="input input-medium" id="title" type="text" name="title" value="{$title}" placeholder="Title"/>
			<i class="fa fa-group fa-midt"></i>
			<input class="input input-medium" id="actors" type="text" name="actors" value="{$actors}"
				   placeholder="Actor"/>
			<i class="fa fa-bullhorn fa-midt"></i>
			<input class="input input-medium" id="director" type="text" name="director" value="{$director}"
				   placeholder="Director"/>
			<i class="fa fa-inbox fa-midt"></i>
			<select class="input input-medium" id="genre" name="genre">
				<option class="grouping" value=""></option>
				{foreach from=$genres item=gen}
					<option {if $gen==$genre}selected="selected"{/if} value="{$gen}">{$gen}</option>
				{/foreach}
			</select>
			<i class="fa fa-flag fa-midt"></i>
			<select class="input input-medium" id="category" name="category">
				<option class="grouping" value=""></option>
				{foreach from=$catlist item=cat}
					<option {if $cat.id==$category}selected="selected"{/if} value="{$cat.id}">{$cat.title}</option>
				{/foreach}
			</select>
			<input class="btn btn-success" type="submit" value="Go"/>
		</form>
	</center>
</div>
{$site->adbrowse}
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		<div class="well well-small">
			<div class="nzb_multi_operations">
				<table width="100%">
					<tr>
						<td width="30%">
							With Selected:
							<div class="btn-group">
								<input type="button" class="nzb_multi_operations_download btn btn-small btn-success"
									   value="Download NZBs"/>
								<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info"
									   value="Send to my Download Basket"/>
								{if $sabintegrated}
									<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary"
										   value="Send to queue"/>
								{/if}
								{if isset($nzbgetintegrated)}
									<input type="button" class="nzb_multi_operations_nzbget btn btn-small btn-primary"
										   value="Send to NZBGet"/>
								{/if}
							</div>
							View: <strong>Covers</strong> | <a
									href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
						</td>
						<td width="50%">
							<center>
								{$pager}
							</center>
						</td>
						<td width="20%">
							<div class="pull-right">
								{if $isadmin}
									Admin:
									<div class="btn-group">
										<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning"
											   value="Edit"/>
										<input type="button"
											   class="nzb_multi_operations_delete btn btn-small btn-danger"
											   value="Delete"/>
									</div>
									&nbsp;
								{/if}
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<table style="width:100%;" class="data highlight icons table table-striped" id="coverstable">
			<tr>
				<th width="130" style="padding-top:0px; padding-bottom:0px;">
					<input type="checkbox" class="nzb_check_all"/>
				</th>
				<th style="padding-top:0px; padding-bottom:0px;">title<br/>
					<a title="Sort Descending" href="{$orderbytitle_desc}">
						<i class="fa fa-caret-down"></i>
					</a>
					<a title="Sort Ascending" href="{$orderbytitle_asc}">
						<i class="fa fa-caret-up"></i>
					</a>
				</th>
			</tr>
			{foreach from=$results item=result}
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
				{assign var="previewfound" value="0"}
				{assign var="previewguid" value=""}
				<tr class="{cycle values=",alt"}">
					<td class="mid">
						<div class="movcover">
							<h4>
								<a target="_blank"
										{foreach from=$msplits item=m}
											{if $previewfound == 0}
												{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}
													{$previewfound = 1}
													{$previewguid = $mguid[$m@index]}
												{/if}
											{/if}
										{/foreach}
								   href="{$smarty.const.WWW_TOP}/xxx/?id={$result.id}"
								   name="name{$result.id}"
								   guid="name{$previewguid}"
								   title="View XXX info"
								   class="modal_xxx thumbnail" rel="viewxxx">
									{if $result.cover == 1}
									<img class="shadow img-polaroid" src="{$smarty.const.WWW_TOP}/covers/xxx/{$result.id}-cover.jpg" style="max-width: 120px;" width="120" border="0" alt="{$result.title|escape:"htmlall"}"/>
									{else} <img class="shadow img-polaroid" src="{$smarty.const.WWW_THEMES}/shared/images/no-cover.png" style="max-width: 120px;" width="120" border="0" alt="{$result.title|escape:"htmlall"}"/>
									{/if}
								</a>
							</h4>
							<div class="movextra">
								<center>
									{if $result.classused == "ade"}
										<a
												target="_blank"
												href="{$site->dereferrer_link}{$result.directurl}"
												name="viewade{$result.title}"
												title="View AdultdvdEmpire page"
										><img
													src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/ade.png"></a>
									{else}
										<a
												target="_blank"
												href="{$site->dereferrer_link}http://www.adultdvdempire.com/dvd/search?q={$result.title}"
												name="viewade{$result.title}"
												title="Search AdultdvdEmpire page"
										><img
													src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/ade.png"></a>
									{/if}
									{if $result.classused == "hm"}
										<a
												target="_blank"
												href="{$site->dereferrer_link}{$result.directurl}"
												name="viewhm{$result.title}"
												title="View Hot Movies page"
										><img
													src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/hotmovies.png"></a>
									{else}
										<a
												target="_blank"
												href="{$site->dereferrer_link}http://www.hotmovies.com/search.php?words={$result.title}&complete=on&search_in=video_title"
												name="viewhm{$result.title}"
												title="Search Hot Movies page"
										><img
													src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/hotmovies.png"></a>
									{/if}
									{if $result.classused == "pop"}
										<a
												target="_blank"
												href="{$site->dereferrer_link}{$result.directurl}"
												name="viewpop{$result.id}"
												title="View Popporn page"
										><img
													src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/popporn.png"></a>
									{else}
										<a
												target="_blank"
												href="{$site->dereferrer_link}http://www.popporn.com/results/index.cfm?v=4&g=0&searchtext={$result.title}"
												name="viewpop{$result.id}"
												title="Search Popporn page"
										><img
													src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/popporn.png"></a>
									{/if}
									<a
											target="_blank"
											href="{$site->dereferrer_link}http://www.iafd.com/results.asp?searchtype=title&searchstring={$result.title}"
											name="viewiafd{$result.title}"
											title="Search Internet Adult Film Database"
									><img
												src="{$smarty.const.WWW_TOP}/themes/shared/images/icons/iafd.png"></a>
								</center>
							</div>
						</div>
					</td>
					<td colspan="3" class="left">
						<h4>
							<a title="{$result.title|stripslashes|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/xxx?id={$result.id}">{$result.title|stripslashes|escape:"htmlall"}</a>
						</h4>
						{if $result.tagline != ''}
							<b>{$result.tagline}</b>
							<br/>
						{/if}
						{if $result.plot != ''}
							{$result.plot}
							<br/>
							<br/>
						{/if}
						{if $result.genre != ''}
							<b>Genre:</b>
							{$result.genre}
							<br/>
						{/if}
						{if $result.director != ''}
							<b>Director:</b>
							{$result.director}
							<br/>
						{/if}
						{if $result.actors != ''}
							<b>Starring:</b>
							{$result.actors}
							<br/>
							<br/>
						{/if}
						<div class="movextra">
							<table class="table" style="margin-bottom:0px; margin-top:10px">
								{foreach from=$msplits item=m}
									<tr id="guid{$mguid[$m@index]}" {if $m@index > 0}class="mlextra"{/if}>
										<td>
											<div class="icon"><input type="checkbox" class="nzb_check"
																	 value="{$mguid[$m@index]}"/></div>
										</td>
										<td>
											<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"seourl"}">
												&nbsp;{$mname[$m@index]|escape:"htmlall"}</a>
											<ul class="inline">
												<li width="100px">Posted {$mpostdate[$m@index]|timeago}</li>
												<li width="80px">{$msize[$m@index]|fsize_format:"MB"}</li>
												<li width="50px"><a title="View file list"
																	href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}</a>
													<i class="fa fa-file"></i></li>
												<li width="50px"><a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/#comments">{$mcomments[$m@index]}</a>
													<i class="fa fa-comments"></i></li>
												{if isset($mnfo[$m@index]) && $mnfo[$m@index] > 0}
													<li width="50px"><a
															href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}"
															title="View Nfo" class="modal_nfo fa fa-info-sign"
															rel="nfo"></a></li>{/if}
												<li width="50px"><a
															href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}"
															title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}"
															class="fa fa-group"></a></li>
												{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}
													<li width="80px"><a
															href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg"
															name="name{$mguid[$m@index]}" title="Screenshot"
															class="modal_prev label" rel="preview">Preview</a></li>{/if}
												{if $mhaspreview[$m@index]}
													<li width="80px"><a href="#" onclick="return false;"
																		class="mediainfo label"
																		title="{$mguid[$m@index]}">Media</a></li>{/if}
											</ul>
										</td>
										<td class="icons" style='width:100px;'>
											<ul class="inline">
												<li>
													<a class="icon icon_nzb fa fa-cloud-download"
													   style="text-decoration: none; color: #7ab800;"
													   title="Download Nzb"
													   href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}"></a>
												</li>
												<li>
													<a class="icon icon_cart fa fa-shopping-basket"
													   style="text-decoration: none; color: #5c5c5c;"
													   title="Send to my Download Basket">
													</a>
												</li>
												{if $sabintegrated}
													<li>
														<a class="icon icon_sab fa fa-share"
														   style="text-decoration: none; color: #008ab8;" href="#"
														   title="Send to queue">
														</a>
													</li>
												{/if}
												{if isset($nzbgetintegrated)}
													<li>
														<a class="icon icon_nzb fa fa-cloud-downloadget" href="#"
														   title="Send to NZBGet">
															<img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/icons/nzbgetup.png">
														</a>
													</li>
												{/if}
											</ul>
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
							</table>
						</div>
					</td>
				</tr>
			{/foreach}
		</table>
		{if $results|@count > 10}
			<div class="well well-small">
				<div class="nzb_multi_operations">
					<table width="100%">
						<tr>
							<td width="30%">
								With Selected:
								<div class="btn-group">
									<input type="button" class="nzb_multi_operations_download btn btn-small btn-success"
										   value="Download NZBs"/>
									<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info"
										   value="Send to my Download Basket"/>
									{if isset($sabintegrated)}
										<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary"
											   value="Send to queue"/>
									{/if}
									{if isset($nzbgetintegrated)}
										<input type="button"
											   class="nzb_multi_operations_nzbget btn btn-small btn-primary"
											   value="Send to NZBGet"/>
									{/if}
								</div>
								&nbsp;&nbsp;&nbsp;&nbsp;<a title="Switch to List view"
														   href="{$smarty.const.WWW_TOP}/browse?t={$category}"><i
											class="fa fa-lg fa-list-ol"></i></a>
							</td>
							<td width="50%">
								<center>
									{$pager}
								</center>
							</td>
							<td width="20%">
								<div class="pull-right">
									{if $isadmin}
										Admin:
										<div class="btn-group">
											<input type="button"
												   class="nzb_multi_operations_edit btn btn-small btn-warning"
												   value="Edit"/>
											<input type="button"
												   class="nzb_multi_operations_delete btn btn-small btn-danger"
												   value="Delete"/>
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
