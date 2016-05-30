{if $recentforumpostslist|@count > 0}
	<li class="menu_recentposts">
		<h2>Recent Posts</h2>
		<ul>
			{foreach $recentforumpostslist as $content}
				<li class="mmenu">
					<a title="by {$content.username|escape:htmlall}"
					   href="{$smarty.const.WWW_TOP}/forumpost/{$content.id}">{$content.subject|escape:htmlall}</a>
				</li>
			{/foreach}
		</ul>
	</li>
{/if}
