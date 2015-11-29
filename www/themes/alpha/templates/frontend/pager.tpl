{assign var="pages" value=($pagertotalitems/$pageritemsperpage)|ceil}
{assign var="currentpage" value=($pageroffset+$pageritemsperpage)/$pageritemsperpage}
{assign var="upperhalfwaypoint" value=((($pages-$currentpage)/2))|round+$currentpage}

{if $pages > 1}
	<!-- <div class="pagination" style="max-width='500px'; margin: 0px 0px -8px 0px;"> -->
	<ul class="pagination pagination pull-left" style="margin: 0;">
		<li {if ($currentpage-1) < 1}class="disabled"{/if}>{if ($currentpage-1) < 1}
				<span><i class="icon-double-angle-left"></i> Prev</span>
			{else}<a href="{$pagerquerybase}{$pageroffset-$pageritemsperpage}{$pagerquerysuffix}"><i
							class="icon-double-angle-left"></i> Prev</a>{/if}</li>
		{if $currentpage > 1}
			<li><a href="{$pagerquerybase}0{$pagerquerysuffix}">1</a></li>{/if}

		{if $currentpage > 3}
			<li class="disabled"><span>...</span></li>
		{/if}

		{if $currentpage > 2}
			<li><a href="{$pagerquerybase}{$pageroffset-$pageritemsperpage}{$pagerquerysuffix}">{$currentpage-1}</a>
			</li>{/if}

		<li class="disabled"><span>{$currentpage}</span></li>

		{if ($currentpage+1) < $pages}
			<li><a href="{$pagerquerybase}{$pageroffset+$pageritemsperpage}{$pagerquerysuffix}">{$currentpage+1}</a>
			</li>{/if}

		{if ($currentpage+1) < ($pages-1) && ($currentpage+2) < $upperhalfwaypoint}
			<li class="disabled"><span>...</span></li>
		{/if}

		{if $upperhalfwaypoint != $pages && $upperhalfwaypoint != ($currentpage+1)}
			<li>
			<a href="{$pagerquerybase}{$upperhalfwaypoint*$pageritemsperpage}{$pagerquerysuffix}">{$upperhalfwaypoint}</a>
			</li>{/if}

		{if ($upperhalfwaypoint+1) < $pages}
			<li class="disabled"><span>...</span></li>
		{/if}

		{if $pages > $currentpage}
			<li>
			<a href="{$pagerquerybase}{($pages*$pageritemsperpage)-$pageritemsperpage}{$pagerquerysuffix}">{$pages}</a>
			</li>{/if}
		<li{if ($currentpage+1) > $pages} class="disabled"{/if}>{if ($currentpage+1) > $pages}
				<span>Next <i class="icon-double-angle-right"></i></span>
			{else}<a href="{$pagerquerybase}{$pageroffset+$pageritemsperpage}{$pagerquerysuffix}">Next <i
							class="icon-double-angle-right"></i></a>{/if}</li>
	</ul>
{/if}