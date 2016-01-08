<li class="nav-header">Useful links</li>
	{if $loggedin=="true"}
	<li><a title="{$site->title} Rss Feeds" href="{$smarty.const.WWW_TOP}/rss">Rss Feeds</a></li>
	<li><a title="{$site->title} Api" href="{$smarty.const.WWW_TOP}/apihelp">Api</a></li>
	{/if}

	{foreach from=$usefulcontentlist item=content}
		<li><a {if $menu.newwindow =="1"}class="external" target="null"{/if} title="{$content.title}" href="{$smarty.const.WWW_TOP}/content/{$content.id}{$content.url}">{$content.title}</a></li>
	{/foreach}
</li>
<br><br><img src="{$smarty.const.WWW_TOP}/themes/shared/images/logo.png"/>
