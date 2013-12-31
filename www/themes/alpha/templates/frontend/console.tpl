{if {$site->adbrowse} != ''}
    <div class="container" style="width:500px;">
<fieldset class="adbanner div-center">
<legend class="adbanner">Advertisement</legend>
{$site->adbrowse}
</fieldset></div>
<br>
{/if}

<div class="panel">
<div class="panel-heading">
<h4 class="panel-title">
<a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#searchtoggle">
<i class="icon-search"></i> Search Filter</a>
</a>
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
<span><i class="icon-th-list"></i></span>&nbsp;&nbsp;
<a href="{$smarty.const.WWW_TOP}/browse?t={$category}"><i class="icon-align-justify"></i></a>
{if $isadmin || $ismod}
&nbsp;&nbsp;
Admin: <input type="button" class="btn btn-warning nzb_multi_operations_edit" value="Edit">
<input type="button" class="btn btn-danger nzb_multi_operations_delete" value="Delete">
{/if}
</div>
{include file='multi-operations.tpl'}


<table class="table table-striped table-condensed table-hover data icons" id="coverstable">
<thead>
<tr>
<th><input type="checkbox" class="nzb_check_all"></th>
<th>title <a title="Sort Descending" href="{$orderbytitle_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbytitle_asc}"><i class="icon-chevron-up"></i></a></th>
<th>platform <a title="Sort Descending" href="{$orderbyplatform_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyplatform_asc}"><i class="icon-chevron-up"></i></a></th>
<th>genre <a title="Sort Descending" href="{$orderbygenre_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbygenre_asc}"><i class="icon-chevron-up"></i></a></th>
<th>release date <a title="Sort Descending" href="{$orderbyreleasedate_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyreleasedate_asc}"><i class="icon-chevron-up"></i></a></th>
<th>posted <a title="Sort Descending" href="{$orderbyposted_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyposted_asc}"><i class="icon-chevron-up"></i></a></th>
<th>size <a title="Sort Descending" href="{$orderbysize_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbysize_asc}"><i class="icon-chevron-up"></i></a></th>
<th>files <a title="Sort Descending" href="{$orderbyfiles_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbyfiles_asc}"><i class="icon-chevron-up"></i></a></th>
<th>stats <a title="Sort Descending" href="{$orderbystats_desc}"><i class="icon-chevron-down"></i></a><a title="Sort Ascending" href="{$orderbystats_asc}"><i class="icon-chevron-up"></i></a></th>
</tr>
</thead>

<tbody>
{foreach from=$results item=result}
<tr>

<td style="width:150px;padding:10px;text-align:center;">
<div class="movcover" style="padding-bottom:5px;">
<a class="title thumbnail" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}">
<img class="shadow" src="{$smarty.const.WWW_TOP}/covers/console/{if $result.cover == 1}{$result.consoleinfoid}.jpg{else}no-cover.jpg{/if}" width="120" border="0" alt="{$result.title|escape:"htmlall"}"></a>
<div class="relextra" style="margin-top:5px;">
{if $result.nfoid > 0}<a href="{$smarty.const.WWW_TOP}/nfo/{$result.guid}" title="View Nfo" class="label modal_nfo" rel="nfo">Nfo</a> {/if}
{if $result.url != ""}<a class="label" target="_blank" href="{$site->dereferrer_link}{$result.url}" name="amazon{$result.consoleinfoid}" title="View amazon page">Amazon</a> {/if}
<a class="label" href="{$smarty.const.WWW_TOP}/browse?g={$result.group_name}" title="Browse releases in {$result.group_name|replace:"alt.binaries":"a.b"}">Grp</a> 
</div>
</div>
</td>

<td colspan="8" class="left" id="guid{$result.guid}">
<h4><a class="title" title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"seourl"}">{$result.title|escape:"htmlall"} - {$result.platform|escape:"htmlall"}</a></h4>

{if $result.genre != ""}<b>Genre:</b> {$result.genre}<br>{/if}
{if $result.esrb != ""}<b>Rating:</b> {$result.esrb}<br>{/if}
{if $result.publisher != ""}<b>Publisher:</b> {$result.publisher}<br>{/if}
{if $result.releasedate != ""}<b>Released:</b> {$result.releasedate|date_format}<br>{/if}
{if $result.review != ""}<b>Review:</b> {$result.review|escape:'htmlall'}<br>{/if}
<div class="container">
<a class="label label-info" href="{$smarty.const.WWW_TOP}/console?platform={$result.platform}" title="View similar nzbs">Similar</a>
{if $isadmin || $ismod}
<a class="label label-warning" href="{$smarty.const.WWW_TOP}/admin/release-edit.php?id={$result.releaseid}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Edit Release">Edit</a>
<a class="label confirm_action label-danger" href="{$smarty.const.WWW_TOP}/admin/release-delete.php?id={$result.releaseid}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Release">Delete</a>
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
<i class="icon-calendar"></i> Posted {$result.postdate|timeago} | <i class="icon-hdd"></i> {$result.size|fsize_format:"MB"} | <i class="icon-file"></i> <a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$result.guid}">{$result.totalpart} files</a> | <i class="icon-comments"></i> <a title="View comments for {$result.searchname|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/#comments">{$result.comments} cmts</a> | <i class="icon-download"></i> {$result.grabs} grabs
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
{/if}
</form>
{else}
<div class="alert alert-link" style="vertical-align:middle;">
<button type="button" class="close" data-dismiss="alert">&times;</button>
<div class="pull-left" style="margin-right: 15px;"><h2 style="margin-top: 7px;"> ಠ_ಠ </h2></div><p>Either some amazon key is wrong, or there is nothing in this section.<br>Please try looking in the <a href="{$smarty.const.WWW_TOP}/browse?t={$category}" style="font-weight:strong;text-decoration:underline;">list view</a>.</p>
</div>
{/if}
