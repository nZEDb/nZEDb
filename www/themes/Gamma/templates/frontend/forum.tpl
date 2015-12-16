<h2>{if $page->title !=''}{$page->title}{else}Forum{/if}</h2>


{if $results|@count > 0}

<div class=".pagination pagination-centered">
	{$pager}
</div>

<div style="float:right;margin-bottom:5px;"><a href="#new" role="button" class="btn btn-small btn-primary" data-toggle="modal">New post</a></div>


<a id="top"></a>

<table style="width:100%;" class="data highlight table table-striped" id="forumtable">
	<tr>
		<th style="padding-top:0px; padding-bottom:0px;" width="60%">Topic</th>
		<th style="padding-top:0px; padding-bottom:0px;">Posted By</th>
		<th style="padding-top:0px; padding-bottom:0px;">Last Update</th>
		<th style="padding-top:0px; padding-bottom:0px;" width="5%" class="mid">Replies</th>
	</tr>

	{foreach from=$results item=result}
	<tr class="{cycle values=",alt"}" id="guid{$result.id}">
		<td style="cursor:pointer;" class="item" onclick="document.location='{$smarty.const.WWW_TOP}/forumpost/{$result.id}';">
			<a title="View post" class="title" href="{$smarty.const.WWW_TOP}/forumpost/{$result.id}">{$result.subject|escape:"htmlall"|truncate:100:'...':true:true}</a>
			<div class="hint">
				{$result.message|escape:"htmlall"|truncate:200:'...':false:false}
			</div>
		</td>
		<td>
			<a title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a>
			<br/>
			on <span title="{$result.createddate}">{$result.createddate|date_format}</span> <div class="hint">({$result.createddate|timeago})</div>
		</td>
		<td>
			<a href="{$smarty.const.WWW_TOP}/forumpost/{$result.id}#last" title="{$result.updateddate}">{$result.updateddate|date_format}</a> <div class="hint">({$result.updateddate|timeago})</div>
		</td>
		<td class="mid">{$result.replies}</td>
	</tr>
	{/foreach}

</table>

<div style="float:right;margin-top:5px;"><a class="btn btn-small" href="#top">Top</a></div>

<br/>

<div class=".pagination pagination-centered">
	{$pager}
</div>

{/if}

<div id="new" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
	    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	    <h3 id="myModalLabel">Add New Post</h3>
	</div>
    <div class="modal-body">
    	<form id="new-forum-thread" class="form-horizontal" action="" method="post">

		<div class="control-group">
			<label class="control-label" for="addSubject">Subject</label>
			<div class="controls">
				<input class="input input-xlarge" type="text" maxlength="200" id="addSubject" name="addSubject" />
			</div>
		</div>

		<div class="control-group">
			<label class="control-label" for="addMessage">Message</label>
			<div class="controls">
				<textarea cols="100" rows="3" class="input input-xlarge" maxlength="5000" id="addMessage" name="addMessage" rows="6" cols="60"></textarea>
			</div>
		</div>
		</form>
    </div>
    <div class="modal-footer">
    	<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
    	<input id="new-forum-thread-submit" class="forumpostsubmit btn btn-success" type="submit" value="submit"/>
    </div>
</div>

<script>
  $('#new-forum-thread-submit').on('click', function(e){
    // We don't want this to act as a link so cancel the link action
    e.preventDefault();

    // Find form and submit it
    $('#new-forum-thread').submit();
  });
</script>

<br/><br/><br/>
