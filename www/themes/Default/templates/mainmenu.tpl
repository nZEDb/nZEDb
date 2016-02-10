{if $menulist|@count > 0}
<li class="menu_main">
	<h2>Menu</h2>
	<ul>
	{foreach $menulist as $menu}
		{assign var="var" value=$menu.menueval}
		{eval var="$var," assign='menuevalresult'}
		{if $menu.title == "Music"
			or $menu.title == "Movies"
			or $menu.title == "Console"
			or $menu.title == "Books"
			or $menu.title == "PC Games"
			or $menu.title == "Login"
			or $menu.title == "Register"
			or $menu.title == "Profile"
			or $menu.title == "Groups List"}
			{continue}
		{/if}
		{if $menuevalresult|replace:",":"1" == "1"}
			<li class="mmenu{if $menu.newwindow == "1"}_new{/if}"><a {if $menu.newwindow == "1"}class="external" target="null"{/if} title="{$menu.tooltip}" href="{$menu.href}">{$menu.title}</a></li>
		{/if}
	{/foreach}
	</ul>
</li>
{/if}
