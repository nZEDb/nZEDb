<h1>{$page->title}</h1>
{if $anidblist}
	<div style="float:right;">
		<form name="anidbsearch" action="">
			<label for="animetitle">Title</label>
			<input id="animetitle" type="text" name="animetitle" value="{$animetitle}" size="15" />
			&nbsp;&nbsp;
			<input type="submit" value="Go" />
		</form>
	</div>
	{$pager}
	<br/><br/>
	<table style="width:100%;margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th style="width:60px;">AniDB ID</th>
			<th>Title</th>
			<th style="width:120px;" class="right">Options</th>
		</tr>
		{foreach from=$anidblist item=anidb}
			<tr class="{cycle values=",alt"}">
				<td width="5%" class="less"><a href="http://anidb.net/perl-bin/animedb.pl?show=anime&amp;aid={$anidb.anidbid}" title="View in AniDB">{$anidb.anidbid}</a></td>
				<td width="87%"><a title="Edit" href="{$smarty.const.WWW_TOP}/anidb-edit.php?id={$anidb.anidbid}">{$anidb.title|escape:"htmlall"}</a></td>
				<td width="7%" class="center"><a title="Delete this AniDB entry" href="{$smarty.const.WWW_TOP}/anidb-delete.php?id={$anidb.anidbid}">delete</a> | <a title="Remove this anidbID from all releases" href="{$smarty.const.WWW_TOP}/anidb-remove.php?id={$anidb.anidbid}">remove</a></td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>No AniDB titles found in database.</p>
{/if}
