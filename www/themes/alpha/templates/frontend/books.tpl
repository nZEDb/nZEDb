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
<form class="form-inline" name="browseby" action="books" style="margin:0;">
<input class="form-control" style="width: 150px;" id="author" type="text" name="author" value="{$author}" placeholder="Author">
<input class="form-control" style="width: 150px;" id="title" type="text" name="title" value="{$title}" placeholder="Title">
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


<table class="table table-condensed table-striped data highlight icons" id="coverstable">
<thead>
<tr>
<th><input type="checkbox" class="nzb_check_all"></th>
<th>author<br/>
<a title="Sort Descending" href="{$orderbyauthor_desc}">
<i class="icon-chevron-down icon-black"></i>
</a>
<a title="Sort Ascending" href="{$orderbyauthor_asc}">
<i class="icon-chevron-up icon-black"></i>
</a>
</th>
<th>genre<br/>
<a title="Sort Descending" href="{$orderbygenre_desc}">
<i class="icon-chevron-down icon-black"></i>
</a>
<a title="Sort Ascending" href="{$orderbygenre_asc}">
<i class="icon-chevron-up icon-black"></i>
</a>
</th>
<th>posted<br/>
<a title="Sort Descending" href="{$orderbyposted_desc}">
<i class="icon-chevron-down icon-black"></i>
</a>
<a title="Sort Ascending" href="{$orderbyposted_asc}">
<i class="icon-chevron-up icon-black"></i>
</a>
</th>
</tr>
</thead>
<tbody>
{foreach from=$results item=result}
<tr>
<td style="text-align:center">
<div class="bookcover">
<a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">
<img class="shadow img-polaroid" src="{$smarty.const.WWW_TOP}/covers/book/{if $result.cover == 1}{$result.bookinfoID}.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$result.title|escape:"htmlall"}" />
</a>
<div class="relextra">
{if $result.nfoID > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="label modal_nfo" rel="nfo">Nfo</a>{/if}
<a class="label" target="_blank" href="{$site->dereferrer_link}{$result.url}" name="amazon{$result.bookinfoID}" title="View amazon page">Amazon</a>
<a class="label" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}" title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a>
</div>
</div>
</td>
<td colspan="8" class="left" id="guid{$result.guid}">
<h2><a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{if $result.author != ""}{$result.author|escape:"htmlall"} - {/if}{$result.title|escape:"htmlall"}</a></h2>
{if $result.genre != "null"}<b>Genre:</b> {$result.genre|escape:'htmlall'}<br />{/if}
{if $result.publisher != ""}<b>Publisher:</b> {$result.publisher}<br />{/if}
{if $result.publishdate != ""}<b>Released:</b> {$result.publishdate|date_format}<br />{/if}
{if $result.pages != ""}<b>Pages:</b> {$result.pages}<br />{/if}
{if $result.salesrank != ""}<b>Amazon Rank:</b> {$result.salesrank}<br />{/if}
{if $result.overview != "null"}<b>Overview:</b> {$result.overview|escape:'htmlall'}<br />{/if}
<br />
<div class="relextra">
<b>{$result.searchname|escape:"htmlall"}</b> <a class="label" href="{$smarty.const.WWW_TOP}/books?platform={$result.platform}" title="View similar nzbs">Similar</a>
{if $isadmin || $ismod}
<a class="label" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.releaseID}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a> <a class="label confirm_action" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.releaseID}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Del</a>
{/if}
<br />
<b>Info:</b> {$result.postdate|timeago},  {$result.size|fsize_format:"MB"},  <a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart} files</a>,  <a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}#comments">{$result.comments} cmt{if $result.comments != 1}s{/if}</a>, {$result.grabs} grab{if $result.grabs != 1}s{/if}
<br />
<div class="icon"><input type="checkbox" class="nzb_check" value="{$result.guid}" /></div>
<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$result.guid}/{$result.searchname|escape:"htmlall"}">&nbsp;</a></div>
<div class="icon icon_cart" title="Add to Cart"></div>
{if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
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
<div class="alert">
<button type="button" class="close" data-dismiss="alert">&times;</button>
<strong>Sorry!</strong> Either some amazon key is wrong, or there is nothing in this section.
</div>
{/if}
