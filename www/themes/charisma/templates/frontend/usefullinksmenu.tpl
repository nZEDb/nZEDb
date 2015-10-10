<li class="menu_useful">
	<h2>Useful Links</h2>
	<ul>
		<li class="mmenu"><a title="Contact Us" href="{$smarty.const.WWW_TOP}/contact-us">Contact Us</a></li>
		<li class="mmenu"><a title="Site Map" href="{$smarty.const.WWW_TOP}/sitemap">Site Map</a></li>
		{if $loggedin=="true"}
			<li class="mmenu"><a title="Search Raw Headers" href="{$smarty.const.WWW_TOP}/searchraw">Raw Search</a></li>
			<li class="mmenu"><a title="{$site->title} Rss Feeds" href="{$smarty.const.WWW_TOP}/rss">Rss Feeds</a></li>
			<li class="mmenu"><a title="{$site->title} Api" href="{$smarty.const.WWW_TOP}/apihelp">Api</a></li>
		{/if}
		{foreach from=$usefulcontentlist item=content}
			<li class="mmenu{if $menu.newwindow =="1"}_new{/if}"><a {if $menu.newwindow =="1"}class="external"
																	target="null"{/if} title="{$content->title}"
																	href="{$smarty.const.WWW_TOP}/content/{$content->id}{$content->url}">{$content->title}</a>
			</li>
		{/foreach}
	</ul>
</li>