{if $articlecontentlist|@count > 0}
	<li class="article_menu">
		<h2>Articles</h2>
		<ul>
			{foreach from=$articlecontentlist item=content}
				<li class="mmenu">
					<a title="{$content->title}"
					   href="{$smarty.const.WWW_TOP}/content/{$content->id}{$content->url}">{$content->title}</a>
				</li>
			{/foreach}
		</ul>
	</li>
{/if}