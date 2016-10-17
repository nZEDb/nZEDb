
<h1><a href="{$smarty.const.WWW_TOP}/forum">Forum</a></h1>

{if $results|@count > 0}

<h2>{$results[0].subject|escape:"htmlall"}</h2>

<div style="float:right;margin-bottom:5px;"><a href="#reply">Reply</a></div>

<a id="top"></a>
<table style="width:100%;" class="data highlight" id="forumtable">
	<tr>
		<th>By</th>
		<th>Message</th>
	</tr>

	{foreach $results as $result name=result}
		<tr class="{cycle values=",alt"}">
			<td width="15%;">
				{if !$privateprofiles || $isadmin || $ismod}
					<a {if $smarty.foreach.result.last}id="last"{/if} title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a>
				{else}
					{$result.username}
				{/if}
				<br/>
				on <span title="{$result.createddate}">{$result.createddate|date_format}</span> <div class="hint">({$result.createddate|timeago})</div>
				{if $userdata.id == $result.user_id || $isadmin || $ismod}
					<div>
						<a class="btn btn-mini btn-warning"
						   href="{$smarty.const.WWW_TOP}/post_edit?id={$result.id}"
						   title="Edit Post">Edit</a>
					</div>
				{/if}
				{if $userdata.role==2}
				<div>
					<a class="rndbtn confirm_action" href="{$smarty.const.WWW_TOP}/admin/forum-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Post">Delete</a>
				</div>
				{/if}

			</td>
			<td>{$result.message}</td>
		</tr>
	{/foreach}

</table>

<div style="float:right;margin-top:5px;"><a href="#">Top</a></div>


<div style="margin-top:10px;">
<h3>Add Reply</h3>
<a id="reply"></a>
<form action="" method="post">
	<label for="addMessage">Message:</label><br/>
	<textarea id="addMessage" name="addMessage"></textarea>
	<br/>
	<input type="submit" value="Submit"/>
	<input value="Cancel" onclick="if(confirm('Are you SURE you wish to cancel?')) history.back();" />
</form>
</div>

{/if}
