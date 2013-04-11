 
<h1>{$page->title}</h1>

<table style="margin-top:10px;" class="data Sortable">

	<tr>
		<th>message</th>
	</tr>
	
	{foreach from=$tablelist item=table}
	<tr>
		<td>{$table} optimised/repaired</td>
	</tr>
	{/foreach}

</table>