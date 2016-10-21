<h3>Forum</h3>

{if $results|@count > 0}

	{$pager}

	<div class="text-right" style="margin-bottom:5px;"><a class="btn btn-success" href="#new">New Post</a></div>

	<a id="top"></a>

	<table class="data table table-condensed table-striped table-highlight" id="forumtable">
		<thead>
		<tr>
			<th>Topic</th>
			<th style="width:140px;text-align:center;">Posted By</th>
			<th style="width:120px;text-align:center;">Last Update</th>
			<th style="width:80px;text-align:right;">Replies</th>
			{if isset($isadmin)}
				<th style="padding-top:0px; padding-bottom:0px;">Action</th>
			{/if}
		</tr>
		</thead>
		<tbody>
		{foreach $results as $result}
			<tr id="guid{$result.id}">
				<td style="cursor:pointer;" class="item" onclick="document.location='{$smarty.const.WWW_TOP}/forumpost/{$result.id}';">
					<a title="View post" class="title" href="{$smarty.const.WWW_TOP}/forumpost/{$result.id}">{$result.subject|escape:"htmlall"|truncate:100:'...':true:true}</a>
					<div class="hint">
						{$result.message|truncate:200:'...':false:false}
					</div>
				</td>
				<td style="width:auto;text-align:center;white-space:nowrap;">
					{if !$privateprofiles || $isadmin || $ismod}
						<a title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a><br>
					{else}
						{$result.username}
					{/if}
					<span title="{$result.createddate}">{$result.createddate|date_format}</span> ({$result.createddate|timeago})
				</td>
				<td style="width:auto;text-align:center;">
					<a href="{$smarty.const.WWW_TOP}/forumpost/{$result.id}#last" title="{$result.updateddate}">{$result.updateddate|date_format}</a> <div class="hint">({$result.updateddate|timeago})</div>
				</td>
				<td style="text-align:center;">{$result.replies}</td>
				<td>
					{if isset($isadmin)}
						<div>
							<a class="confirm_action btn btn-sm btn-danger"
							   href="{$smarty.const.WWW_TOP}/topic_delete?id={$result.id}"
							   title="Delete Topic">Delete Topic</a>
						</div>
					{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	<div class="text-right" style="margin-top:5px;"><a class="btn btn-info btn-sm" href="#top">Top</a></div>

	<br>

	{$pager}

{/if}

<a id="new"></a>
<form action="" method="post" style="margin-top:10px;">
	<fieldset>
		<legend>Add New Post</legend>
		<div class="form-group">
			<label for="addSubject">Subject:</label>
			<input type="text" class="form-control" maxlength="200" id="addSubject" name="addSubject" placeholder="">
		</div>
		<div class="form-group">
			<label for="addMessage">Message:</label>
			<textarea class="form-control" maxlength="5000" id="addMessage" name="addMessage" rows="3" placeholder=""></textarea>
		</div>
		<button class="btn btn-default" type="submit" value="submit">Submit</button>
		<input class="btn btn-warning" value="Cancel" onclick="if(confirm('Are you SURE you wish to cancel?')) history.back();" />
	</fieldset>
</form>
