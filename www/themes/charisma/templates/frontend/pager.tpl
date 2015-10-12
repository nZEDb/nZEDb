{assign var="pages" value=($pagertotalitems/$pageritemsperpage)|round}
{assign var="currentpage" value=($pageroffset+$pageritemsperpage)/$pageritemsperpage}
{assign var="upperhalfwaypoint" value=((($pages-$currentpage)/2)|round)+$currentpage}
{if $pages > 1}
	<div class="dataTables_paginate paging_simple_numbers" id="DataTables_Table_0_paginate">
		<ul class="pagination">
			<li>
				<a href="{$pagerquerybase}{$pageroffset-$pageritemsperpage}{$pagerquerysuffix}" aria-label="Previous">
					<span aria-hidden="true">&laquo;</span>
				</a>
			</li>
			{if $currentpage > 1}
				<li><a href="{$pagerquerybase}0{$pagerquerysuffix}">1</a></li>
			{/if}
			{if $currentpage > 3}
				<li class="disabled"><a href="#">...</a></li>
			{/if}
			{if $currentpage > 2}
				<li><a href="{$pagerquerybase}{$pageroffset-$pageritemsperpage}{$pagerquerysuffix}">{$currentpage-1}</a>
				</li>
			{/if}
			<li class="active"><a href="#">{$currentpage}</a></li>
			{if ($currentpage+1) < $pages}
				<li><a href="{$pagerquerybase}{$pageroffset+$pageritemsperpage}{$pagerquerysuffix}">{$currentpage+1}</a>
				</li>
			{/if}
			{if ($currentpage+1) < ($pages-1) && ($currentpage+2) < $upperhalfwaypoint}
				<li class="disabled"><a href="#">...</a></li>
			{/if}
			{if $upperhalfwaypoint != $pages && $upperhalfwaypoint != ($currentpage+1)}
				<li>
					<a href="{$pagerquerybase}{$upperhalfwaypoint*$pageritemsperpage}{$pagerquerysuffix}">{$upperhalfwaypoint}</a>
				</li>
			{/if}
			{if ($upperhalfwaypoint+1) < $pages}
				<li class="disabled"><a href="#">...</a></li>
			{/if}
			{if $pages > $currentpage}
				<li>
					<a href="{$pagerquerybase}{($pages*$pageritemsperpage)-$pageritemsperpage}{$pagerquerysuffix}">{$pages}</a>
				</li>
			{/if}
			<li>
				<a href="{$pagerquerybase}{$pageroffset+$pageritemsperpage}{$pagerquerysuffix}" aria-label="Next">
					<span aria-hidden="true">&raquo;</span>
				</a>
			</li>
		</ul>
	</div>
{/if}