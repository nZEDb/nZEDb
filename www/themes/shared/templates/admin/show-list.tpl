<h1>{$page->title}</h1>
{if $tvshowlist}
	<div style="float:right;">
		<form name="showsearch" action="">
			<label for="showname">Title</label>
			<input id="showname" type="text" name="showname" value="{$showname}" size="15" />
			&nbsp;&nbsp;
			<input type="submit" value="Go" />
		</form>
	</div>
	{$pager}
	<br/><br/>
	<table style="width:100%;margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th style="width:50px;">videos_id</th>
			<th>title</th>
			<th style="width:80px;">date</th>
			<th style="width:80px;">source</th>
			<th style="width:100px;" class="right">options</th>
		</tr>
		{foreach from=$tvshowlist item=tvshow}
			<tr class="{cycle values=",alt"}">
				<td class="less">{$tvshow.id}</td>
				<td><a title="Edit" href="{$smarty.const.WWW_TOP}/show-edit.php?id={$tvshow.id}">{$tvshow.title|escape:"htmlall"}</a></td>
				<td class="less">{$tvshow.started|date_format}</td>
				<td class="less">
					{if $tvshow.source == 1}tvdb
						{elseif $tvshow.source == 2}tvmaze
						{elseif $tvshow.source == 3}tmdb
					{/if}
				</td>
				<td class="right"><a title="delete this show entry" href="{$smarty.const.WWW_TOP}/show-delete.php?id={$tvshow.id}">delete</a> | <a title="remove this showid from all releases" href="{$smarty.const.WWW_TOP}/show-remove.php?id={$tvshow.id}">remove</a></td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>No TV Shows available.</p>
{/if}