{if {$site->adbrowse} != ''}
<div class="row">
    <div class="container" style="width:500px;">
<fieldset class="adbanner div-center">
<legend class="adbanner">Advertisement</legend>
{$site->adbrowse}
</fieldset></div></div>
<br>
{/if}


<h3 class="text-center"><a href="#" onclick="if(jQuery(this).text()=='Advanced Search')jQuery(this).text('Basic Search');else jQuery(this).text('Advanced Search');jQuery('#sbasic,#sadvanced').toggle();return false;">{if $sadvanced}Basic{else}Click For Advanced{/if} Search</a></h3>

<p class="text-center"><b>Include ^ to indicate search must start with term, -- to exclude words.</b></p>
<br>

<div class="row" id="sbasic"{if $sadvanced} style="display:none;"{/if}>
<form method="get" action="{$smarty.const.WWW_TOP}/search" id="custom-search-form" class="form-search">
<div class="col-6 col-lg-6">
<div class="input-group">
<input type="text" class="form-control" placeholder="Name" id="search" name="search" value="{$search|escape:'html'}">
<span class="input-group-btn">
<button id="search_search_button" class="btn btn-default" type="submit"><i class="icon-search"></i></button>
</span>
</div><!-- /input-group -->
</div><!-- /.col-lg-6 -->
<div class="col-6 col-lg-6">
<div class="input-group">
<input type="text" class="form-control" placeholder="Subject" id="subject" name="subject" value="{$subject|escape:'html'}">
<span class="input-group-btn">
<button  id="subject_search_button" class="btn btn-default" type="submit"><i class="icon-search"></i></button>
</span>
</div><!-- /input-group -->
</div><!-- /.col-lg-6 -->
<input type="hidden" name="t" value="{if $category[0]!=""}{$category[0]}{else}-1{/if}" id="search_cat">
<input type="hidden" name="search_type" value="basic" id="search_type">
</form>
</div>

<div class="row" id="sbasic"{if not $sadvanced} style="display:none;"{/if}>
<form class="form-horizontal" method="get" action="{$smarty.const.WWW_TOP}/search">
<div class="container form-group">
<div class="row">
<div class="col-4 col-lg-4">
<input type="text" class="searchadv form-control" id="searchadvr" name="searchadvr" value="{$searchadvr|escape:'html'}" placeholder="Release Name">
</div>
<div class="col-4 col-lg-4">
<div class="col-6">
<input type="text" class="searchdaysinput form-control" id="searchadvdaysnew" name="searchadvdaysnew" value="{$searchadvdaysnew|escape:'html'}" placeholder="Minimum Days">
</div>
<div class="col-6">
<input type="text" class="searchdaysinput form-control" id="searchadvdaysold" name="searchadvdaysold" value="{$searchadvdaysold|escape:'html'}" placeholder="Maximum Days">
</div>
</div>
<div class="col-4 col-lg-4">
<div class="col-6">
{html_options id="searchadvsizefrom" class="form-control" name="searchadvsizefrom" options=$sizelist selected=$selectedsizefrom}
</div>
<div class="col-6">
{html_options id="searchadvsizeto" class="form-control" name="searchadvsizeto" options=$sizelist selected=$selectedsizeto}
</div>
</div>
</div>
<div class="row">
<div class="col-4 col-lg-4">
<input type="text" class="searchadv form-control" id="searchadvsubject" name="searchadvsubject" value="{$searchadvsubject|escape:'html'}" placeholder="Usenet Name">
</div>
<div class="col-4 col-lg-4">
{html_options class="searchadvbtns form-control col-12" id="searchadvgroups" name="searchadvgroups" options=$grouplist selected=$selectedgroup}
</div>
<div class="col-4 col-lg-4">
<div class="col-6">
<input type="hidden" id="inlineCheckbox1" name="searchadvhasnfo" value="0">
&nbsp;<input type="checkbox" id="inlineCheckbox1" name="searchadvhascomments" value="1"> Has Info
</div>
<div class="col-6">
<input type="hidden" id="searchadvhascomments" name="searchadvhascomments" value="0">
&nbsp;<input type="checkbox" id="searchadvhascomments" name="searchadvhascomments" value="1"> Has Comment
</div>
</div>
</div>
<div class="row">
<div class="col-4 col-lg-4">
<input type="text" class="searchadv form-control" id="searchadvposter" name="searchadvposter" value="{$searchadvposter|escape:'html'}" placeholder="Poster">
</div>
<div class="col-4 col-lg-4">
{html_options class="searchadvbtns form-control col-12" id="searchadvcat" name="searchadvcat" options=$catlist selected=$selectedcat}
</div>
<div class="col-4 col-lg-4">
<input type="hidden" name="search_type" value="adv" id="search_type">
<input class="btn btn-default btn-block" id="search_adv_button" type="submit" value="search">
</div>
</div>
</div>
</form>
</div>
<br>
<br>
<br>

