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
	<table class="table table-striped table-hover table-condensed data" id="coverstable">
		<thead>
			<tr>
				<th><input type="checkbox" class="nzb_check_all"></th>
				<th>artist <a title="Sort Descending" href="{$orderbyartist_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbyartist_asc}"><i class="icon-chevron-up"></i></a></th>
				<th>year <a title="Sort Descending" href="{$orderbyyear_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbyyear_asc}"><i class="icon-chevron-up"></i></a></th>
				<th>genre <a title="Sort Descending" href="{$orderbygenre_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbygenre_asc}"><i class="icon-chevron-up"></i></a></th>
				<th>posted <a title="Sort Descending" href="{$orderbyposted_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbyposted_asc}"><i class="icon-chevron-up"></i></a></th>
				<th>size <a title="Sort Descending" href="{$orderbysize_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbysize_asc}"><i class="icon-chevron-up"></i></a></th>
				<th>files <a title="Sort Descending" href="{$orderbyfiles_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbyfiles_asc}"><i class="icon-chevron-up"></i></a></th>
				<th>stats <a title="Sort Descending" href="{$orderbystats_desc}"><i class="icon-chevron-down"></i></a><a
							title="Sort Ascending" href="{$orderbystats_asc}"><i class="icon-chevron-up"></i></a></th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$results item=result}
			<tr>
				<td style="width:150px;padding:10px;text-align:center;">
					<div class="movcover">
						<a
							class="title thumbnail"
							title="View amazon page"
							href="{$site->dereferrer_link}{$result.url}"
						><img
							height="130px"
							width="130px"
							class="shadow"
							src="{$smarty.const.WWW_TOP}/covers/music/{if $result.cover == 1}{$result.musicinfoid}.jpg{else}no-cover.jpg{/if}"
							alt="{$result.artist|escape:"htmlall"} - {$result.title|escape:"htmlall"}"
						></a>
					</div>
					<div class="relextra" style="margin-top:5px;">
						{if $result.url != ""}
							<a
								class="label"
								href="{$site->dereferrer_link}{$result.url}"
								name="amazon{$result.musicinfoid}"
								title="View amazon page"
							><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/amazon.png"></a>
						{/if}
						<a
							class="label"
							target="_blank"
							href="{$site->dereferrer_link}http://www.allmusic.com/search/artist/{$result.artist|escape:"htmlall"}"
							name="allmusic{$result.id}"
							title="Find artist on AllMusic"
						><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/allmusic.png"></a>
						<a
							class="label"
							target="_blank"
							href="{$site->dereferrer_link}http://www.discogs.com/search/?q={$result.artist|escape:"htmlall"}&type=artist"
							name="discogs{$result.id}"
							title="Find artist on Discogs"
							><img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/discogs.png"></a>
					</div>
					<hr>
					<div>
						<a
							class="label label-info"
							href="{$smarty.const.WWW_TOP}/music?artist={$result.artist|escape:"url"}"
							title="View albums from this artist"
						>Similar</a>
					</div>
				</td>
				<td colspan="7" class="left" id="guid{$result.guid}">
					<h4>{$result.artist}{" - "}{$result.title}</h4>
					{if $result.genre_id != ""}
						<b>Genre:</b>
						<a href="{$smarty.const.WWW_TOP}/music/?genre={$result.genre_id}">{$result.genre|escape:"htmlall"}</a>
						<br>
					{/if}
					{if $result.publisher != ""}
						<b>Publisher:</b> {$result.publisher|escape:"htmlall"}<br>
					{/if}
					{if $result.releasedate != ""}
						<b>Released:</b> {$result.releasedate|date_format}<br>
					{/if}
					{if $result.review != ""}
						<b>Review:</b> {$result.review|escape:'htmlall'}<br>
					{/if}
					{if $result.haspreview == 2 && $userdata.canpreview == 1}
						<b>Preview:</b>
						<a
							href="#"
							name="audio{$result.guid}" title="Listen to {$result.searchname|escape:"htmlall"}"
							class="audioprev" rel="audio"
						><i class="icon-music"></i></a>
						<audio
							id="audprev{$result.guid}"
							src="{$smarty.const.WWW_TOP}/covers/audio/{$result.guid}.mp3"
							preload="none"
						></audio>
					{/if}
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
											<b>{$mname[$m@index]|escape:"htmlall"|wordwrap:80:"\n":true}</b>
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
															title="View Nfo" class="modal_nfo"
															rel="nfo"
														><i class="icon-info-sign"></i></a></span
													>
												{/if}
												{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}
													<span class="label label-default">
														<a
															href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg"
															name="name{$mguid[$m@index]}"
															title="Screenshot of {$mname[$m@index]|escape:"htmlall"}"
															class="modal_prev" rel="preview"
														><i class="icon-camera"></i></a></span
													>
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
		<p>No music releases have amazon covers.
			<br>This might mean the Administrator's Amazon API keys are wrong, or he has file permission issues, or he has disabled looking up Amazon.
			<br>This could also mean there are no music releases.
			<br>Please try looking in the
			<a href="{$smarty.const.WWW_TOP}/browse?t={$category}" style="font-weight:strong;text-decoration:underline;"
			>list view</a>, which does not require Amazon covers.
		</p>
	</div>
{/if}
