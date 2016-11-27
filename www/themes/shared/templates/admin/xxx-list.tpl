<h1>{$page->title}</h1>
{if $xxxmovielist}
	{$pager}
	<table style="margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th>XXXInfo ID</th>
			<th>Title</th>
			<th>Trailer</th>
			<th>Cover</th>
			<th>Backdrop</th>
			<th>Created</th>
		</tr>
		{foreach from=$xxxmovielist item=xxxmovie}
			<tr class="{cycle values=",alt"}">
				<td class="less">{$xxxmovie.id}</a></td>
				<td><a title="Edit" href="{$smarty.const.WWW_TOP}/xxx-edit.php?id={$xxxmovie.id}">{$xxxmovie.title}</a></td>
				<td class="less">{$xxxmovie.hastrailer}</td>
				<td class="less">{$xxxmovie.cover}</td>
				<td class="less">{$xxxmovie.backdrop}</td>
				<td class="less">{$xxxmovie.createddate|date_format}</td>
			</tr>
		{/foreach}
	</table>
{else}
	<p>No XXX Movies available.</p>
{/if}
