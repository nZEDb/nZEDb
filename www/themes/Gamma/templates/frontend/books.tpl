<h2>Browse Books</h2>

<div class="well well-small">
<center>
<form class="form-inline" name="browseby" action="books" style="margin:0;">

		<i class="fa fa-user fa-midt"></i>
		<input class="input input-medium" id="author" type="text" name="author" value="{$author}" placeholder="Author" />

		<i class="fa fa-book fa-midt"></i>
		<input class="input input-medium" id="title" type="text" name="title" value="{$title}" placeholder="Title" />

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
							{if $sabintegrated}<input type="button" class="nzb_multi_operations_sab btn btn-small btn-primary" value="Send to queue" />{/if}
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
		<th width="130"><input type="checkbox" class="nzb_check_all" /></th>
		<th>author<br/>
			<a title="Sort Descending" href="{$orderbyauthor_desc}">
				<i class="fa fa-caret-down"></i>
			</a>
			<a title="Sort Ascending" href="{$orderbyauthor_asc}">
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
		<th>posted<br/>
			<a title="Sort Descending" href="{$orderbyposted_desc}">
				<i class="fa fa-caret-down"></i>
			</a>
			<a title="Sort Ascending" href="{$orderbyposted_asc}">
				<i class="fa fa-caret-up"></i>
			</a>
		</th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td class="mid">
				<div class="movcover">
					<a class="title" href="{$site->dereferrer_link}{$result.url}">
						<img class="shadow img-polaroid" src="{$smarty.const.WWW_TOP}/covers/book/{if $result.cover == 1}{$result.bookinfoid}.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$result.author|escape:"htmlall"} - {$result.title|escape:"htmlall"}" />

					</a>
					<div class="movextra">
						<center>
						{if $result.url != ""}<a class="rndbtn badge badge-amaz" target="_blank" href="{$site->dereferrer_link}{$result.url}" name="amazon{$result.bookinfoid}" title="View amazon page">Amazon</a>{/if}
						<a class="rndbtn badge" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}" title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
						</center>
					</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h4><a href="{$smarty.const.WWW_TOP}/books/?author={$result.author}">{$result.author|escape:"htmlall"}</a> - {$result.title|escape:"htmlall"}</h4>
				{if $result.review != ""}
					<span class="descinitial">
						{$result.review|escape:"htmlall"|nl2br|magicurl|truncate:"350":"
					</span>
					<a class=\"descmore\" href=\"#\">more...</a>"}
					{if $result.review|strlen > 350}
						<span class="descfull">{$result.review|escape:"htmlall"|nl2br|magicurl}</span>
					{else}
						</span>
					{/if}
					<br /><br />
				{/if}

				{if $result.publisher != ""}<b>Publisher:</b> {$result.publisher|escape:"htmlall"}<br />{/if}
				{if $result.publishdate != ""}<b>Published:</b> {$result.publishdate|date_format}<br />{/if}
				{if $result.pages != ""}<b>Pages:</b> {$result.pages}<br />{/if}
				{if $result.isbn != ""}<b>ISBN:</b> {$result.isbn}<br />{/if}

				<div class="movextra" style="margin-top:10px">
					<table class="table">
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
								<div class="icon"><input type="checkbox" class="nzb_check" value="{$mguid[$m@index]}" /></div>
							</td>
							<td>
								<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"seourl"}">{$mname[$m@index]|escape:"htmlall"}</a><br/>
								<ul class="inline">
									<li width="50px"><b>Info:</b></li>
									<li width="100px">Posted {$mpostdate[$m@index]|timeago}</li>
									<li width="80px">{$msize[$m@index]|fsize_format:"MB"}</li>
									<li width="50px"><a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]}</a> <i class="fa fa-file"></i></li>
									<li width="50px"><a title="View comments for {$mname[$m@index]|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/#comments">{$mcomments[$m@index]}</a> <i class="fa fa-comments-o"></i></li>
									<li width="50px">{$mgrabs[$m@index]} <i class="fa fa-cloud-download"></i></li>
									{if $mnfo[$m@index] > 0}
									<li width="50px"><a href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}" title="View Nfo" class="modal_nfo badge" rel="nfo">Nfo</a></li>
									{/if}
									{if $mpass[$m@index] == 1}
									<li width="50px">Passworded, {elseif $mpass[$m@index] == 2}Potential Password</li>
									{/if}
									<li width="50px"><a href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}" class="badge" title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}">Grp</a></li>
									{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}
									<li width="50px"><a href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg" name="name{$mguid[$m@index]}" title="Screenshot of {$mname[$m@index]|escape:"htmlall"}" class="modal_prev badge" rel="preview">Preview</a></li>
									{/if}

									{if $minnerfiles[$m@index] > 0}
									<li width="50px"><a href="#" onclick="return false;" class="mediainfo badge" title="{$mguid[$m@index]}">Media</a></li>
									{/if}

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
							<tr><td colspan="5"><a class="mlmore" href="#">{$m@total-2} more...</a></td></tr>
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
