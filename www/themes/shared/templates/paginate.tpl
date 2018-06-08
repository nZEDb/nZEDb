{strip}
	{if $pagerlast > 1}
		<div class="pager">
			{if $pagecurrent > 1}
				<a title="First" href="{$pagerquerybase}1">First</a>&nbsp;&nbsp;
				<a title="Previous" href="{$pagerquerybase}{$pagecurrent-1}">&lt;</a>&nbsp;
			{/if}
			{section name=pager loop=$pagerlast+1}
				{if $smarty.section.pager.iteration == $pagecurrent}
					<span class="current" title="Current page {$smarty.section.pager.iteration}">{$smarty.section.pager.iteration}</span>&nbsp;
				{elseif $smarty.section.pager.iteration > $pagecurrent && $smarty.section.pager.iteration < ($pagecurrent + 10)}
					... <a title="Goto page {$smarty.section.pager.iteration}"
						href="{$pagerquerybase}{$smarty.section.pager.iteration}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>&nbsp;
				{elseif $smarty.section.pager.iteration < $pagecurrent && $smarty.section.pager.index >= ($pagecurrent - 10)}
					&nbsp; <a title="Goto page {$smarty.section.pager.iteration}"
						href="{$pagerquerybase}{$smarty.section.pager.iteration}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>&nbsp;...&nbsp;
				{/if}
			{/section}
			{if $pagecurrent < $pagerlast}
				&nbsp;<a title="Next" href="{$pagerquerybase}{$pagecurrent+1}">&gt;</a>
				&nbsp;
				&nbsp;<a title="Last" href="{$pagerquerybase}{$pagerlast}">Last</a>
			{/if}
		</div>
	{/if}
{/strip}
