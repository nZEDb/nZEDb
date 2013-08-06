<div class="row" style="text-align:center;margin:0 0 10px">
{foreach $cal as $c}
<small>&nbsp;<b><a href="{$smarty.const.WWW_TOP}/calendar?date={$c}">{$c}</a></b>&nbsp;</small>
{/foreach}
</div>
<div class="row" style="text-align:center;margin:0">
<div class="col-4 col-lg-4">
<table class="table table-condensed table-striped data data highlight icons" id="browsetable">
<thead>
<tr>
{if $predata|@count > 0}
<th style="padding:0px;" colspan="10"><h3 class="text-center">{$predate}</h3></th>
{else}
<th style="padding:0px;"><h3 class="text-center">No results</h3></th>
{/if}
</tr>
</thead>
<tbody>
{foreach $predata as $s}
<tr class="{cycle values=",alt"}">
<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
</tr>
{/foreach}
</tbody>
</table>
</div>
<div class="col-4 col-lg-4">
<table class="table table-condensed table-striped data highlight icons" id="browsetable">
<thead>
<tr>
{if $daydata|@count > 0}
<th style="padding:0px;" colspan="10"><h3 class="text-center">{$date}</h3></th>
{else}
<th style="padding:0px;"><h3 class="text-center">No results</h3></th>
{/if}
</tr>
</thead>
<tbody>
{foreach $daydata as $s}
<tr class="{cycle values=",alt"}">
<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}
</td>
</tr>
{/foreach}
</tbody>
</table>
</div>
<div class="col-4 col-lg-4">
<table class="table table-condensed table-striped data data highlight icons" id="browsetable">
<thead>
<tr>
{if $nxtdata|@count > 0}
<th style="padding:0px;" colspan="10"><h3 class="text-center">{$nxtdate}</h3></th>
{else}
<th style="padding:0px;"><h3 class="text-center">No results</h3></th>
{/if}
</tr>
</thead>
<tbody>
{foreach $nxtdata as $s}
<tr class="{cycle values=",alt"}">
<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}
</td>
</tr>
{/foreach}
</tbody>
</table>
</div>
</div>