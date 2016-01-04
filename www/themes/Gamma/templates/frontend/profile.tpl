<div class="span8">
<ul class="inline">
	<li><h2>Profile for {$user.username|escape:"htmlall"}</h2></li>
	{if $user.id==$userdata.id}
		<li style="vertical-align:text-bottom;"><a href="{$smarty.const.WWW_TOP}/profileedit" class="btn btn-small btn-warning">Edit</a></li>
	{/if}
</ul>
<table class="data table" width="100%">
	<tr>
		<th width="30%">Username:</th>
		<td width="70%">{$user.username|escape:"htmlall"}</td>
	</tr>
	{if $user.id==$userdata.id || $userdata.role==2}
	<tr>
		<th title="Not public">Email:</th>
		<td>{$user.email}</td>
	</tr>
	{/if}
	<tr>
		<th>Registered:</th>
		<td title="{$user.createddate}">{$user.createddate|date_format}  ({$user.createddate|timeago} ago)</td>
	</tr>
	<tr>
		<th>Last Login:</th>
		<td title="{$user.lastlogin}">{$user.lastlogin|date_format}  ({$user.lastlogin|timeago} ago)</td>
	</tr>
	<tr>
		<th>Role:</th>
		<td>{$user.rolename}</td>
	</tr>
	{if isset($user.notes) && $userdata.role == 2}
		<tr>
			<th title="Admin Notes">Notes:</th>
			<td>{$user.notes|escape:htmlall}{if $user.notes|count_characters > 0}<br/>{/if}<a href="{$smarty.const.WWW_TOP}/admin/user-edit.php?id={$user.id}#notes" class="btn btn-mini btn-info">Add/Edit</a></td>
		</tr>
	{/if}
	{if $user.id==$userdata.id || $userdata.role==2}
		<tr>
			<th title="Not public">Site Api/Rss Key:</th>
			<td><a href="{$smarty.const.WWW_TOP}/rss?t=0&amp;dl=1&amp;i={$user.id}&amp;r={$user.rsstoken}">{$user.rsstoken}</a></td>
		</tr>
	{/if}
	<tr>
		<th>Theme:</th>
		<td>{$user.style}</td>
	</tr>
	{if $user.id==$userdata.id || $userdata.role==2}
		<tr>
			<th>API Hits Today:</th>
			<td><span id="uatd">{$apirequests.num}</span> {if $userdata.role==2 && $apirequests.num > 0}&nbsp;&nbsp;&nbsp;<a onclick="resetapireq({$user.id}, 'api'); document.getElementById('uatd').innerHTML='0'; return false;" class="btn btn-mini btn-info" href="#">Reset</a>{/if}</td>
		</tr>
		<tr>
			<th>Grabs Today:</th>
			<td><span id="ugrtd">{$grabstoday}</span> {if $user.grabs >= $user.downloadrequests}&nbsp;&nbsp;<small>(Next DL in {($grabstoday.nextdl/3600)|intval}h {($grabstoday.nextdl/60) % 60}m)</small>{/if}{if $userdata.role==2 && $user.grabs> 0}&nbsp;&nbsp;&nbsp;<a onclick="resetapireq({$user.id}, 'grabs'); document.getElementById('ugrtd').innerHTML='0'; return false;" class="btn btn-mini btn-info" href="#">Reset</a>{/if}</td>
		</tr>
	{/if}
	<tr>
		<th>Grabs Total:</th>
		<td>{$user.grabs}</td>
	</tr>
	{if (!$publicview || $isadmin) && $site->registerstatus==1}
	<tr>
		<th title="Not public">Invites</th>
		<td>{$user.invites} </td>
	</tr>
	{if $user.invites > 0}
	<tr>
		<th>Invite someone</th>
		<td>
			<a id="lnkSendInvite" onclick="return false;" class="btn btn-small btn-info" href="#">Send Invite</a>

			<div style="display:none; margin-top:20px;" id="divInvite">
				<div style="display:none;" class="invitesuccess alert alert-success " id="divInviteSuccess"><strong>Invite Sent</strong><br/></div>
				<div style="display:none;" class="invitefailed alert alert-error" id="divInviteError"></div>
				<form id="frmSendInvite" method="GET">
					<input class="input-block-level" type="text" id="txtInvite" placeholder="Email"/>
					<input class="btn btn-success" type="submit" value="Send"/>
					<a id="lnkCancelInvite" onclick="return false;" class="btn btn-warning" href="#">Cancel</a>
				</form>
			</div>
		{/if}
		</td>
	</tr>
	{/if}
	{if $userinvitedby && $userinvitedby.username != ""}
	<tr>
		<th>Invited By:</th>
		{if $privileged || !$privateprofiles}
		<td><a title="View {$userinvitedby.username}'s profile" href="{$smarty.const.WWW_TOP}/profile?name={$userinvitedby.username}">{$userinvitedby.username}</a>
			{else}
			{$userinvitedby.username}
			{/if}
		</td>
	</tr>
	{/if}
	<tr>
		<th>UI Preferences:</th>
		<td>
			{if $user.movieview == "1"}View movie covers{else}View standard movie category{/if}<br/>
			{if $user.musicview == "1"}View music covers{else}View standard music category{/if}<br/>
			{if $user.consoleview == "1"}View console covers{else}View standard console category{/if}<br/>
			{if $user.gameview == "1"}View games covers{else}View standard games category{/if}<br/>
			{if $user.bookview == "1"}View book covers{else}View standard book category{/if}<br/>
			{if $user.xxxview == "1"}View xxx covers{else}View standard xxx category{/if}<br/>
		</td>
	</tr>
	{if $user.id==$userdata.id || $userdata.role==2}
		<tr>
			<th title="Not public">Excluded Categories:</th>
			<td>{$exccats|replace:",":"<br/>"}</td>
		</tr>
	{/if}
	{if $site->sabintegrationtype == 2 && $user.id==$userdata.id}
		<tr>
			<th>SABnzbd Integration:</th>
			<td>
				<b>Url:</b> {if $saburl == ''}N/A{else}{$saburl}{/if}<br/>
				<b>Key:</b> {if $sabapikey == ''}N/A{else}{$sabapikey}{/if}<br/>
				<b>Type:</b> {if $sabapikeytype == ''}N/A{else}{$sabapikeytype}{/if}<br/>
				<b>Priority:</b> {if $sabpriority == ''}N/A{else}{$sabpriority}{/if}<br/>
				<b>Storage:</b> {if $sabsetting == ''}N/A{else}{$sabsetting}{/if}
			</td>
		</tr>
	{/if}
	{if $user.id==$userdata.id}
			<tr>
				<th>My TV Shows:</th>
				<td><a href="{$smarty.const.WWW_TOP}/myshows" class="btn btn-mini btn-info">Manage my shows</a></td>
			</tr>
			<tr>
				<th>My Movies:</th>
				<td><a href="{$smarty.const.WWW_TOP}/mymovies" class="btn btn-mini btn-info">Manage my movies</a></td>
			</tr>
	{/if}
