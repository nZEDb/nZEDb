<h1>{$page->title}</h1> 

{if $commentslist}
{$pager}

<table style="margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th>user</th>
		<th>date</th>
		<th>comment</th>
		<th>host</th>
		<th>options</th>
	</tr>

	
	{foreach from=$commentslist item=comment}
	<tr class="{cycle values=",alt"}">
		<td><a href="{$smarty.const.WWW_TOP}/user-edit.php?id={$comment.userID}">{$comment.username}</a></td>
		<td title="{$comment.createddate}">{$comment.createddate|date_format}</td>
		<td>{$comment.text|escape:"htmlall"|nl2br}</td>
		<td>{$comment.host}</td>
		<td>
			<a href="{$smarty.const.WWW_TOP}/../details/{$comment.guid}#comments">view</a> | 
			<a href="{$smarty.const.WWW_TOP}/comments-delete.php?id={$comment.ID}">delete</a>
		</td>
	</tr>
	{/foreach}


</table>
{else}
<p>No comments available</p>
{/if}
