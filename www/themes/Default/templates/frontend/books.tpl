{if $site->adbrowse}
	{$site->adbrowse}
{/if}
<h1>Browse Books</h1>

<form name="browseby" action="books">
	<table class="rndbtn" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<th class="left"><label for="title">Title</label></th>
			<th class="left"><label for="author">Author</label></th>
			<th class="left"><label for="category">Category</label></th>
			<th></th>
		</tr>
		<tr>
			<td><input id="title" type="text" name="title" value="{$title}" size="15"/></td>
			<td><input id="author" type="text" name="author" value="{$author}" size="15"/></td>
			<td>
				<select id="category" name="t">
					<option class="grouping" value="8000"></option>
					{foreach from=$catlist item=ct}
						<option {if $ct.id==$category}selected="selected"{/if} value="{$ct.id}">{$ct.title}</option>
					{/foreach}
				</select>
			</td>
			<td><input type="submit" value="Go"/></td>
		</tr>
	</table>
</form>
<p></p>

{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">

		<div class="nzb_multi_operations">
			View: <b>Covers</b> | <a href="{$smarty.const.WWW_TOP}/browse?t={$category}">List</a><br/>
			<small>With Selected:</small>
			<input type="button" class="nzb_multi_operations_download" value="Download NZBs"/>
			<input type="button" class="nzb_multi_operations_cart" value="Add to Cart"/>
			{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab" value="Send to my Queue"/>{/if}
		</div>
		<br/>

		{$pager}

		<table style="width:100%;" class="data highlight icons" id="coverstable">
			<tr>
				<th width="130"><input type="checkbox" class="nzb_check_all"/></th>
				<th>title<br/><a title="Sort Descending" href="{$orderbytitle_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbytitle_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>author<br/><a title="Sort Descending" href="{$orderbyauthor_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbyauthor_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>posted<br/><a title="Sort Descending" href="{$orderbyposted_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbyposted_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>size<br/><a title="Sort Descending" href="{$orderbysize_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbysize_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>files<br/><a title="Sort Descending" href="{$orderbyfiles_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbyfiles_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>stats<br/><a title="Sort Descending" href="{$orderbystats_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbystats_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
			</tr>

			{foreach from=$results item=result}
				<tr class="{cycle values=",alt"}">
					<td class="mid">
						<div class="bookcover">
							<a class="title thumbnail" title="View amazon page"
							   href="{$site->dereferrer_link}{$result.url}">
								<img class="shadow"
									 src="{$smarty.const.WWW_TOP}/covers/book/{if $result.cover == 1}{$result.bookinfoid}.jpg{else}no-cover.jpg{/if}"
									 width="120" border="0" alt="{$result.title|escape:"htmlall"}">
							</a>

							<div class="movextra">
								{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
														 title="View Nfo" class="rndbtn modal_nfo" rel="nfo">
										Nfo</a>{/if}
								<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}{$result.url}"
								   name="amazon{$result.bookinfoid}" title="View amazon page">Amazon</a>
								<a class="rndbtn" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}"
								   title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
							</div>
						</div>
					</td>
					<td colspan="8" class="left" id="guid{$result.guid}">
						<h2>{$result.author|stripslashes|escape:"htmlall"}{" - "}{$result.title|stripslashes|escape:"htmlall"}
						</h2>
						{if $result.genre != "null"}<b>Genre:</b>{$result.genre|escape:'htmlall'}<br/>{/if}
						{if $result.publisher != ""}<b>Publisher:</b>{$result.publisher}<br/>{/if}
						{if $result.publishdate != ""}<b>Released:</b>{$result.publishdate|date_format}<br/>{/if}
						{if $result.pages != ""}<b>Pages:</b>{$result.pages}<br/>{/if}
						{if $result.salesrank != ""}<b>Amazon Rank:</b>{$result.salesrank}<br/>{/if}
						{if $result.overview != "null"}<b>Overview:</b>{$result.overview|escape:'htmlall'}<br/>{/if}
						<br/>

						<div class="movextra">
							<table>
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
								{foreach from=$msplits item=m}
									<tr id="guid{$mguid[$m@index]}" {if $m@index > 1}class="mlextra"{/if}>
										<td>
											<div class="icon"><input type="checkbox" class="nzb_check"
																	 value="{$mguid[$m@index]}"/></div>
										</td>
										<td>
											<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$mname[$m@index]|escape:"htmlall"}</a>

											<div>
												<i class="icon-calendar"></i> Posted {$mpostdate[$m@index]|timeago} | <i
														class="icon-hdd"></i> {$msize[$m@index]|fsize_format:"MB"} | <i
														class="icon-file"></i> <a title="View file list"
																				  href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}
													files</a> | <i class="icon-comments"></i> <a
														title="View comments for {$mname[$m@index]|escape:"htmlall"}"
														href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}#comments">{$mcomments[$m@index]}
													cmt{if $mcomments[$m@index] != 1}s{/if}</a> | <i
														class="icon-download"></i> {$mgrabs[$m@index]}
												grab{if $mgrabs[$m@index] != 1}s{/if} |
												{if $mnfo[$m@index] > 0}<a
													href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}"
													title="View Nfo" class="modal_nfo" rel="nfo">Nfo</a> | {/if}
												{if $mpass[$m@index] == 1}Passworded | {elseif $mpass[$m@index] == 2}Potential Password | {/if}
												<a href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}"
												   title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}">Grp</a>
												{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1} | <a
														href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg"
														name="name{$mguid[$m@index]}"
														title="Screenshot of {$mname[$m@index]|escape:"htmlall"}"
														class="modal_prev" rel="preview">Preview</a>{/if}
												{if $minnerfiles[$m@index] > 0} | <a href="#" onclick="return false;"
																					 class="mediainfo"
																					 title="{$mguid[$m@index]}">
														Media</a>{/if}
											</div>
										</td>
										<td class="icons">
											<div class="icon icon_nzb"><a title="Download Nzb"
																		  href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}">
													&nbsp;</a></div>
											<div class="icon icon_cart" title="Add to Cart"></div>
											{if $sabintegrated}
												<div class="icon icon_sab" title="Send to my Queue"></div>
											{/if}
										</td>
									</tr>
									{if $m@index == 1 && $m@total > 2}
										<tr>
											<td colspan="5"><a class="mlmore" href="#">{$m@total-2} more...</a></td>
										</tr>
									{/if}
								{/foreach}
							</table>
						</div>
					</td>
				</tr>
			{/foreach}

		</table>

		<br/>

		{$pager}

		<div class="nzb_multi_operations">
			<small>With Selected:</small>
			<input type="button" class="nzb_multi_operations_download" value="Download NZBs"/>
			<input type="button" class="nzb_multi_operations_cart" value="Add to Cart"/>
			{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab" value="Send to my Queue"/>{/if}
		</div>

	</form>
{else}
	<h4>There doesn't seem to be any releases here. Please try the <a
				href="{$smarty.const.WWW_TOP}/browse?t={$category}">list</a> view.</h4>
{/if}

<br/><br/><br/>
