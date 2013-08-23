<ul class="nav navbar-nav" id="nav-menu" role="navigation">
{foreach from=$parentcatlist item=parentcat}
{if $parentcat.ID == 1000 && $userdata.consoleview=="1"}
<li class="dropdown">
<a id="cat1" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/console">{$parentcat.title} <b class="caret"></b></a>
<ul class="dropdown-menu" role="menu" aria-labelledby="cat1">
<li><a href="{$smarty.const.WWW_TOP}/console">All {$parentcat.title}</a></li>
{foreach from=$parentcat.subcatlist item=subcat}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/console?t={$subcat.ID}">{$subcat.title}</a></li>
{/foreach}
</ul>
</li>
{elseif $parentcat.ID == 2000 && $userdata.movieview=="1"}
<li class="dropdown">
<a id="cat2" role="button" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/movies">{$parentcat.title} <b class="caret"></b></a>
<ul class="dropdown-menu" role="menu" aria-labelledby="cat2">
<li><a href="{$smarty.const.WWW_TOP}/movies"> All {$parentcat.title}</a></li>
{foreach from=$parentcat.subcatlist item=subcat}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/movies?t={$subcat.ID}">{$subcat.title}</a></li>
{/foreach}
</ul>
</li>
{elseif ($parentcat.ID == 3000 && $userdata.musicview=="1")}
<li class="dropdown">
<a id="cat3" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/music">{$parentcat.title} <b class="caret"></b></a>
<ul class="dropdown-menu" role="menu" aria-labelledby="cat3">
<li><a href="{$smarty.const.WWW_TOP}/music">All {$parentcat.title}</a></li>
{foreach from=$parentcat.subcatlist item=subcat}
{if $subcat.ID == 3030}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.ID}">{$subcat.title}</a></li>
{else}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/music?t={$subcat.ID}">{$subcat.title}</a></li>
{/if}
{/foreach}
</ul>
</li>
{elseif ($parentcat.ID == 5000)}
<li class="dropdown">
<a id="cat{$parentcat.ID}" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.ID}">{$parentcat.title} <b class="caret"></b></a>
<ul class="dropdown-menu" role="menu" aria-labelledby="cat{$parentcat.ID}">
<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.ID}">All {$parentcat.title}</a></li>
{foreach from=$parentcat.subcatlist item=subcat}
{if ($subcat.ID == 7020 && $userdata.bookview=="1")}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books">{$subcat.title}</a></li>
{else}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.ID}">{$subcat.title}</a></li>
{/if}
{/foreach}
</ul>
</li>
{else}
<li class="dropdown">
<a id="drop{$parentcat.ID}" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.ID}">{$parentcat.title} <b class="caret"></b></a>
<ul class="dropdown-menu" role="menu" aria-labelledby="drop{$parentcat.ID}">
<li><a href="{$smarty.const.WWW_TOP}/browse?t={$parentcat.ID}">All {$parentcat.title}</a></li>
{foreach from=$parentcat.subcatlist item=subcat}
{if ($subcat.ID == 8010 && $userdata.bookview=="1")}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/books">{$subcat.title}</a></li>
{else}
<li><a title="Browse {$subcat.title}" href="{$smarty.const.WWW_TOP}/browse?t={$subcat.ID}">{$subcat.title}</a></li>
{/if}
{/foreach}
</ul>
</li>
{/if}
{/foreach}
<li class="dropdown">
<a id="dropAll" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" href="{$smarty.const.WWW_TOP}/browse">Browse <b class="caret"></b></a>
<ul class="dropdown-menu" role="menu" aria-labelledby="dropAll">
<li><a href="{$smarty.const.WWW_TOP}/browse">Browse All</a></li>
<li><a title="Browse Groups" href="{$smarty.const.WWW_TOP}/browsegroup">Groups</a></li>
</ul>
</li>
</ul>

<form class="navbar-form navbar-right" id="headsearch_form" action="{$smarty.const.WWW_TOP}/search/" method="get">
<div class="form-group">
<label class="sr-only" for="headsearch">Keyword</label>
<input type="text" class="form-control" style="width: 160px;" id="headsearch" name="search" value="{if $header_menu_search == ""}{else}{$header_menu_search|escape:"htmlall"}{/if}" placeholder="Keyword">
</div>
<div class="form-group">
<label class="sr-only" for="headcat">Category</label>
<select class="form-control" style="width: auto;" id="headcat" name="t">
<option class="grouping" value="-1">All</option>
{foreach from=$parentcatlist item=parentcat}
<option {if $header_menu_cat==$parentcat.ID}selected="selected"{/if} class="grouping" value="{$parentcat.ID}">{$parentcat.title}</option>
{foreach from=$parentcat.subcatlist item=subcat}
<option {if $header_menu_cat==$subcat.ID}selected="selected"{/if} value="{$subcat.ID}">&nbsp;&nbsp;{$subcat.title}</option>
{/foreach}
{/foreach}
</select>
</div>
<button type="submit" id="headsearch_go" class="btn btn-default">Search</button>
</form>
