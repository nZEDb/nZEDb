{if $site->menuposition == 1 or $site->menuposition == 0}
	{if $articlecontentlist|@count > 0}
		<div class="panel nzedb-panel">
			<div class="panel-heading nzedb-panel-heading">
				<h3 class="panel-title">Useful Links</h3>
			</div>

			<ul class="list-group list-group-flush">
				<a class="list-group-item" title="Contact Us" href="{$smarty.const.WWW_TOP}/contact-us" style="font-weight:bold">Contact Us</a>
				<a class="list-group-item" title="Site Map" href="{$smarty.const.WWW_TOP}/sitemap" style="font-weight:bold">Site Map</a>
				{if $loggedin=="true"}
					<a class="list-group-item" title="{$site->title} Rss Feeds" href="{$smarty.const.WWW_TOP}/rss" style="font-weight:bold">Rss Feeds</a>
					<a class="list-group-item" title="{$site->title} Api" href="{$smarty.const.WWW_TOP}/apihelp" style="font-weight:bold">Api</a>
				{/if}
				{foreach from=$usefulcontentlist item=content}
					<a class="list-group-item{if $menu.newwindow =="1"} external" target="null{/if}" title="{$content->title}" href="{$smarty.const.WWW_TOP}/content/{$content->id}{$content->url}" style="font-weight:bold">{$content->title}</a>
				{/foreach}
			</ul>
		</div><!--/.well -->
	{/if}
{/if}