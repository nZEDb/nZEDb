<h3><a href="{$smarty.const.WWW_TOP}/forum">Forum</a></h3>

{if $results|@count > 0}

	<h2>{$results[0].subject|escape:"htmlall"}</h2>

	<div class="text-right" style="margin-bottom:5px;"><a class="btn btn-info btn-sm" href="#reply">Reply</a></div>

	<a id="top"></a>

	<table class="table table-highlight data" id="forumtable">
		<thead>
		<tr>
			<th>By</th>
			<th>Message</th>
		</tr>
		</thead>
		<tbody>
		{foreach $results as $result name=result}
			<tr>
				<td>
					{if !$privateprofiles || $isadmin || $ismod}
						<a {if $smarty.foreach.result.last}id="last"{/if} title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a>
					{else}
						{$result.username}
					{/if}
					 on<br>
					<span title="{$result.createddate}">{$result.createddate|date_format}</span> ({$result.createddate|timeago})
					{if $userdata.id == $result.user_id || $isadmin || $ismod}
						<div>
							<a class="label label-warning"
							   href="{$smarty.const.WWW_TOP}/post_edit?id={$result.id}"
							   title="Edit Post">Edit</a>
						</div>
					{/if}
					{if $isadmin || $ismod}
						<div>
							<a class="label label-danger confirm_action" href="{$smarty.const.WWW_TOP}/admin/forum-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Post">Delete</a>
						</div>
					{/if}

				</td>
				<td>{$result.message}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	<div class="text-right" style="margin-bottom:5px;"><a class="btn btn-info btn-sm" href="#">Top</a></div>

	<a id="reply"></a>
	<form action="" method="post">
		<fieldset>
			<h3>Add Reply</h3>
			<div class="form-group">
				<label for="addMessage">Message:</label>
				<textarea id="addMessage" name="addMessage" ></textarea>
			</div>
			<button class="btn btn-success" type="submit" value="submit">Submit</button>
			<input class="btn btn-warning" value="Cancel" onclick="if(confirm('Are you SURE you wish to cancel?')) history.back();" />
		</fieldset>
	</form>

{/if}
