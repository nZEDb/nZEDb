{if $recentforumpostslist|@count > 0}
<li class="nav-header">Recent Posts</li>
	{foreach from=$recentforumpostslist item=content}
		<li>
			<a title="by {$content.username|escape:htmlall}" href="{$smarty.const.WWW_TOP}/forumpost/{$content.id}">{$content.subject|escape:htmlall}</a>
		</li>
	{/foreach}
</li>
{/if}
