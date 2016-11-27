{if $articlecontentlist|@count > 0}
	<li class="menu_articles">
		<h2>Articles</h2>
		<ul>
			{foreach $articlecontentlist as $content}
				<li class="mmenu{if $menu.newwindow == "1"}_new{/if}">
					<a {if $menu.newwindow == "1"}class="external" target="null"{/if} title="{$content->title}" href="{$smarty.const.WWW_TOP}/content/{$content->id}{$content->url}">{$content->title}</a>
				</li>
			{/foreach}
		</ul>
	</li>
{/if}
