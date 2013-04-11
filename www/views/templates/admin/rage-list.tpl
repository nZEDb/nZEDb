<h1>{$page->title}</h1> 

{if $tvragelist}

<div style="float:right;">

	<form name="ragesearch" action="">
		<label for="ragename">Title</label>
		<input id="ragename" type="text" name="ragename" value="{$ragename}" size="15" />
		&nbsp;&nbsp;
		<input type="submit" value="Go" />
	</form>
</div>

{$pager}

<br/><br/>

<table style="width:100%;margin-top:10px;" class="data Sortable highlight">

	<tr>
		<th style="width:50px;">rageid</th>
		<th>title</th>
		<th style="width:80px;">date</th>
		<th style="width:100px;" class="right">options</th>
	</tr>
	
	{foreach from=$tvragelist item=tvrage}
	<tr class="{cycle values=",alt"}">
		<td class="less"><a href="http://www.tvrage.com/shows/id-{$tvrage.rageID}" title="View in TvRage">{$tvrage.rageID}</a></td>
		<td><a title="Edit" href="{$smarty.const.WWW_TOP}/rage-edit.php?id={$tvrage.ID}">{$tvrage.releasetitle|escape:"htmlall"}</a></td>
		<td class="less">{$tvrage.createddate|date_format}</td>
		<td class="right"><a title="delete this rage entry" href="{$smarty.const.WWW_TOP}/rage-delete.php?id={$tvrage.ID}">delete</a> | <a title="remove this rageid from all releases" href="{$smarty.const.WWW_TOP}/rage-remove.php?id={$tvrage.rageID}">remove</a></td>
	</tr>
	{/foreach}

</table>
{else}
<p>No TVRage episodes available.</p>
{/if}
