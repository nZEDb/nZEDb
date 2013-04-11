 
<h1>{$page->title}</h1>

<h2>For {$rel.searchname|escape:'htmlall'}</h2>

<table style="width:100%;" class="data Sortable">

	<tr>
		<th>#</th>
		<th>filename</th>
		<th>size</th>
		<th>date</th>
	</tr>

	{foreach from=$binaries item=binary}
	<tr>
		<td width="20" title="{$binary.relpart}/{$binary.reltotalpart}">{$binary.relpart}</td>
		<td title="{$binary.name|escape:'htmlall'}">{$binary.filename}</td>
		<td class="less">{$binary.size|fsize_format:"MB"}</td>
		<td class="less" title="{$binary.date}">{$binary.date|date_format}</td>
	</tr>
	{/foreach}

</table>	