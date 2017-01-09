<div class="well well-sm" id="group_list">
	<h1>{$page->title}</h1>
	<p>
		Below is a list of MGR posters
	</p>
	{if $posterslist}
		<table style="width:100%;" class="data table table-striped responsive-utilities sortable">
			<tr>
				<th>Poster name</th>
			</tr>
			{foreach from=$posterslist item=poster}
				<tr id="poster" class="{cycle values=",alt"}">
					<td>
						<a href="{$smarty.const.WWW_TOP}/posters-edit.php?id={$poster.id}&poster={$poster.poster}">{$poster.poster}</a>
					</td>
				</tr>
			{/foreach}
		</table>
	{else}
		<p>No MGR posters available (eg. none have been added).</p>
	{/if}
</div>
