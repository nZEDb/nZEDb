{if $menulist|@count > 0} 
<li class="menu_main">
	<h2>Menu</h2> 
	<ul>
	{foreach from=$menulist item=menu}
	{assign var="var" value=$menu.menueval}	
	{eval var="$var," assign='menuevalresult'}
	{if $menuevalresult|replace:",":"1" == "1"}
	<li class="mmenu{if $menu.newwindow =="1"}_new{/if}"><a {if $menu.newwindow =="1"}class="external" target="null"{/if} title="{$menu.tooltip}" href="{$menu.href}">{$menu.title}</a></li>
	{/if}
	{/foreach}
	</ul>
</li>
{/if}