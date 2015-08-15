<h1>{$page->title}</h1>
<div style="float:right;margin-bottom:5px;">
	<form name="predbsearch" action="" method="get">
		<label for="title">Search:</label>
		&nbsp;&nbsp;<input id="q" type="text" name="q" value="{$query}" size="25"/>
		&nbsp;&nbsp;
		<input type="submit" value="Go"/>
	</form>
</div>
{$site->adbrowse}
{if isset($results) && $results|@count > 0}
	{$pager}
<table class="data table table-condensed table-striped table-responsive table-hover">
	<tr>
		<th width="125" class="mid">Date</th>
		<th class="left">Directory</th>
		<th class="mid">Category</th>
		<th class="mid">FS/FC</th>
	</tr>
	{foreach $results as $pre}
	<tr class="{cycle values=",alt"}">
		<td class="left">{$pre.ctime|date_format:"%b %e, %Y %T"}</td>
		<td class="left">
			{if $pre.guid != ''}
				<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$pre.guid}">{$pre.dirname|wordwrap:80:"\n":true}</a>
				{else}
				{$pre.dirname|wordwrap:80:"\n":true}
				{if $pre.nuketype != '' && $pre.nukereason != ''}</br style="font-size: 12px"><sub>({$pre.nuketype}: {$pre.nukereason})</sub>{/if}
			{/if}
		</td>
		<td class="mid"><a href="{$smarty.const.WWW_TOP}/predb?c={$pre.category}">{$pre.category}</a></td>
		<td class="mid">{if $pre.filesize > 0}{$pre.filesize}MB{if $pre.filecount > 0}/{$pre.filecount}F{/if}{else}--{/if}</td>
	</tr>
{/foreach}
</table>
</br>
{$pager}
{else}
<h2>No results.</h2>
{/if}