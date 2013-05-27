 
<h1>{$page->title}</h1>

{$pager}

<table style="width:100%;margin-bottom:10px; margin-top:5px;" class="data Sortable highlight">

	<tr>
		<th>title</th>
		<th>added</th>
		<th>pre-date</th>
		<th>source</th>
		<th>category</th>
		<th>size</th>
	</tr>

	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td class="predb">{$result.title}</td>
			<td class="predb">{$result.adddate}</td>
			<td class="predb">{$result.predate}</td>
			<td class="predb">{$result.source}</td>
			<td class="predb">{$result.category}</td>
			<td class="predb">{$result.size}</td>
		</tr>
	{/foreach}


</table>

<pager style="padding-bottom:10px;"> {$pager} </pager>
