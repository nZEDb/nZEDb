{if {$site->adbrowse} != ''}
	<div class="container" style="width:500px;">
		<fieldset class="adbanner div-center">
			<legend class="adbanner">Advertisement</legend>
			{$site->adbrowse}
		</fieldset>
	</div>
	<br>
{/if}
{if $results|@count > 0}
	<table class="table-striped table-condensed table-highlight data Sortable table" id="browsetable">
		<thead>
		<tr>
			<th>name</th>
			<th>description</th>
			<th>updated</th>
			<th>releases</th>
		</tr>
		</thead>
		<tbody>
		{foreach from=$results item=result}
			{if $result.num_releases > 0}
				<tr>
					<td>
						<a title="Browse releases from {$result.name|replace:"alt.binaries":"a.b"}" href="{$smarty.const.WWW_TOP}/browse?g={$result.name}">{$result.name|replace:"alt.binaries":"a.b"}</a>
					</td>
					<td>
						{$result.description}
					</td>
					<td class="less">{$result.last_updated|timeago} ago</td>
					<td class="less">{$result.num_releases}</td>
				</tr>
			{/if}
		{/foreach}
		</tbody>
	</table>
{/if}