{if $results|@count == 0 && ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) != ""}
<center><div class="nosearchresults">
Your search did not match any releases.
<br><br>
Suggestions:
<br><br>
<ul>
<center><li>Make sure all words are spelled correctly.</li></center>
<center><li>Try different keywords.</li></center>
<center><li>Try more general keywords.</li></center>
<center><li>Try fewer keywords.</li></center>
</ul>
</div></center>
{elseif ($search || $subject || $searchadvr || $searchadvsubject || $selectedgroup || $selectedsizefrom || $searchadvdaysold) == ""}
{/if}

{if $results|@count > 0}
<form id="nzb_multi_operations_form" method="get" action="{$smarty.const.WWW_TOP}/search">
<div class="nzb_multi_operations">
<div class="row">
<div class="col-12" style="text-align: right; padding-bottom: 4px;">
View:
<a href="{$smarty.const.WWW_TOP}/{$section}?t={$category}"><i class="icon-th-list"></i></a>&nbsp;&nbsp;
<span><i class="icon-align-justify"></i></span>
&nbsp;&nbsp;
{if $isadmin || $ismod}
Admin: <input type="button" class="btn btn-warning btn-small nzb_multi_operations_edit" value="Edit">
<input type="button" class="btn btn-danger btn-small nzb_multi_operations_delete" value="Delete">
{/if}
</div>
</div>
{include file='multi-operations.tpl'}
</div>


<table class="table table-collapsed table-striped table-bordered table-hover data highlight icons" id="browsetable">
<thead>
<tr>
<th><input id="chkSelectAll" type="checkbox" class="nzb_check_all"></th>
<th style="vertical-align:top;">name <a title="Sort Descending" href="{$orderbyname_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyname_asc}"><i class="icon-chevron-up"></i></a>
</th>
<th style="vertical-align:top;">category<br><a title="Sort Descending" href="{$orderbycat_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbycat_asc}"><i class="icon-chevron-up"></i></a>
</th>
<th style="vertical-align:top;">posted<br><a title="Sort Descending" href="{$orderbyposted_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyposted_asc}"><i class="icon-chevron-up"></i></a>
</th>
<th style="vertical-align:top;">size<br><a title="Sort Descending" href="{$orderbysize_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbysize_asc}"><i class="icon-chevron-up"></i></a>
</th>
<th style="vertical-align:top;">files<br><a title="Sort Descending" href="{$orderbyfiles_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyfiles_asc}"><i class="icon-chevron-up"></i></a>
</th>
<th style="vertical-align:top;">stats<br><a title="Sort Descending" href="{$orderbystats_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbystats_asc}"><i class="icon-chevron-up"></i></a>
</th>
<th style="vertical-align:top;">action</th>
</tr>
</thead>
<tbody>
{foreach from=$results item=result}
<tr class="{cycle values=",alt"}{if $lastvisit|strtotime<$result.adddate|strtotime} new{/if}" id="guid{$result.guid}">
<td class="check" style="text-align:center;"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.guid}"></td>

<td class="item" style="width:100%;text-align:left;white-space:nowrap;">
<label for="chk{$result.guid|substr:0:7}">
<a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.searchname|escape:"htmlall"|truncate:150:"...":true}</a>
</label>

{if $result.passwordstatus == 1}
<img title="Probably Passworded" src="{$smarty.const.WWW_TOP}/themes/alpha/images/icons/lock2.png" alt="Probably Passworded">
{elseif $result.passwordstatus == 2}
<img title="Broken post" src="{$smarty.const.WWW_TOP}/themes/alpha/images/icons/broken.png" alt="Broken post">
{elseif $result.passwordstatus == 10}
<img title="Passworded archive" src="{$smarty.const.WWW_TOP}/themes/alpha/images/icons/lock.gif" alt="Passworded archive">
{/if}

