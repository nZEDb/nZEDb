<h2><a href="{$smarty.const.WWW_TOP}/forum">Forum</a></h2>

{if $results|@count > 0}

<h3>{$results[0].subject|escape:"htmlall"}</h3>

<a id="top"></a>
<table style="width:100%;" class="data highlight table table-striped" id="forumtable">
	<tr>
		<th style="padding-top:0px; padding-bottom:0px;">By</th>
		<th style="padding-top:0px; padding-bottom:0px;">Message</th>
	</tr>

	{foreach from=$results item=result name=result}
		<tr class="{cycle values=",alt"}">
			<td width="15%;">
				{if $result.isadmin == 1}<strong>{/if}
				<a {if $smarty.foreach.result.last}id="last"{/if} title="{if $result.isadmin == 1}Admin{else}View profile{/if}" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a>
				{if $result.isadmin == 1}</strong>{/if}
				<br/>
				on <span title="{$result.createddate}">{$result.createddate|date_format}</span> <div class="hint">({$result.createddate|timeago})</div>
				{if $userdata.role==2}
				<br/>
				<div>
					<a class="rndbtn confirm_action btn btn-mini btn-danger" href="{$smarty.const.WWW_TOP}/admin/forum-delete.php?id={$result.id}&amp;from={$smarty.server.REQUEST_URI|escape:"url"}" title="Delete Post">Delete</a>
				</div>
				{/if}

			</td>
			<td>{$result.message|escape:"htmlall"|nl2br|magicurl}</td>
		</tr>
	{/foreach}

</table>



<div style="float:right;margin-top:5px;">
	<a href="#new" role="button" class="btn btn-small btn-primary" data-toggle="modal">Reply</a>
	{if $results|@count > 10}
		<a class="btn btn-small" href="#">Top</a>
	{/if}
</div>


<div id="new" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel">Add New Post</h3>
	</div>
	<div class="modal-body">
		<form id="forum-post-reply" class="form-horizontal" action="" method="post">
			<div class="control-group">
				<label class="control-label" for="addReply">Message</label>
				<div class="controls">
					<textarea class="input input-xlarge" maxlength="5000" id="addReply" name="addReply" rows="6" cols="100"></textarea>
				</div>
			</div>
		</form>
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
		<input id="forum-post-reply-submit" class="forumreplysubmit btn btn-success" type="submit" value="submit"/>
	</div>
</div>

<script>
  $('#forum-post-reply-submit').on('click', function(e){
	// We don't want this to act as a link so cancel the link action
	e.preventDefault();

	// Find form and submit it
	$('#forum-post-reply').submit();
  });
</script>


{/if}
