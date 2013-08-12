<h3>Forum</h3>
	
{if $results|@count > 0}

{$pager}

<div class="text-right"  tyle="margin-bottom:5px;"><a class="btn btn-success" href="#new">New Post</a></div>

<a id="top"></a>

<table class="data table table-condensed table-striped table-highlight" id="forumtable">
	<thead>
	<tr>
		<th>Topic</th>
		<th style="width:140px;text-align:center;">Posted By</th>
		<th style="width:120px;text-align:center;">Last Update</th>
		<th style="width:80px;text-align:right;">Replies</th>
	</tr>
</thead>
<tbody>
	{foreach from=$results item=result}
		<tr id="guid{$result.ID}">
			<td style="cursor:pointer;" class="item" onclick="document.location='{$smarty.const.WWW_TOP}/forumpost/{$result.ID}';">
				<a title="View post" class="title" href="{$smarty.const.WWW_TOP}/forumpost/{$result.ID}">{$result.subject|escape:"htmlall"|truncate:100:'...':true:true}</a>
				<div class="hint">
					{$result.message|escape:"htmlall"|truncate:200:'...':false:false}
				</div>
			</td>
			<td style="width:auto;text-align:center;white-space:nowrap;">
				<a title="View profile" href="{$smarty.const.WWW_TOP}/profile/?name={$result.username}">{$result.username}</a><br>
				<span title="{$result.createddate}">{$result.createddate|date_format}</span> ({$result.createddate|timeago})
			</td>
			<td style="width:auto;text-align:center;">
				<a href="{$smarty.const.WWW_TOP}/forumpost/{$result.ID}#last" title="{$result.updateddate}">{$result.updateddate|date_format}</a> <div class="hint">({$result.updateddate|timeago})</div>
			</td>
			<td style="text-align:center;">{$result.replies}</td>
		</tr>
	{/foreach}
</tbody>
</table>

<div class="text-right" style="margin-top:5px;"><a class="btn btn-info btn-small" href="#top">Top</a></div>

<br>

{$pager}

{/if}

<div class="row" style="margin-top:10px;">
<a id="new"></a>
<form action="" method="post">
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
    <input class="btn btn-default forumpostsubmit" type="submit" value="submit">
  </fieldset>
</form>
</div>


