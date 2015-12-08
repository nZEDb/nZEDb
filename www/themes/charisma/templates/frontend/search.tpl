{if {$site->adbrowse} != ''}
	{$site->adbrowse}
{/if}
<div class="header">
	<h2>{$site->title} > <strong>Search</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Search
		</ol>
	</div>
</div>
<div>
	<center>
		<a href="#" onclick="if (jQuery(this).text() == 'Advanced Search')
					jQuery(this).text('Basic Search');
				else
					jQuery(this).text('Advanced Search');
				jQuery('#sbasic,#sadvanced').toggle();
				return false;">{if $sadvanced}Basic{else}Click For Advanced{/if} Search
		</a>
	</center>
</div>
<br>
<div class="well well-sm">
	<form method="get" action="{$smarty.const.WWW_TOP}/search">
		<div id="sbasic" class="row" style="text-align:center;{if $sadvanced} display:none;{/if}">
			<div class="col-md-6">
				<input id="search" class="form-control" maxlength="50" name="search" value="{$search|escape:'html'}"
					   type="search" placeholder="What are you looking for?"/>
			</div>
			<div class="col-md-6">
				<input type="hidden" name="t" value="{if $category[0]!=""}{$category[0]}{else}-1{/if}" id="search_cat"/>
				<input type="hidden" name="search_type" value="basic" id="search_type"/>
				<input id="search_search_button" class="btn btn-primary" type="submit" value="Search"/>
			</div>
		</div>
	</form>
</div>
<form method="get" action="{$smarty.const.WWW_TOP}/search">
	<div id="sadvanced" {if not $sadvanced}style="display:none"{/if}>
		<center>
			<table class="data table table-striped table-condensed table-responsive">
				<tr>
					<th><label for="searchadvr">Release Name:</label></th>
					<td><input class="searchadv" id="searchadvr" name="searchadvr" value="{$searchadvr|escape:'html'}"
							   type="text"></td>
				</tr>
				<tr>
					<th><label for="searchadvsubject">Usenet Name:</label></th>
					<td><input class="searchadv" id="searchadvsubject" name="searchadvsubject"
							   value="{$searchadvsubject|escape:'html'}" type="text"></td>
				</tr>
				<tr>
					<th><label for="searchadvposter">Poster:</label></th>
					<td><input class="searchadv" id="searchadvposter" name="searchadvposter"
							   value="{$searchadvposter|escape:'html'}" type="text"></td>
				</tr>
				<tr>
					<th><label for="searchadvfilename">Filename:</label></th>
					<td><input class="searchadv" id="searchadvfilename" name="searchadvfilename" value="{$searchadvfilename|escape:'html'}" type="text"/></td>
				</tr>
				<tr>
				<tr>
					<th><label for="searchadvdaysnew">Min age(days):</label></th>
					<td>
						<input class="searchdaysinput" id="searchadvdaysnew" name="searchadvdaysnew"
							   value="{$searchadvdaysnew|escape:'html'}" type="text">
					</td>
				</tr>
				<tr>
					<th><label for="searchadvdaysold">Max age(days):</label></th>
					<td>
						<input class="searchdaysinput" id="searchadvdaysold" name="searchadvdaysold"
							   value="{$searchadvdaysold|escape:'html'}" type="text">
					</td>
				</tr>
				<tr>
					<th><label for="searchadvgroups">Group:</label></th>
					<td>{html_options class="searchadvbtns" id="searchadvgroups" name="searchadvgroups" options=$grouplist selected=$selectedgroup}</td>
				</tr>
				<tr>
					<th><label for="searchadvcat">Category:</label></th>
					<td>{html_options class="searchadvbtns" id="searchadvcat" name="searchadvcat" options=$catlist selected=$selectedcat}</td>
				</tr>
				<tr>
					<th><label for="searchadvsizefrom">Min/Max Size:</label></th>
					<td>
						{html_options id="searchadvsizefrom" name="searchadvsizefrom" options=$sizelist selected=$selectedsizefrom}
						{html_options id="searchadvsizeto" name="searchadvsizeto" options=$sizelist selected=$selectedsizeto}
					</td>
				</tr>
				<tr>
					<th><label for="searchadvhasnfo">NFO/Comments:</label></th>
					<td>
						<input type="hidden" name="searchadvhasnfo" value="0">
						<input type="checkbox" name="searchadvhasnfo" value="1">
						<input type="hidden" name="searchadvhascomments" value="0">
						<input type="checkbox" name="searchadvhascomments" value="1">
						<div style="float:right;"><input type="hidden" name="search_type" value="adv" id="search_type">
							<input id="search_adv_button" type="submit" value="search">
						</div>
					</td>
				</tr>
			</table>
		</center>
	</div>
