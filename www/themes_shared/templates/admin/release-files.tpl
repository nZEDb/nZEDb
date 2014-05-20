<h1>{$page->title}</h1>
<h2>For {$release.searchname|escape:'htmlall'}</h2>
<table style="width:100%;" class="data Sortable">
	<tr>
		<th>#</th>
		<th>filename</th>
		<th>size</th>
		<th>parts</th>
	</tr>
	{foreach from=$files item=file}
		<tr>
			<td style="width:20px">{counter}</td>
			<td title="{$file.title|escape:'htmlall'}">{$file.title|escape:'htmlall'}</td>
			<td class="less">{$file.size|fsize_format:"MB"}</td>
			<td>{count($file.segments)}</td>
		</tr>
	{/foreach}
</table>