{strip}
	{if $pagemaximum > 1}
		<div class="pager">
			{if $pagecurrent > 1}
				<a title="First" href="{$pagerquerybase}1">First</a>&nbsp;&nbsp;
				<a title="Previous" href="{$pagerquerybase}{$pagecurrent-1}">&lt;</a>&nbsp;
			{/if}
			{section name=pager start=1 loop=$pagemaximum+1}
				{if $pagecurrent == $smarty.section.pager.index}
					<span class="current" title="Current page {$smarty.section.pager.iteration}">{$smarty.section.pager.iteration}</span>&nbsp;
				{elseif $smarty.section.pager.index > $pagecurrent && $smarty.section.pager.index < ($pagecurrent + 10)}
					... <a title="Goto page {$smarty.section.pager.iteration}"
						href="{$pagerquerybase}{$smarty.section.pager.index}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>&nbsp;
				{elseif $smarty.section.pager.index < $pagecurrent && $smarty.section.pager.index >= ($pagecurrent - 10)}
					&nbsp; <a title="Goto page {$smarty.section.pager.iteration}"
						href="{$pagerquerybase}{$smarty.section.pager.index}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>&nbsp;...&nbsp;
				{/if}
			{/section}
			{if $pagecurrent < $pagemaximum}
				&nbsp;<a title="Next" href="{$pagerquerybase}{$pagecurrent+1}">&gt;</a>
				&nbsp;
				&nbsp;<a title="Last" href="{$pagerquerybase}{$pagemaximum}">Last</a>
			{/if}
		</div>
	{/if}
{/strip}
