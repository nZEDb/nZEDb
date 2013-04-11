 
<h1>{$page->title}</h1>

<h2>Top Grabbers</h2>

<table style="width:100%;margin-top:10px;" class="data highlight">
	<tr>
		<th>User</th>
		<th>Grabs</th>
	</tr>

	{foreach from=$topgrabs item=result}
		<tr class="{cycle values=",alt"}">
			<td width="75%"><a href="{$smarty.const.WWW_TOP}/user-edit.php?id={$result.ID}">{$result.username}</a></td>
			<td>{$result.grabs}</td>
		</tr>
	{/foreach}
	
</table>

<br/><br/>

<h2>Top Downloads</h2>

<table style="width:100%;margin-top:10px;" class="data highlight">
	<tr>
		<th>Release</th>
		<th>Grabs</th>
		<th>Days Ago</th>
	</tr>

	{foreach from=$topdownloads item=result}
		<tr class="{cycle values=",alt"}">
			<td width="75%"><a href="{$smarty.const.WWW_TOP}/../details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
			{if $isadmin}<a href="{$smarty.const.WWW_TOP}/release-edit.php?id={$result.ID}">[Edit]</a>{/if}</td>
			<td>{$result.grabs}</td>
			<td>{$result.adddate|timeago}</td>
		</tr>
	{/foreach}
	
</table>

<br/><br/>

<h2>Releases Added In Last 7 Days</h2>

<table style="width:100%;margin-top:10px;" class="data highlight">
	<tr>
		<th>Category</th>
		<th>Releases</th>
	</tr>

	{foreach from=$recent item=result}
		<tr class="{cycle values=",alt"}">
			<td width="75%">{$result.title}</td>
			<td>{$result.count}</td>
		</tr>
	{/foreach}
	
</table>

<br/><br/>

<h2>Top Comments</h2>

<table style="width:100%;margin-top:10px;" class="data highlight">
	<tr>
		<th>Release</th>
		<th>Comments</th>
		<th>Days Ago</th>
	</tr>

	{foreach from=$topcomments item=result}
		<tr class="{cycle values=",alt"}">
			<td width="75%"><a href="{$smarty.const.WWW_TOP}/../details/{$result.guid}/{$result.searchname|escape:"htmlall"}#comments">{$result.searchname|escape:"htmlall"|replace:".":" "}</a></td>
			<td>{$result.comments}</td>
			<td>{$result.adddate|timeago}</td>
		</tr>
	{/foreach}
	
</table>
