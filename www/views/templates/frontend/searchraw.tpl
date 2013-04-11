 
<h1>Search Binaries</h1>

<form method="get" action="{$smarty.const.WWW_TOP}/searchraw">
	<div style="text-align:center;">
		<label for="search" style="display:none;">Search</label>
		<input id="search" name="search" value="{$search|escape:'htmlall'}" type="text"/>
		<input id="searchraw_search_button" type="submit" value="search" />
	</div>
</form>

{if $results|@count == 0 && $search != ""}
	<div class="nosearchresults">
		Your search - <strong>{$search|escape:'htmlall'}</strong> - did not match any headers.
		<br/><br/>
		Suggestions:
		<br/><br/>
		<ul>
		<li>Make sure all words are spelled correctly.</li>
		<li>Try different keywords.</li>
		<li>Try more general keywords.</li>
		<li>Try fewer keywords.</li>
		</ul>
	</div>
{elseif $search == ""}
{else}

{$site->adbrowse}	

<form method="post" id="dl" name="dl" action="{$smarty.const.WWW_TOP}/searchraw">
<table style="width:100%;" class="data" id="browsetable">
	<tr>
		<!--<th width="10"></th>-->
		<th>filename</th>
		<th>group</th>
		<th>posted</th>
		{if $isadmin}
		<th>Misc</th>
		<th>%</th>
		{/if}
		<th>Nzb</th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<!--<td class="selection"><input name="file{$result.ID}" id="file{$result.ID}" value="{$result.ID}" type="checkbox"/></td>-->
			<td title="{$result.xref|escape:"htmlall"}">{$result.name|escape:"htmlall"}</td>
			<td class="less">{$result.group_name|replace:"alt.binaries":"a.b"}</td>
			<td class="less" title="{$result.date}">{$result.date|date_format}</td>
			{if $isadmin}
			<td><span title="procstat">{$result.procstat}</span>/<span title="procattempts">{$result.procattempts}</span>/<span title="totalparts">{$result.totalParts}</span>/<span title="regex">{if $result.regexID==""}_{else}{$result.regexID}{/if}</span>/<span title="relpart">{$result.relpart}</span>/<span title="reltotalpart">{$result.reltotalpart}</span></td>
			<td class="less">{if $result.binnum < $result.totalParts}<span style="color:red;">{$result.binnum}/{$result.totalParts}</span>{else}100%{/if}</td>
			{/if}			
			<td class="less">{if $result.releaseID > 0}<a title="View Nzb details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.filename|escape:"htmlall"}">Yes</a>{/if}</td>
		</tr>
	{/foreach}
	
</table>
</form>

<!--
<div class="checkbox_operations">
	Selection:
	<a href="#" class="select_all">All</a>
	<a href="#" class="select_none">None</a>
	<a href="#" class="select_invert">Invert</a>
	<a href="#" class="select_range">Range</a>
</div>

<div style="padding-top:20px;">
	<a href="#" id="searchraw_download_selected">Download selected as Nzb</a>
</div>
-->
{/if}
