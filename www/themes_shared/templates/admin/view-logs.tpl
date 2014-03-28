<h1>{$page->title}</h1>
<br /><br />
{if $data}
	{$pager}
	<table style="width:100%;margin-top:10px;" class="data highlight">
		<tr>
			<th>
				<select name="logtype" id="logtype" onchange="window.location='{$smarty.const.WWW_TOP}/view-logs.php?t=' + this.value;">
					{foreach from=$types item=newtype}
						<option {if $type == $newtype}selected="selected"{/if} value="{$newtype}">
							{$newtype}
						</option>
					{/foreach}
				</select>
			</th>
		</tr>
		{foreach from=$data item=log}
			<tr class="{cycle values=",alt"}">
				<td>{$log}</td>
			</tr>
		{/foreach}
	</table>
	<br />
	{$pager}
{else}
	<h2>No logs found, try enabling logging in www/automated.config.php</h2>
	<select name="logtype" id="logtype" onchange="window.location='{$smarty.const.WWW_TOP}/view-logs.php?t=' + this.value;">
		{foreach from=$types item=newtype}
			<option {if $type == $newtype}selected="selected"{/if} value="{$newtype}">
				{$newtype}
			</option>
		{/foreach}
	</select>
{/if}