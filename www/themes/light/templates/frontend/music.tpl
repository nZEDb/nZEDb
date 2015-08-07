{if $site->adbrowse}
	{$site->adbrowse}
{/if}
<h1>Browse Music</h1>
<form name="browseby" action="music">
	<table class="rndbtn" border="0" cellpadding="2" cellspacing="0">
		<tr>
			<th class="left"><label for="musicartist">Artist</label></th>
			<th class="left"><label for="musictitle">Title</label></th>
			<th class="left"><label for="genre">Genre</label></th>
			<th class="left"><label for="year">Year</label></th>
			<th class="left"><label for="category">Category</label></th>
			<th></th>
		</tr>
		<tr>
			<td><input id="musicartist" type="text" name="artist" value="{$artist}" size="15"/></td>
			<td><input id="musictitle" type="text" name="title" value="{$title}" size="15"/></td>
			<td>
				<select id="genre" name="genre">
					<option class="grouping" value=""></option>
					{foreach from=$genres item=gen}
						<option {if $gen.id == $genre}selected="selected"{/if}
								value="{$gen.id}">{$gen.title|escape:"htmlall"}</option>
					{/foreach}
				</select>
			</td>
			<td>
				<select id="year" name="year">
					<option class="grouping" value=""></option>
					{foreach from=$years item=yr}
						<option {if $yr==$year}selected="selected"{/if} value="{$yr}">{$yr}</option>
					{/foreach}
				</select>
			</td>
			<td>
				<select id="category" name="t">
					<option class="grouping" value="3000"></option>
					{foreach from=$catlist item=ct}
						<option {if $ct.id==$category}selected="selected"{/if} value="{$ct.id}">{$ct.title}</option>
					{/foreach}
				</select>
			</td>
			<td><input class='rndbtn' type="submit" value="Go"/></td>
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
				<th>artist<br/><a title="Sort Descending" href="{$orderbyartist_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbyartist_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>year<br/><a title="Sort Descending" href="{$orderbyyear_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbyyear_asc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_up.gif" alt=""/></a>
				</th>
				<th>genre<br/><a title="Sort Descending" href="{$orderbygenre_desc}"><img
								src="{$smarty.const.WWW_TOP}/themes_shared/images/sorting/arrow_down.gif"
								alt=""/></a><a title="Sort Ascending" href="{$orderbygenre_asc}"><img
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
						<div class="movcover">
							<a class="title" title="View details"
							   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">
								<img class="shadow"
									 src="{$smarty.const.WWW_TOP}/covers/music/{if $result.cover == 1}{$result.musicinfoid}.jpg{else}no-cover.jpg{/if}"
									 width="120" border="0"
									 alt="{$result.artist|escape:"htmlall"} - {$result.title|escape:"htmlall"}"/>
							</a>
							<div class="movextra">
								{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}"
														 title="View Nfo" class="rndbtnsml modal_nfo" rel="nfo">
										Nfo</a>{/if}
								<a class="rndbtnsml" target="_blank" href="{$site->dereferrer_link}{$result.url}"
								   name="amazon{$result.musicinfoid}" title="View amazon page">Amazon</a>
								<a class="rndbtnsml" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}"
								   title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
							</div>
						</div>
					</td>
					<td colspan="7" class="left" id="guid{$result.guid}">
						<h2>{$result.artist}{" - "}{$result.title}</h2>
						{if $result.genre_id != ""}
							<b>Genre:</b>
							<a href="{$smarty.const.WWW_TOP}/music/?genre={$result.genre_id}">{$result.genre|escape:"htmlall"}</a>
							<br>
						{/if}
						{if $result.publisher != ""}<b>Publisher:</b>{$result.publisher|escape:"htmlall"}<br/>{/if}
						{if $result.releasedate != ""}<b>Released:</b>{$result.releasedate|date_format}<br/>{/if}
						{if $result.review != ""}<b>Review:</b>{$result.review|escape:'htmlall'}<br>{/if}
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
   {header("Location: {$smarty.const.WWW_TOP}/browse?t={$category}")}
{/if}
<br/><br/><br/>