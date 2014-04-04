{if $site->menuposition == 2}<!--Top Menu Framework-->
	{if $menulist|@count > 0}
		{strip}
			<ul class="nav navbar-nav">
			{foreach from=$menulist item=menu}
				{assign var="var" value=$menu.menueval}
				{eval var="$var," assign='menuevalresult'}
				{if $menu.title == "Music"
					or $menu.title == "Movies"
					or $menu.title == "Console"
					or $menu.title == "Books"
					or $menu.title == "Login"
					or $menu.title == "Register"
					or $menu.title == "Profile"
					or $menu.title == "Groups List"
					or $menu.title == "Admin"
					or $menu.title == "My Shows"
					or $menu.title == "My Movies"
					or $menu.title == "My Cart"
					or $menu.title == "My Queue"}
					{continue}
				{/if}
				{if $menuevalresult|replace:",":"1" == "1"}
					<li class="mmenu{if $menu.newwindow =="1"}_new{/if}" style="display:inline-block;"><a {if $menu.newwindow =="1"}class="external" target="null"{/if} title="{$menu.tooltip}" href="{$menu.href}">{$menu.title|replace:"Advanced ":''}</a></li>
				{/if}
			{/foreach}
			</ul>
		{/strip}
	{/if}
{/if}