</table>
{if $userdata.role==2 && isset($downloadlist) && $downloadlist|@count > 0}
<div style="padding-top:20px;">
	<h3>Downloads for User and Host</h3>
	<table class="data Sortable highlight table table-striped" id="downloadtable" style="margin-top:10px;">
	<tr>
		<th>date</th>
		<th>hosthash</th>
		<th>release</th>
	</tr>
	{foreach from=$downloadlist item=download}
		{if $download@iteration == 10}
			<tr class="more">
				<td colspan="3"><a onclick="$('tr.extra').toggle();$('tr.more').toggle();return false;" href="#">show all...</a></td>
			</tr>
		{/if}
		<tr {if $download@iteration >= 10}class="extra" style="display:none;"{/if}>
			<td class="less" title="{$download.timestamp}">{$download.timestamp|date_format}</td>
			<td title="{$download.hosthash}">{if $download.hosthash == ""}n/a{else}{$download.hosthash|truncate:10}{/if}</td>
			<td>{if $download.guid == ""}n/a{else}<a href="{$smarty.const.WWW_TOP}/details/{$download.guid}{/if}</td>
		</tr>
		{/foreach}
	</table>
</div>
{/if}

{if $commentslist|@count > 0}
<div style="padding-top:20px;">
	<a id="comments"></a>
	<h3>Comments</h3>
	{$pager}
	<table style="margin-top:10px;" class="data Sortable table">
		<tr>
			<th>date</th>
			<th>release</th>
			<th>comment</th>
		</tr>
		{foreach from=$commentslist item=comment}
		<tr>
			<td width="80" title="{$comment.createddate}">{$comment.createddate|date_format}</td>
			<td><a href="{$smarty.const.WWW_TOP}/details/{$comment.guid}/{$comment.searchname|escape:"seourl"}">{$comment.searchname}</a></td>
			<td>{$comment.text|escape:"htmlall"|nl2br}</td>
		</tr>
		{/foreach}
	</table>
</div>
{/if}
</div>
