{if $site->menuposition == 2}<!--Top Menu Framework-->
{if $menulist|@count > 0}
{strip}<ul class="menu_main" style="margin:0">
{foreach from=$menulist item=menu}
{assign var="var" value=$menu.menueval}
{eval var="$var," assign='menuevalresult'}
{if $menuevalresult|replace:",":"1" == "1"}
<li class="mmenu{if $menu.newwindow =="1"}_new{/if}" style="{if $menu.title == "Music" or $menu.title == "Movies" or $menu.title == "Console" or $menu.title == "Books" or $menu.title == "Login" or $menu.title == "Register" or $menu.title == "Profile" or $menu.title == "Groups List"}display:none;{else}display:inline-block;{/if}"><a {if $menu.newwindow =="1"}class="external" target="null"{/if} title="{$menu.tooltip}" href="{$menu.href}">{$menu.title|replace:"Advanced ":''}</a></li>
{/if}
{/foreach}
</ul>{/strip}
{/if}
{/if}

{if $site->menuposition == 1 or $site->menuposition == 0}<!--Side Menu Framework-->
<div class="panel">
<div class="panel-heading">
<h3 class="panel-title">Menu</h3>
</div>
<ul class="list-group">
{foreach from=$menulist item=menu}
{assign var="var" value=$menu.menueval}
{eval var="$var," assign='menuevalresult'}
{if $menuevalresult|replace:",":"1" == "1"}
<a class="list-group-item{if $menu.newwindow =="1"} external" target="null{/if}" title="{$menu.tooltip}" href="{$menu.href}" style="font-weight:bold">{$menu.title}</a>
{/if}
{/foreach}
</ul>
</div>
{/if}
