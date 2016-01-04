<h2>Browse Music</h2>

<div class="well well-small">
<center>
<form class="form-inline" name="browseby" action="music" style="margin:0;">
		<i class="fa fa-user fa-midt"></i>
		<input class="input input-medium" id="musicartist" type="text" name="artist" value="{$artist}" placeholder="Artist" />

		<i class="fa fa-music fa-midt"></i>
		<input class="input input-medium" id="musictitle" type="text" name="title" value="{$title}" placeholder="Title" />
		<i class="fa fa-inbox fa-midt"></i>
			<select class="input input-small" id="genre" name="genre">
				<option class="grouping" value=""></option>
				{foreach from=$genres item=gen}
					<option {if $gen.id == $genre}selected="selected"{/if} value="{$gen.id}">{$gen.title|escape:"htmlall"}</option>
				{/foreach}
			</select>
		<i class="fa fa-calendar fa-midt"></i>
			<select class="input input-small" id="year" name="year">
				<option class="grouping" value=""></option>
				{foreach from=$years item=yr}
					<option {if $yr==$year}selected="selected"{/if} value="{$yr}">{$yr}</option>
				{/foreach}
			</select>
		<i class="fa fa-flag fa-midt"></i>
			<select class="input input-small" id="category" name="t">
			<option class="grouping" value="3000"></option>
				{foreach from=$catlist item=ct}
					<option {if $ct.id==$category}selected="selected"{/if} value="{$ct.id}">{$ct.title}</option>
				{/foreach}
			</select>
		<input class="btn btn-success" type="submit" value="Go" />
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
							<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
							<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
							{if isset($sabintegrated)}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
							{if isset($nzbgetintegrated)}<input type="button" class="nzb_multi_operations_nzbget btn btn-small btn-primary" value="Send to NZBGet" />{/if}
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
						{if isset($section) && $section != ''}
							<div class="pull-right">
							{if $isadmin}
								Admin:
								<div class="btn-group">
									<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
									<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
								</div>
								&nbsp;
							{/if}
							</div>
						{/if}
					</td>
				</tr>
			</table>
		</div>
	</div>
