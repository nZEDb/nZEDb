{if $menulist|@count > 0}
<li class="nav-header">{$site->title}</li>
<li class="nav-header">Menu</li>

	{foreach from=$menulist item=menu}
	{assign var="var" value=$menu.menueval}	
	{eval var="$var," assign='menuevalresult'}
	{if $menuevalresult|replace:",":"1" == "1"}
	{if $menu.title == "Movie releases"}<li class="nav-header">Movies</li>{/if}
	{if $menu.title == "TV Releases"}<li class="nav-header">Tv</li>{/if}
	{if $menu.title == "Music releases"}<li class="nav-header">Music</li>{/if}
	{if $menu.title == "Console"}<li class="nav-header">Misc</li>{/if}
	<li>
		<a {if $menu.newwindow =="1"}class="external" target="null"{/if} title="{$menu.tooltip}" href="{$menu.href|replace:"{$smarty.const.WWW_TOP}/":"/"}">{$menu.title}</a>
	</li>
	{/if}
	{/foreach}
</li>
{/if}