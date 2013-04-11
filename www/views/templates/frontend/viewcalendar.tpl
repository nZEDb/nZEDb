<h1>{$page->title}</h1>

<div style="float:right;">
{foreach $cal as $c}
<a href="{$smarty.const.WWW_TOP}/calendar?date={$c}">{$c}</a>&nbsp;&nbsp;&nbsp;             
{/foreach}
</div>
<table><tr valign="top"><td width="33%";>
<table width="100%;" class="data highlight icons" id="browsetable">
	<tr>
	{if $predata|@count > 0}
		<td style="padding-top:15px;" colspan="10"><h2>{$predate}</h2></td>
	</tr>
	{foreach $predata as $s}
		<tr class="{cycle values=",alt"}">
			<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
		</tr>
	{/foreach}
{else}
<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
{/if}
</table>
</td><td width="33%";>
<table width="100%;" class="data highlight icons" id="browsetable">
	<tr>
	{if $daydata|@count > 0}
		<td style="padding-top:15px;" colspan="10"><h2>{$date}</h2></td>
	</tr>
	{foreach $daydata as $s}
		<tr class="{cycle values=",alt"}">
			<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
		</tr>
	{/foreach}
{else}
<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
{/if}
</table>
</td><td width="33%";>
<table width="100%;" class="data highlight icons" id="browsetable">
	<tr>
	{if $nxtdata|@count > 0}
		<td style="padding-top:15px;" colspan="10"><h2>{$nxtdate}</h2></td>
	</tr>
	{foreach $nxtdata as $s}
		<tr class="{cycle values=",alt"}">
			<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageID}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
		</tr>
	{/foreach}
{else}
<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
{/if}
</table>
</td></tr></table>
