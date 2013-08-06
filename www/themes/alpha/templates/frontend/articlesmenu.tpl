{if $site->menuposition == 1 or $site->menuposition == 0}
{if $articlecontentlist|@count > 0}
<div class="panel">
<div class="panel-heading">
<h3 class="panel-title">Articles</h3>
</div>
<ul class="list-group">
{foreach from=$articlecontentlist item=content}
<a class="list-group-item{if $menu.newwindow =="1"} external" target="null{/if}" title="{$content->title}" href="{$smarty.const.WWW_TOP}/content/{$content->id}{$content->url}">
<h4 class="list-group-item-heading">{$content->title}</h4>
<p class="list-group-item-text">Content Article Goes here Truncated to 140chars with ...</p>
</a>
{/foreach}
</ul>
</div>
{/if}
{/if}
