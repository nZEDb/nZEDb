{if $articlecontentlist|@count > 0}
<li class="nav-header">Articles</li>
	{foreach from=$articlecontentlist item=content}

		<li>
			<a {if $menu.newwindow =="1"}class="external" target="null"{/if} title="{$content->title}" href="{$smarty.const.WWW_TOP}/content/{$content->id}{$content->url}">{$content->title}</a>
		</li>
	{/foreach}
</li>
{/if}



