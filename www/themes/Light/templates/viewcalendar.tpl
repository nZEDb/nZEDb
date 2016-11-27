<h1>{$page->title}</h1>

{foreach $cal as $c}
	<a href="{$smarty.const.WWW_TOP}/calendar?date={$c}">{$c}</a>&nbsp;&nbsp;&nbsp;
{/foreach}
</div>
<table>
	<tr valign="top">
		<td width="33%;">
			<table width="100%;" class="data nopad highlight icons" id="browsetable">
				{if $predata|@count > 0}
					<th style="padding-top:15px;"><h2>{$predate}</h2></th>
					{foreach $predata as $s}
						<tr class="{cycle values=",alt"}">
							<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageid}">{$s.showtitle}</a><br>{$s.fullep} - {$s.eptitle}</td>
						</tr>
					{/foreach}
				{else}
					<td style="padding-top:15px;"><h2>No results</h2></td></tr>
				{/if}
			</table>
		</td>
		<td width="33%;">
			<table width="100%;" class="data nopad highlight icons" id="browsetable">
				{if $daydata|@count > 0}
					<th style="padding-top:15px;" colspan="10"><h2>{$date}</h2></th>
					{foreach $daydata as $s}
						<tr class="{cycle values=",alt"}">
							<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageid}">{$s.showtitle}</a><br>{$s.fullep} - {$s.eptitle}</td>
						</tr>
					{/foreach}
				{else}
					<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
				{/if}
			</table>
		</td>
		<td width="33%;">
			<table width="100%;" class="data nopad highlight icons" id="browsetable">
				{if $nxtdata|@count > 0}
					<th style="padding-top:15px;"><h2>{$nxtdate}</h2></th>
					{foreach $nxtdata as $s}
						<tr class="{cycle values=",alt"}">
							<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageid}">{$s.showtitle}</a><br>{$s.fullep} - {$s.eptitle}</td>
						</tr>
					{/foreach}
				{else}
					<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
				{/if}
			</table>
		</td>
	</tr>
</table>
