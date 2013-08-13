{if {$site->adbrowse} != ''}
<div class="row">
    <div class="container" style="width:500px;">
<fieldset class="adbanner div-center">
<legend class="adbanner">Advertisement</legend>
{$site->adbrowse}
</fieldset></div></div>
<br>
{/if}
<div>

<br>
<p style="font-size:16px">
<b>Jump to</b>:&nbsp;&nbsp;[ {if $seriesletter == '0-9'}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/series/0-9">0-9</a>{if $seriesletter == '0-9'}</u></b>{/if}&nbsp;
{foreach $seriesrange as $range}
&nbsp;{if $range == $seriesletter}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/series/{$range}">{$range}</a>{if $range == $seriesletter}</u></b>{/if}&nbsp;
{/foreach} ]
</p>
</div>

<div>
<a class="btn btn-default" href="{$smarty.const.WWW_TOP}/myshows" title="List my watched shows">My Shows</a>&nbsp;&nbsp;
<a class="btn btn-default" href="{$smarty.const.WWW_TOP}/myshows/browse" title="browse your shows">Browse My Shows</a>

<form name="ragesearch" action="" method="get" id="custom-search-form" class="form-search form-horizontal col-4 col-lg-4 pull-right">
<div class="input-group col-12 col-lg-12">
<input type="text" class="form-control" placeholder="Search" id="title" name="title" value="{$ragename}">
<span class="input-group-btn">
<button type="submit" value="Go" class="btn btn-default"><i class="icon-search"></i></button>
</span>
</div>
</form>
</div>

<br>

{if $serieslist|@count > 0}

<table style="padding:0;"  class="table table-condensed data  icons" id="browsetable">
{foreach $serieslist as $sletter => $series}
<thead>
<tr>
<th colspan="5"><h3 style="margin:0 4px">{$sletter}...</h3></th>
</tr>
<tr>
<th><div class="text-left">Name</div></th>
<th style="width:80px"><div class="text-center">Country</div></th>
<th style="width:180px"><div class="text-center">Genre</div></th>
<th style="width:120px"><div class="text-center">Option</div></th>
<th style="width:180px"><div class="text-center">View</div></th>
</tr>
</thead>
<tbody>
{foreach $series as $s}
<tr class="{cycle values=",alt"}">
<td><div class="text-left"><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.releasetitle|escape:"htmlall"}</a>{if $s.prevdate != ''}<br><span class="label">Last: {$s.previnfo|escape:"htmlall"} aired {$s.prevdate|date_format}</span>{/if}</div></td>
<td><div class="text-center">{$s.country|escape:"htmlall"}</div></td>
<td><div class="text-center">{$s.genre|truncate:40:"...":true|escape:"htmlall"|replace:'|':', '}</div></td>
<td><div class="text-center">
{if $s.userseriesID != ''}
<a class="label label-warning" href="{$smarty.const.WWW_TOP}/myshows/edit/{$s.rageID}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="edit" name="series{$s.rageID}" title="Edit">Edit</a>&nbsp;&nbsp;<a class="label label-danger" href="{$smarty.const.WWW_TOP}/myshows/delete/{$s.rageID}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="remove" name="series{$s.rageID}" title="Remove from My Shows">Remove</a>
{else}
<a class="label label-success" href="{$smarty.const.WWW_TOP}/myshows/add/{$s.rageID}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows" rel="add" name="series{$s.rageID}" title="Add to My Shows">Add</a>
{/if}
</div></td>
<td>
    <div class="text-center">
        <a class="label label-info" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">Series</a> {if $s.rageID > 0}<a class="label label-important" title="View at TVRage" target="_blank" href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$s.rageID}">TVRage</a> <a class="label label-warning" title="RSS Feed for {$s.releasetitle|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/rss?rage={$s.rageID}&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}">Rss</a>{/if}</div></td>
</tr>
{/foreach}
</tbody>
{/foreach}
</table>
{else}
<h2>No results</h2>
{/if}
