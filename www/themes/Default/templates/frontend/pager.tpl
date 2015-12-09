{strip}
{if $pagertotalitems > $pageritemsperpage}
<div class="pager">
{section name=pager loop=$pagertotalitems start=0 step=$pageritemsperpage}
{if $pageroffset == $smarty.section.pager.index}<span class="current" title="Current page {$smarty.section.pager.iteration}">{$smarty.section.pager.iteration}</span>&nbsp;
{elseif $pageroffset-$smarty.section.pager.index == $pageritemsperpage || $pageroffset+$pageritemsperpage == $smarty.section.pager.index}<a title="Goto page {$smarty.section.pager.iteration}" href="{$pagerquerybase}{$smarty.section.pager.index}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>&nbsp;{elseif ($pagertotalitems-($smarty.section.pager.index+$pageritemsperpage)) < 0}... <a title="Goto last page" href="{$pagerquerybase}{$smarty.section.pager.index}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>
{elseif $smarty.section.pager.index > (($pagertotalitems/2)+$pageroffset+1)  && $smarty.section.pager.index < (($pagertotalitems/2)+$pageroffset)+50}... <a title="Goto page {$smarty.section.pager.iteration}" href="{$pagerquerybase}{$smarty.section.pager.index}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>&nbsp;{elseif ($pagertotalitems-($smarty.section.pager.index+$pageritemsperpage)) < 0}... <a title="Goto last page" href="{$pagerquerybase}{$smarty.section.pager.index}{$pagerquerysuffix}">{$smarty.section.pager.iteration}</a>
{elseif ($smarty.section.pager.iteration == 1)}<a title="Goto first page" href="{$pagerquerybase}0{$pagerquerysuffix}">1</a> ... {/if}{/section}
</div>
{/if}
{/strip}
