<div class="page-header">
	<h1>{$page->title}</h1>
</div>

<center>
	<div class="well well-small">
	{foreach $cal as $c}
	<a href="{$smarty.const.WWW_TOP}/calendar?date={$c}">{$c}</a>&nbsp;&nbsp           
	{/foreach}
	</div>
</center>

<table width="100%;">
	<tr valign="top">
		<td width="33%";>
			
			<table width="100%;" class="data highlight icons table table-striped" id="browsetable">
				<tr class="error">
					{if $predata|@count > 0}
					<td style="padding-top:15px;" colspan="10"><h4>{$predate}</h4></td>
				</tr>
				{foreach $predata as $s}
				<tr class="{cycle values=",alt"}" height="80">
					<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageid}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
				</tr>
				{/foreach}
				{else}
				<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
				{/if}
			</table>
			
		</td>
		<td width="33%";>
			
			<table width="100%;" class="data highlight icons table table-striped" id="browsetable">
				<tr class="success">
					{if $daydata|@count > 0}
					<td style="padding-top:15px;" colspan="10"><h4>{$date}</h4></td>
				</tr>
				{foreach $daydata as $s}
				<tr class="{cycle values=",alt"}" height="80">
					<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageid}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
				</tr>
				{/foreach}
				{else}
				<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
				{/if}
			</table>
			
		</td>
		<td width="33%";>
			<table width="100%;" class="data highlight icons table table-striped" id="browsetable">
				<tr class="info">
					{if $nxtdata|@count > 0}
					<td style="padding-top:15px;" colspan="10"><h4><b>{$nxtdate}<b/></h4></td>
				</tr>
				{foreach $nxtdata as $s}
				<tr class="{cycle values=",alt"}" height="80">
					<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.rageid}">{$s.showtitle}</a><br/>{$s.fullep} - {$s.eptitle}</td>
				</tr>
				{/foreach}
				{else}
				<td style="padding-top:15px;" colspan="10"><h2>No results</h2></td></tr>
				{/if}
			</table>
		</td>
	</tr>
</table>
