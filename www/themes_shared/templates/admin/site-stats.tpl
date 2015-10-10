<h1>{$page->title}</h1>
<h2>Top Grabbers</h2>
<table style="width:100%;margin-top:10px;" class="data highlight">
	<tr>
		<th>User</th>
		<th>Grabs</th>
	</tr>
	{foreach from=$topgrabs item=result}
		<tr class="{cycle values=",alt"}">
			<td style="width:75%;"><a href="{$smarty.const.WWW_TOP}/user-edit.php?id={$result.id}">{$result.username}</a></td>
			<td>{$result.grabs}</td>
		</tr>
	{/foreach}
</table>
<br /><br />
<h2>Signups</h2>
<table style="width:100%;margin-top:10px;" class="data highlight Sortable">
	<tr>
		<th>Month</th>
		<th>Signups</th>
	</tr>

	{foreach from=$usersbymonth item=result}
		{assign var="totusers" value=$totusers+$result.num}
		<tr class="{cycle values=",alt"}">
			<td width="75%">{$result.mth}</td>
			<td>{$result.num}</td>
	</tr>
	{/foreach}
	<tr><td><strong>Total</strong></td><td><strong>{$totusers}</strong></td></tr>
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
			<td style="width:75%"><a href="{$smarty.const.WWW_TOP}/../details/{$result.guid}">{$result.searchname|escape:"htmlall"|replace:".":" "}</a>
			{if $isadmin}<a href="{$smarty.const.WWW_TOP}/release-edit.php?id={$result.id}">[Edit]</a>{/if}</td>
			<td>{$result.grabs}</td>
			<td>{$result.adddate|timeago}</td>
		</tr>
	{/foreach}
</table>
<br/><br/>
{if $isadmin and $loggingon}
	<h2>Top Failed Logins and IP's</h2>
	<table style="width:100%;border:0;cellspacing:0;cellpadding:5">
		<tr>
			<td style="width:50%;">
				<!-- left table -->
				<table style="width:100%;margin-top:10px;" class="data highlight">
					<tr>
						<th colspan="4">
							<h3><br />Top Login Falures by Username and IP</h3>
						</th>
					</tr>
					<tr>
						<th>Last Attempt</th>
						<th>Username</th>
						<th>IP Address</th>
						<th>Count</th>
					</tr>
					{foreach from=$toplogincombined item=result}
						<tr class="{cycle values=",alt"}">
							<td>{$result.time}</td>
							<td>{$result.username}</td>
							<td><a href="http://network-tools.com/default.asp?prog=network&host={$result.host}" target="_blank" alt="WHOIS info on {$result.host}">{$result.host}</a></td>
							<td>{$result.count}</td>
						</tr>
					{/foreach}
				</table>
			</td>
			<td style="width:50%;">
				<!-- right table -->
				<table style="width:100%;margin-top:10px;" class="data highlight">
					<tr>
						<th colspan="3">
							<h3><br />Top Login Falures by IP</h3>
						</th>
					</tr>
					<tr>
						<th>Last Attempt</th>
						<th>IP Address</th>
						<th>Count</th>
					</tr>
					{foreach from=$toploginips item=result}
						<tr class="{cycle values=",alt"}">
							<td>{$result.time}</td>
							<td><a href="http://network-tools.com/default.asp?prog=network&host={$result.host}" target="_blank" alt="WHOIS info on {$result.host}">{$result.host}</a></td>
							<td>{$result.count}</td>
						</tr>
					{/foreach}
				</table>
			</td>
		</tr>
	</table>
	<br><br>
{/if}
<h2>Releases Added In Last 7 Days</h2>
<table style="width:100%;margin-top:10px;" class="data highlight">
	<tr>
		<th>Category</th>
		<th>Releases</th>
	</tr>
	{foreach from=$recent item=result}
		<tr class="{cycle values=",alt"}">
			<td style="width:75%;">{$result.title}</td>
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
			<td style="width:75%;"><a href="{$smarty.const.WWW_TOP}/../details/{$result.guid}#comments">{$result.searchname|escape:"htmlall"|replace:".":" "}</a></td>
			<td>{$result.comments}</td>
			<td>{$result.adddate|timeago}</td>
		</tr>
	{/foreach}
</table>
