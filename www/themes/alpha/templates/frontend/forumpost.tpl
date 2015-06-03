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
		{foreach from=$results item=result name=result}
			<tr>
				<td>
					{if !$privateprofiles || $isadmin || $ismod}
						<a {if $smarty.foreach.result.last}id="last"{/if} title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a>
					{else}
						{$result.username}
					{/if}
					 on<br>
					<span title="{$result.createddate}">{$result.createddate|date_format}</span> ({$result.createddate|timeago})
					{if $isadmin || $ismod}
						<div>
							<a class="label label-danger confirm_action" href="{$smarty.const.WWW_TOP}/admin/forum-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Post">Delete</a>
						</div>
					{/if}

				</td>
				<td>{$result.message|escape:"htmlall"|nl2br|magicurl}</td>
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
				<label for="addReply">Message:</label>
				<textarea class="form-control" maxlength="5000" id="addReply" name="addReply" rows="6"></textarea>
			</div>
			<button class="btn btn-success forumreplysubmit" type="submit" value="submit">Submit</button>
		</fieldset>
	</form>

{/if}