<div class="resextra">
{*<a class="browsename" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.name|escape:"htmlall"|truncate:150:"...":true}</a>*}
<div class="btns">
<span class="label">{release_flag($result.searchname, browse)}</span>
{if $result.videostatus == 1}
<a href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}" title="This release has a video preview." class="model_prev label" rel="preview"><i class="icon-youtube-play"></i></a>{/if}
{if $result.nfoID > 0}
<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="modal_nfo label" rel="nfo">Nfo</a>{/if}
{if $result.imdbID > 0}
<a href="#" name="name{$result.imdbID}" title="View movie info" class="modal_imdb label" rel="movie" >Cover</a>{/if}
{if $result.haspreview == 1 && $userdata.canpreview == 1}
<a href="{$smarty.const.WWW_TOP}/covers/preview/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Screenshot of {$result.searchname|escape:"htmlall"}" class="modal_prev label" rel="preview">Preview</a>{/if}
{if $result.jpgstatus == 1 && $userdata.canpreview == 1}
<a href="{$smarty.const.WWW_TOP}/covers/sample/{$result.guid}_thumb.jpg" name="name{$result.guid}" title="Sample of {$result.searchname|escape:"htmlall"}" class="modal_prev label" rel="preview">Sample</a>{/if}
{if $result.musicinfoID > 0}
<a href="#" name="name{$result.musicinfoID}" title="View music info" class="modal_music label" rel="music" >Cover</a>{/if}
{if $result.consoleinfoID > 0}
<a href="#" name="name{$result.consoleinfoID}" title="View console info" class="modal_console label" rel="console" >Cover</a>{/if}
{if $result.rageID > 0}
<a class="label" href="{$smarty.const.WWW_TOP}/series/{$result.rageID}" title="View all episodes">View Series</a>{/if}
{if $result.anidbID > 0}
<a class="label" href="{$smarty.const.WWW_TOP}/anime/{$result.anidbID}" title="View all episodes">View Anime</a>{/if}
{if $result.tvairdate != ""}
<span class="seriesinfo label" title="{$result.guid}">Aired {if $result.tvairdate|strtotime > $smarty.now}in future{else}{$result.tvairdate|daysago}{/if}</span>{/if}
{if $result.reID > 0}
<span class="mediainfo label" title="{$result.guid}">Media</span>{/if}
{if $result.group_name != ""}
<a class="label" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name|escape:"htmlall"}" title="Browse {$result.group_name}">{$result.group_name|escape:"htmlall"|replace:"alt.binaries.":"a.b."}</a>{/if}
</div>
</div>
</td>
<td class="category" style="width:auto;text-align:center;white-space:nowrap;"><a title="Browse {$result.category_name}" href="{$smarty.const.WWW_TOP}/browse?t={$result.categoryID}">{$result.category_name}</a></td>
<td class="posted" title="{$result.postdate}" style="white-space:nowrap;text-align:center;">{$result.postdate|timeago}</td>
<td class="size" style="width:auto;text-align:center;white-space:nowrap;">{$result.size|fsize_format:"MB"}{if $result.completion > 0}<br>{if $result.completion < 100}<span class="label label-warning">{$result.completion}%</span>{else}<span class="label label-success">{$result.completion}%</span>{/if}{/if}</td>
<td class="files" style="width:auto;text-align:center;white-space:nowrap;">
<a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a>
{if $result.rarinnerfilecount > 0}
<div class="rarfilelist">
<img src="{$smarty.const.WWW_TOP}/themes/alpha/images/icons/magnifier.png" alt="{$result.guid}" class="tooltip">
</div>
{/if}
</td>
<td class="stats" style="width:auto;text-align:center;white-space:nowrap;"><a title="View comments" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a><br/>{$result.grabs} grab{if $result.grabs != 1}s{/if}</td>
<td class="icons" style="width:60px;text-align:center;white-space:nowrap;">
<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}">&nbsp;</a></div>
<div class="icon icon_cart" title="Add to Cart"></div>
{if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
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
{/if}

