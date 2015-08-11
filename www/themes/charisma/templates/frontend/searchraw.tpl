<div class="header">
	<h2>Search > <strong>Raw</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Search / Raw
		</ol>
	</div>
</div>
<div class="row">
	<div class="col-xlg-12 portlets">
		<div class="panel panel-default">
			<div class="panel-body pagination2">
				<form method="get" action="{$serverroot}searchraw">
					<div style="text-align:center;">
						<label for="search" style="display:none;">Search</label>
						<input id="search" name="search" value="{$search|escape:'htmlall'}" type="text"/>
						<input id="searchraw_search_button" type="submit" value="Search" class="btn btn-primary"/>
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
					<form method="post" id="dl" name="dl" action="{$serverroot}searchraw">
						<table style="width:100%;" class="data" id="browsetable">
							<tr>
								<!--<th width="10"></th>-->
								<th>filename</th>
								<th>group</th>
								<th>posted</th>
								{if isset($isadmin)}
									<th>Misc</th>
									<th>%</th>
								{/if}
								<th>Nzb</th>
							</tr>
							{foreach from=$results item=result}
								<tr class="{cycle values=",alt"}">
									<!--<td class="selection"><input name="file{$result.id}" id="file{$result.id}" value="{$result.id}" type="checkbox"/></td>-->
									<td title="{$result.xref|escape:"htmlall"}">{$result.name|escape:"htmlall"}</td>
									<td class="less">{$result.group_name|replace:"alt.binaries":"a.b"}</td>
									<td class="less" title="{$result.date}">{$result.date|date_format}</td>
									{if isset($isadmin)}
										<td><span title="procstat">{$result.procstat}</span>/<span
													title="totalparts">{$result.totalParts}</span>/<span
													title="regex">{if $result.regexid==""}_{else}{$result.regexid}{/if}</span>/<span
													title="relpart">{$result.relpart}</span>/<span
													title="reltotalpart">{$result.reltotalpart}</span></td>
										<td class="less">{if $result.binnum < $result.totalParts}<span
													style="color:red;">{$result.binnum}
												/{$result.totalParts}</span>{else}100%{/if}</td>
									{/if}
									<td class="less">{if $result.releaseid > 0}<a title="View Nzb details"
																				  href="{$smarty.const.WWW_TOP}/details/{$result.guid}">
												Yes</a>{/if}</td>
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
			</div>
		</div>
	</div>
</div>