<table style="width:100%;" class="data highlight icons table table-striped" id="coverstable">
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
		{assign var="mfailed" value=","|explode:$result.grp_release_failed}
		{assign var="mpass" value=","|explode:$result.grp_release_password}
		{assign var="minnerfiles" value=","|explode:$result.grp_rarinnerfilecount}
		{assign var="mhaspreview" value=","|explode:$result.grp_haspreview}
		{foreach from=$msplits item=m}
		<tr class="{cycle values=",alt"}">
			<td class="mid">
				<div class="movcover">
					<a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">
						<img class="shadow img-polaroid" src="{$smarty.const.WWW_TOP}/covers/music/{if $result.cover == 1}{$result.musicinfoid}.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$result.artist|escape:"htmlall"} - {$result.title|escape:"htmlall"}" />
					</a>
					<div class="movextra">
						<center>
						{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}" title="View Nfo" class="rndbtn modal_nfo badge" rel="nfo">Nfo</a>{/if}
						{if $result.url != ""}<a class="rndbtn badge badge-amaz" target="_blank" href="{$site->dereferrer_link}{$result.url}" name="amazon{$result.musicinfoid}" title="View amazon page">Amazon</a>{/if}
						<a class="rndbtn badge" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}" title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
						</center>
					</div>
				</div>
			</td>
			<td colspan="7" class="left" id="guid{$mguid[$m@index]}">
				<ul class="inline">
					<li><h4><a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}">{$result.artist|escape:"htmlall"} - {$result.title|escape:"htmlall"}</a> (<a class="title" title="{$result.year}" href="{$smarty.const.WWW_TOP}/music?year={$result.year}">{$result.year}</a>)</h4></li>
					<li style="vertical-align:text-bottom;"><div class="icon"><input type="checkbox" class="nzb_check" value="{$mguid[$m@index]}" /></div></li>
					<li style="vertical-align:text-bottom;"><a class="icon icon_nzb fa fa-cloud-download" style="text-decoration: none; color: #7ab800;" title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}"></a></li>
					<li style="vertical-align:text-bottom;"><a href="#" class="icon icon_cart fa fa-shopping-basket" style="text-decoration: none; color: #5c5c5c;" title="Send to my Download Basket"></a></li>
					<li style="vertical-align:text-bottom;">{if $sabintegrated}<div><a href="#" class="icon icon_sab fa fa-share" style="text-decoration: none; color: #008ab8;"  title="Send to my Queue"></a></div>{/if}</li>
					<li style="vertical-align:text-bottom;">{if isset($nzbgetintegrated)}<div><a href="#" class="icon icon_nzb fa fa-cloud-downloadget" title="Send to my NZBGet"><img src="{$smarty.const.WWW_TOP}/themes/Gamma/images/icons/nzbgetup.png"></a></div>{/if}</li>
				</ul>
				{if $result.genre != ""}<b>Genre:</b> <a href="{$smarty.const.WWW_TOP}/music/?genre={$result.genreid}">{$result.genre|escape:"htmlall"}</a><br />{/if}
				{if $result.publisher != ""}<b>Publisher:</b> {$result.publisher|escape:"htmlall"}<br />{/if}
				{if $result.releasedate != ""}<b>Released:</b> {$result.releasedate|date_format}<br />{/if}
				{if isset($result.haspreview) && $result.haspreview == 2 && $userdata.canpreview == 1}<b>Preview:</b> <a href="#" name="audio{$mguid[$m@index]}" title="Listen to {$result.title|escape:"htmlall"}" class="audioprev rndbtn" rel="audio">Listen</a><audio id="audprev{$mguid[$m@index]}" src="{$smarty.const.WWW_TOP}/covers/audio/{$mguid[$m@index]}.mp3" preload="none"></audio>{/if}
				<div class="movextra">
					<b>{$result.title|escape:"htmlall"}</b>
					<a class="rndbtn btn btn-mini btn-info" href="{$smarty.const.WWW_TOP}/music?artist={$result.artist|escape:"url"}" title="View similar nzbs">Similar</a>
					{if $isadmin}
						<a class="rndbtn btn btn-mini btn-warning" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.releaseid}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a>
						<a class="rndbtn confirm_action btn btn-mini btn-danger" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.releaseid}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Delete</a>
					{/if}
					<br/>
					<ul class="inline">
						<li width="50px"><b>Info:</b></li>
						<li width="100px">Posted {$mpostdate[$m@index]|timeago}</li>
						<li width="80px">{$msize[$m@index]|fsize_format:"MB"}</li>
						<li width="50px"><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}</a><i class="fa fa-file"></i></li>
						<li width="50px"><a title="View comments for {$result.title|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}#comments">{$mcomments[$m@index]}</a><i class="fa fa-comments-o"></i></li>
						<li width="50px">{$mgrabs[$m@index]} <i class="fa fa-cloud-download"></i></li>
					</ul>
				</div>
			</td>
		</tr>
		{/foreach}
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
							<input type="button" class="nzb_multi_operations_download btn btn-small btn-success" value="Download NZBs" />
							<input type="button" class="nzb_multi_operations_cart btn btn-small btn-info" value="Send to my Download Basket" />
							{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
							{if isset($nzbgetintegrated)}<input type="button" class="nzb_multi_operations_nzbget btn btn-small btn-primary" value="Send to NZBGet" />{/if}
						</div>
					</td>
					<td width="50%">
						<center>
							{$pager}
						</center>
					</td>
					<td width="20%">
						{if isset($section) && $section != ''}
							<div class="pull-right">
							{if $isadmin}
								Admin:
								<div class="btn-group">
									<input type="button" class="nzb_multi_operations_edit btn btn-small btn-warning" value="Edit" />
									<input type="button" class="nzb_multi_operations_delete btn btn-small btn-danger" value="Delete" />
								</div>
								&nbsp;
							{/if}
							<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}"><i class="fa fa-th-list"></i></a>
							&nbsp;
							</div>
						{/if}
					</td>
				</tr>
			</table>
		</div>
	</div>
	{/if}
	</form>
{/if}
