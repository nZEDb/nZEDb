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
	<h3>Nothing found, maybe you need to turn on logging in : {$path}</h3>
	<select name="logtype" id="logtype" onchange="window.location='{$smarty.const.WWW_TOP}/view-logs.php?t=' + this.value;">
		{foreach from=$types item=newtype}
			<option {if $type == $newtype}selected="selected"{/if} value="{$newtype}">
				{$newtype}
			</option>
		{/foreach}
	</select>
{/if}