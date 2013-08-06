
{if {$site->adbrowse} != ''}
<div class="row">
    <div class="container" style="width:500px;">
<fieldset class="adbanner div-center">
<legend class="adbanner">Advertisement</legend>
{$site->adbrowse}
</fieldset></div></div>
<br>
{/if}

<div class="accordion" id="searchtoggle">
<div class="accordion-group">
<div class="accordion-heading">
<a class="accordion-toggle" data-toggle="collapse" data-parent="#searchtoggle" href="#searchfilter"><i class="icon-search"></i> Search Filter</a>
</div>
<div id="searchfilter" class="accordion-body collapse">
<div class="accordion-inner">
<form class="form-inline" name="browseby" action="music" style="margin:0;">
<input class="form-control" style="width: 150px;" id="musicartist" type="text" name="artist" value="{$artist}" placeholder="Artist">
<input class="form-control" style="width: 150px;" id="musictitle" type="text" name="title" value="{$title}" placeholder="Title">
<select class="form-control" style="width: auto;" id="genre" name="genre">
<option class="grouping" value="">Genre... </option>
{foreach from=$genres item=gen}
<option {if $gen.ID == $genre}selected="selected"{/if} value="{$gen.ID}">{$gen.title|escape:"htmlall"}</option>
{/foreach}
</select>
<select class="form-control" style="width: auto;" id="year" name="year">
<option class="grouping" value="">Year... </option>
{foreach from=$years item=yr}
<option {if $yr==$year}selected="selected"{/if} value="{$yr}">{$yr}</option>
{/foreach}
</select>
<select class="form-control" style="width: auto;" id="category" name="t">
<option class="grouping" value="3000">Category... </option>
{foreach from=$catlist item=ct}
<option {if $ct.ID==$category}selected="selected"{/if} value="{$ct.ID}">{$ct.title}</option>
{/foreach}
</select>
<input class="btn btn-success" type="submit" value="Go">
</form>
</div>
</div>
</div>
</div>


{if $results|@count > 0}

<form id="nzb_multi_operations_form" action="get">
<div class="nzb_multi_operations">
<div class="row" style="text-align:right;margin-bottom:5px;">
View:
<span><i class="icon-th-list"></i></span>&nbsp;&nbsp;
<a href="{$smarty.const.WWW_TOP}/browse?t={$category}"><i class="icon-align-justify"></i></a>
{if $isadmin || $ismod}
&nbsp;&nbsp;
Admin: <input type="button" class="btn btn-warning nzb_multi_operations_edit" value="Edit">
<input type="button" class="btn btn-danger nzb_multi_operations_delete" value="Delete">
{/if}
</div>
{include file='multi-operations.tpl'}
</div>

<table class="table table-striped table-hover table-condensed data" id="coverstable">
<thead>
<tr>
<th><input type="checkbox" class="nzb_check_all"></th>
<th>artist <a title="Sort Descending" href="{$orderbyartist_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyartist_asc}"><i class="icon-chevron-up"></i></a></th>
<th>year <a title="Sort Descending" href="{$orderbyyear_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyyear_asc}"><i class="icon-chevron-up"></i></a></th>
<th>genre <a title="Sort Descending" href="{$orderbygenre_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbygenre_asc}"><i class="icon-chevron-up"></i></a></th>
<th>posted <a title="Sort Descending" href="{$orderbyposted_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyposted_asc}"><i class="icon-chevron-up"></i></a></th>
<th>size <a title="Sort Descending" href="{$orderbysize_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbysize_asc}"><i class="icon-chevron-up"></i></a></th>
<th>files <a title="Sort Descending" href="{$orderbyfiles_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyfiles_asc}"><i class="icon-chevron-up"></i></a></th>
<th>stats <a title="Sort Descending" href="{$orderbystats_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbystats_asc}"><i class="icon-chevron-up"></i></a></th>
</tr>
</thead>
<tbody>
{foreach from=$results item=result}
<tr>
<td style="width:150px;padding:10px;">
<div class="movcover" style="padding-bottom:5px;">
<center>
<a class="title thumbnail" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}">
<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/music/{if $result.cover == 1}{$result.musicinfoID}.jpg{else}no-cover.music.jpg{/if}" alt="{$result.artist|escape:"htmlall"} - {$result.title|escape:"htmlall"}">
</a>
</center>
</div>
<div class="relextra">
<center>
{if $result.nfoID > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="label modal_nfo" rel="nfo">Nfo</a>{/if}
{if $result.url != ""}<a class="label" target="_blank" href="{$site->dereferrer_link}{$result.url}" name="amazon{$result.musicinfoID}" title="View amazon page">Amazon</a>{/if}
<a class="label" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}" title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
</center>
</div>
</td>

<td colspan="7" class="left" id="guid{$result.guid}">
<h4><a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}">{$result.artist|escape:"htmlall"} - {$result.title|escape:"htmlall"}</a> (<a class="title" title="{$result.year}" href="{$smarty.const.WWW_TOP}/music?year={$result.year}">{$result.year}</a>)</h4>

{if $result.genre != ""}<b>Genre:</b> <a href="{$smarty.const.WWW_TOP}/music/?genre={$result.genreID}">{$result.genre|escape:"htmlall"}</a><br>{/if}
{if $result.publisher != ""}<b>Publisher:</b> {$result.publisher|escape:"htmlall"}<br>{/if}
{if $result.releasedate != ""}<b>Released:</b> {$result.releasedate|date_format}<br>{/if}
{if $result.haspreview == 2 && $userdata.canpreview == 1}<b>Preview:</b> <a href="#" name="audio{$result.guid}" title="Listen to {$result.searchname|escape:"htmlall"}" class="audioprev rndbtn" rel="audio">Listen</a><audio id="audprev{$result.guid}" src="{$smarty.const.WWW_TOP}/covers/audio/{$result.guid}.mp3" preload="none"></audio>{/if}
<div class="container">
<a class="label label-primary" href="{$smarty.const.WWW_TOP}/music?artist={$result.artist|escape:"url"}" title="View similar nzbs">Similar</a>
{if $isadmin || $ismod}
<a class="label label-warning" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.releaseID}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a>
<a class="label confirm_action label-danger" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.releaseID}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Delete</a>
{/if}
</div>
<hr>
<div class="relextra">
<b>{$result.searchname|escape:"htmlall"}</b>
<div class="container">
<div class="icon"><input type="checkbox" class="nzb_check" value="{$result.guid}" /></div>
<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}">&nbsp;</a></div>
<div class="icon icon_cart" title="Add to Cart"></div>
{if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
&nbsp;&nbsp;&nbsp;&nbsp;
Posted {$result.postdate|timeago}, {$result.size|fsize_format:"MB"}, <a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart}</a> <i class="icon-file"></i>, <a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments}</a> <i class="icon-comments-alt"></i>, {$result.grabs} <i class="icon-download-alt"></i>
</div>
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
</form>
{/if}

{else}
<div class="alert">
<button type="button" class="close" data-dismiss="alert">&times;</button>
<strong>Sorry!</strong> Either some amazon key is wrong, or there is nothing in this section.
</div>
{/if}
