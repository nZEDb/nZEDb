
<h1>Forum</h1>

{if $results|@count > 0}

{$pager}

<div style="float:right;margin-bottom:5px;"><a href="#new">New Post</a></div>

<a id="top"></a>

<table style="width:100%;" class="data highlight" id="forumtable">
	<tr>
		<th width="60%">Topic</th>
		<th>Posted By</th>
		<th>Last Update</th>
		<th width="5%" class="mid">Replies</th>
		{if isset($isadmin)}
			<th style="padding-top:0px; padding-bottom:0px;">Action</th>
		{/if}
	</tr>

	{foreach $results as $result}
		<tr class="{cycle values=",alt"}" id="guid{$result.id}">
			<td style="cursor:pointer;" class="item" onclick="document.location='{$smarty.const.WWW_TOP}/forumpost/{$result.id}';">
				<a title="View post" class="title" href="{$smarty.const.WWW_TOP}/forumpost/{$result.id}">{$result.subject|escape:"htmlall"|truncate:100:'...':true:true}</a>
				<div class="hint">
					{$result.message|truncate:200:'...':false:false}
				</div>
			</td>
			<td>
				{if !$privateprofiles || $isadmin || $ismod}
					<a title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a>
				{else}
					{$result.username}
				{/if}
				<br/>
				on <span title="{$result.createddate}">{$result.createddate|date_format}</span> <div class="hint">({$result.createddate|timeago})</div>
			</td>
			<td>
				<a href="{$smarty.const.WWW_TOP}/forumpost/{$result.id}#last" title="{$result.updateddate}">{$result.updateddate|date_format}</a> <div class="hint">({$result.updateddate|timeago})</div>
			</td>
			<td class="mid">{$result.replies}</td>
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

</table>

<div style="float:right;margin-top:5px;"><a href="#top">Top</a></div>

<br/>

{$pager}

{/if}

<div style="margin-top:10px;">
<a id="new"></a>
<h3>Add New Post</h3>
<form action="" method="post">
	<label for="addSubject">Subject:</label><br/>
	<input type="text" maxlength="200" id="addSubject" name="addSubject" />
	<br/>
	<label for="addMessage">Message:</label><br/>
	<textarea maxlength="5000" id="addMessage" name="addMessage" rows="6" cols="60"></textarea>
	<br/>
	<input type="submit" value="submit"/>
	<input class="btn btn-warning" value="Cancel" onclick="if(confirm('Are you SURE you wish to cancel?')) history.back();" />
</form>
</div>

<br/><br/><br/>