</form>
{if $results|@count == 0 && ($search || $subject|| $searchadvr|| $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) != ""}
	<center>
		<div class="nosearchresults">
			Your search did not match any releases.
			<br><br>
			Suggestions:
			<br><br>
			<ul>
				<center>
					<li>Make sure all words are spelled correctly.</li>
				</center>
				<center>
					<li>Try different keywords.</li>
				</center>
				<center>
					<li>Try more general keywords.</li>
				</center>
				<center>
					<li>Try fewer keywords.</li>
				</center>
			</ul>
		</div>
	</center>
{elseif ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) == ""}
{else}
	<form style="padding-top:10px;" id="nzb_multi_operations_form" method="get" action="{$smarty.const.WWW_TOP}/search">
		<div class="row">
			<div class="col-md-8">
				{if isset($shows)}
					<p>
						<a href="{$smarty.const.WWW_TOP}/series"
						   title="View available TV series">Series List</a> |
						<a title="Manage your shows"
						   href="{$smarty.const.WWW_TOP}/myshows">Manage My Shows</a> |
						<a title="All releases in your shows as an RSS feed"
						   href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">Rss
							Feed</a>
					</p>
				{/if}
				<div class="nzb_multi_operations">
					{if isset($section) && $section != ''}View:
						<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}">Covers</a>
						|
						<b>List</b>
						<br/>
					{/if}
					With Selected:
					<div class="btn-group">
						<input type="button"
							   class="nzb_multi_operations_download btn btn-sm btn-success"
							   value="Download NZBs"/>
						<input type="button"
							   class="nzb_multi_operations_cart btn btn-sm btn-info"
							   value="Send to my Download Basket"/>
						{if isset($sabintegrated)}
							<input type="button"
								   class="nzb_multi_operations_sab btn btn-sm btn-primary"
								   value="Send to Queue"/>
						{/if}
						{if isset($nzbgetintegrated)}
							<input type="button"
								   class="nzb_multi_operations_nzbget btn btn-sm btn-primary"
								   value="Send to NZBGet"/>
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
		<table class="data table table-condensed table-striped table-responsive table-hover" id="browsetable">
			<thead>
			<tr>
				<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all"/></th>
				<th>Name
					<a title="Sort Descending" href="{$orderbyname_desc}">
						<i class="fa-icon-caret-down text-muted"> </i>
					</a>
					<a title="Sort Ascending" href="{$orderbyname_asc}">
						<i class="fa-icon-caret-up text-muted"> </i>
					</a>
				</th>
				<th>Category</th>
				<th>Posted</th>
				<th>Size</th>
				<th>Files</th>
				<th>Downloads</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$results item=result}
				<tr class="{cycle values=",alt"}{if $lastvisit|strtotime<$result.adddate|strtotime} new{/if}"
					id="guid{$result.guid}">
					<td class="check">
						<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check"
							   value="{$result.guid}">
					</td>
					<td class="item">
						<label for="chk{$result.guid|substr:0:7}">
							<a class="title" title="View details"
							   href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.searchname|escape:"htmlall"|truncate:150:"...":true}</a>{if $result.failed > 0} <i class="fa fa-exclamation-circle" style="color: red" title="This release has failed to download for some users"></i>{/if}</label value="Searchname">
						<div class="resextra">
							<div class="btns" style="float:right">
								{release_flag($result.searchname, browse)}
								{if $result.passwordstatus == 1}
									<img title="RAR/ZIP Possibly Passworded."
										 src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/lock2.png"
										 alt="RAR/ZIP Possibly Passworded.">
								{elseif $result.passwordstatus == 2}
									<img title="RAR/ZIP Possibly Damaged."
										 src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/broken.png"
										 alt="RAR/ZIP Possibly Damaged.">
								{elseif $result.passwordstatus == 10}
									<img title="RAR/ZIP is Passworded."
										 src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/lock.gif"
										 alt="RAR/ZIP is Passworded.">
								{/if}
								{if $result.videostatus > 0}
									<a
											class="model_prev label label-default"
											href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}"
											title="This release has a video preview."
											rel="preview"
											><i class="icon-youtube-play"></i>
									</a>
								{/if}
								{if $result.nfoid > 0}
									<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo"
									   class="modal_nfo label label-default" rel="nfo">Nfo</a>
								{/if}
								{if $result.imdbid > 0}
									<a href="#" name="name{$result.imdbid}" title="View movie info"
									   class="modal_imdb label label-default" rel="movie">Cover</a>
								{/if}
								{if $result.haspreview == 1 && $userdata.canpreview == 1}
								<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg"
								   name="name{$result.guid}" title="Screenshot of {$result.searchname|escape:"htmlall"}"
								   class="modal_prev label label-default" rel="preview">Preview</a>{/if}
								{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
								<a href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg"
								   name="name{$result.guid}" title="Sample of {$result.searchname|escape:"htmlall"}"
								   class="modal_prev label label-default" rel="preview">Sample</a>{/if}
								{if $result.musicinfoid > 0}
									<a href="#" name="name{$result.musicinfoid}" title="View music info"
									   class="modal_music label label-default" rel="music">Cover</a>
								{/if}
								{if $result.consoleinfoid > 0}
									<a href="#" name="name{$result.consoleinfoid}" title="View console info"
									   class="modal_console label label-default" rel="console">Cover</a>
								{/if}
								{if $result.videos_id > 0}
									<a class="label label-default"
									   href="{$smarty.const.WWW_TOP}/series/{$result.videos_id}" title="View all episodes">View
										Series</a>
								{/if}
								{if $result.anidbid > 0}
									<a class="label label-default"
									   href="{$smarty.const.WWW_TOP}/anime/{$result.anidbid}" title="View all episodes">View
										Anime</a>
								{/if}
								{if isset($result.firstaired) && $result.firstaired != ''}
									<span class="seriesinfo label label-default"
										  title="{$result.guid}">Aired {if $result.firstaired|strtotime > $smarty.now}in future{else}{$result.firstaired|daysago}{/if}</span>
								{/if}
								{if $result.group_name != ""}
									<a class="label label-default"
									   href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}"
									   title="Browse {$result.group_name}">{$result.group_name|escape:"htmlall"|replace:"alt.binaries.":"a.b."}</a>
								{/if}
								{if $result.failed > 0}<span class="label label-default">
									<i class ="fa fa-thumbs-o-up"></i> {$result.grabs} Grab{if $result.grabs != 1}s{/if} / <i class ="fa fa-thumbs-o-down"></i> {$result.failed} Failed Download{if $result.failed != 1}s{/if}</span>
								{/if}
							</div>
						</div>
					</td>
					<td class="category">
						<a title="Browse {$result.category_name}"
						   href="{$smarty.const.WWW_TOP}/browse?t={$result.categoryid}">{$result.category_name}</a>
					</td>
					<td class="posted" title="{$result.postdate}">
						{$result.postdate|timeago}
					</td>
					<td class="size">
						{$result.size|fsize_format:"MB"}
						{if $result.completion > 0}
							<br>
							{if $result.completion < 100}
								<span class="warning">{$result.completion}%</span>
							{else}
								{$result.completion}%
							{/if}
						{/if}
					</td>
					<td class="files">
						<a title="View file list"
						   href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
						{if $result.rarinnerfilecount > 0}
							<div class="rarfilelist">
								<img src="{$smarty.const.WWW_TOP}/themes_shared/images/icons/magnifier.png"
									 alt="{$result.guid}">
							</div>
						{/if}
					</td>
					<td class="stats">
						<a title="View comments"
						   href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments}
							cmt{if $result.comments != 1}s{/if}</a>
						<br>{$result.grabs} grab{if $result.grabs != 1}s{/if}
					</td>
					<td class="icon_nzb"><a
								href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}"><i
									class="fa fa-cloud-download text-muted"
									title="Download NZB"></i></a>
						<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments"><i
									class="fa fa-comments-o text-muted"
									title="Comments"></i></a>
						<a href="#" class="icon_cart text-muted"><i
									class="fa fa-shopping-basket" title="Send to my Download Basket"></i></a>
						{if isset($sabintegrated)}
							<a href="#" class="icon_sab text-muted"><i class="fa fa-share-o"
																	   title="Send to my Queue"></i></a>
						{/if}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
		<br/>
		<div class="row">
			<div class="col-md-8">
				<div class="nzb_multi_operations">
					<small>With Selected:</small>
					<div class="btn-group">
						<input type="button"
							   class="nzb_multi_operations_download btn btn-sm btn-success"
							   value="Download NZBs"/>
						<input type="button"
							   class="nzb_multi_operations_cart btn btn-sm btn-info"
							   value="Send to my Download Basket"/>
						{if isset($sabintegrated)}
							<input type="button"
								   class="nzb_multi_operations_sab btn btn-sm btn-primary"
								   value="Send to Queue"/>
						{/if}
						{if isset($nzbgetintegrated)}
							<input type="button"
								   class="nzb_multi_operations_nzbget btn btn-sm btn-primary"
								   value="Send to NZBGet"/>
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
		<br><br><br>
	</form>
{/if}
