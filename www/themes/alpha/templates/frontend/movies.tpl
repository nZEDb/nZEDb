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
<form class="form-inline" name="browseby" action="movies">
<input class="form-control" style="width: 150px;" id="movietitle" type="text" name="title" value="{$title}" placeholder="Title">
<input class="form-control" style="width: 150px;" id="movieactors" type="text" name="actors" value="{$actors}" placeholder="Actor">
<input class="form-control" style="width: 150px;" id="moviedirector" type="text" name="director" value="{$director}"  placeholder="Director">
<select class="form-control" style="width: auto;" id="rating" name="rating">
<option class="grouping" value="">Rating... </option>
{foreach from=$ratings item=rate}
<option {if $rating==$rate}selected="selected"{/if} value="{$rate}">{$rate}</option>
{/foreach}
</select>
<select class="form-control" style="width: auto;" id="genre" name="genre" placeholder="Genre">
<option class="grouping" value="">Genre... </option>
{foreach from=$genres item=gen}
<option {if $gen==$genre}selected="selected"{/if} value="{$gen}">{$gen}</option>
{/foreach}
</select>
<select class="form-control" style="width: auto;" id="year" name="year">
<option class="grouping" value="">Year... </option>
{foreach from=$years item=yr}
<option {if $yr==$year}selected="selected"{/if} value="{$yr}">{$yr}</option>
{/foreach}
</select>
<select class="form-control" style="width: auto;" id="category" name="t">
<option class="grouping" value="2000">Category... </option>
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

<table class="table table-condensed data highlight icons" id="coverstable">
<thead>
<tr>
<th><input type="checkbox" class="nzb_check_all"></th>
<th>title <a title="Sort Descending" href="{$orderbytitle_desc}"><i class="icon-chevron-down icon-black"></i></a><a title="Sort Ascending" href="{$orderbytitle_asc}"><i class="icon-chevron-up icon-black"></i></a>
</th>
<th>year <a title="Sort Descending" href="{$orderbyyear_desc}"><i class="icon-chevron-down icon-black"></i></a><a title="Sort Ascending" href="{$orderbyyear_asc}"><i class="icon-chevron-up icon-black"></i></a>
</th>
<th>rating <a title="Sort Descending" href="{$orderbyrating_desc}"><i class="icon-chevron-down icon-black"></i></a><a title="Sort Ascending" href="{$orderbyrating_asc}"><i class="icon-chevron-up icon-black"></i></a>
</th>
</tr>
</thead>
<tbody>
{foreach from=$results item=result}
<tr>
<td style="vertical-align:top;text-align:center;width:150px;padding:10px;">
<div class="movcover">
<a target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbID}/" name="name{$result.imdbID}" title="View movie info" class="modal_imdb thumbnail" rel="movie">
<img class="shadow" style="margin: 3px 0;" src="{$smarty.const.WWW_TOP}/covers/movies/{if $result.cover == 1}{$result.imdbID}-cover.jpg{else}no-cover.jpg{/if}" width="160" border="0" alt="{$result.title|escape:"htmlall"}">
</a>
<div class="relextra" style="margin-top:5px;">
<span class="label"><a target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbID}/" name="name{$result.imdbID}" title="View movie info" class="modal_imdb" rel="movie" >Cover</a></span>
<span class="label"><a target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result.imdbID}/" name="imdb{$result.imdbID}" title="View imdb page">Imdb</a></span>
<span class="label"><a target="_blank" href="{$site->dereferrer_link}http://trakt.tv/search/imdb?q=tt{$result.imdbID}/" name="trakt{$result.imdbID}" title="View trakt page">Trakt</a></span>
<span class="label"><a target="blackhole" href="{$site->dereferrer_link}{$site->CPurl}/api/{$site->CPapikey}/movie.add/?identifier=tt{$result.imdbID}&title={$result.title}" name="CP{$result.imdbID}" title="Add to CouchPotato">Couch</a></span>
</div>
</div>
</td>
<td colspan="3" class="left">
<h2>{$result.title|stripslashes|escape:"htmlall"} (<a class="title" title="{$result.year}" href="{$smarty.const.WWW_TOP}/movies?year={$result.year}">{$result.year}</a>) {if $result.rating != ''}{$result.rating}/10{/if}
{foreach from=$result.languages item=movielanguage}
{release_flag($movielanguage, browse)}
{/foreach}</h2>
{if $result.tagline != ''}<b>{$result.tagline|stripslashes}</b><br>{/if}
{if $result.plot != ''}{$result.plot|stripslashes}<br>{/if}
<br>
{if $result.genre != ''}<b>Genre:</b> {$result.genre|stripslashes}<br>{/if}
{if $result.director != ''}<b>Director:</b> {$result.director}<br>{/if}
{if $result.actors != ''}<b>Starring:</b> {$result.actors}<br>{/if}
<br>
<div class="relextra">
<table class="table table-condensed table-hover">
{assign var="msplits" value=","|explode:$result.grp_release_id}
{assign var="mguid" value=","|explode:$result.grp_release_guid}
{assign var="mnfo" value=","|explode:$result.grp_release_nfoID}
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
<a href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"><b>{$mname[$m@index]|escape:"htmlall"}</b></a><br>
<div class="container">
<div class="pull-left">Posted {$mpostdate[$m@index]|timeago},  {$msize[$m@index]|fsize_format:"MB"},  <a title="View file list" href="{$smarty.const.WWW_TOP}/filelist/{$mguid[$m@index]}">{$mtotalparts[$m@index]} files</a>,  <a title="View comments for {$mname[$m@index]|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/details/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}#comments">{$mcomments[$m@index]} cmt{if $mcomments[$m@index] != 1}s{/if}</a>, {$mgrabs[$m@index]} grab{if $mgrabs[$m@index] != 1}s{/if}
</div>
<div class="pull-right">
{if $mnfo[$m@index] > 0}<span class="label"><a href="{$smarty.const.WWW_TOP}/nfo/{$mguid[$m@index]}" title="View Nfo" class="modal_nfo" rel="nfo">Nfo</a></span> {/if}
{if $mpass[$m@index] == 1}<span class="label">Passworded</span>{elseif $mpass[$m@index] == 2}<span class="label">Potential Password</span> {/if}
<span class="label"><a href="{$smarty.const.WWW_TOP}/browse?g={$mgrp[$m@index]}" title="Browse releases in {$mgrp[$m@index]|replace:"alt.binaries":"a.b"}">Grp</a></span> 
{if $mhaspreview[$m@index] == 1 && $userdata.canpreview == 1}<span class="label"><a href="{$smarty.const.WWW_TOP}/covers/preview/{$mguid[$m@index]}_thumb.jpg" name="name{$mguid[$m@index]}" title="Screenshot of {$mname[$m@index]|escape:"htmlall"}" class="modal_prev" rel="preview">Preview</a></span> {/if}
{if $minnerfiles[$m@index] > 0}<span class="label"><a href="#" onclick="return false;" class="mediainfo" title="{$mguid[$m@index]}">Media</a></span> {/if}
</div>
</div>
</td>
<td class="icons" style="width:80px;">
<div class="icon icon_nzb"><a title="Download Nzb" href="{$smarty.const.WWW_TOP}/getnzb/{$mguid[$m@index]}/{$mname[$m@index]|escape:"htmlall"}"></a></div>
{if $sabintegrated}<div class="icon icon_sab" title="Send to my Sabnzbd"></div>{/if}
<div class="icon icon_cart" title="Add to Cart"></div>
</td>
</tr>
{if $m@index == 1 && $m@total > 2}
<tr><td colspan="5"><a class="mlmore" href="#">{$m@total-2} more...</a></td></tr>
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
</form>
{/if}
{else}
<div class="alert">
<button type="button" class="close" data-dismiss="alert">&times;</button>
<strong>Sorry!</strong> Either some amazon key is wrong, or there is nothing in this section.
</div>
{/if}
