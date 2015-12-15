<h2>{$page->title} </h2>

<h4>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}/{$rel.searchname|escape:"seourl"}">{$rel.searchname|escape:'htmlall'}</a></h4>
<br/>

<table class="data Sortable highlight table table-striped">

	<tr>
		<th>#</th>
		<th>filename</th>
		<th></th>
		<th style="text-align:center;">completion</th>
		<th style="text-align:center;">size</th>
	</tr>

	{foreach item=i name=iteration from=$files item=file}
	<tr class="{cycle values=",alt"}">
		<td width="30">{$smarty.foreach.iteration.index+1}</td>
		<td>{$file.title|escape:'htmlall'}</td>

		{assign var="icon" value='themes/Gamma/images/fileicons/'|cat:$file.ext|cat:".png"}
		{if $file.ext == "" || !is_file("$icon")}
			{assign var="icon" value='file'}
		{else}
			{assign var="icon" value=$file.ext}
		{/if}

		{if $file.partstotal != 0}
			{assign var="completion" value=($file.partsactual/$file.partstotal*100)|number_format:1}
		{else}
			{assign var="completion" value=0|number_format:1}
		{/if}

		<td width="30"><img title=".{$file.ext}" alt="{$file.ext}" src="{$smarty.const.WWW_TOP}/themes/Gamma/images/fileicons/{$icon}.png" /></td>
		<td class="less right"><center>{if $completion < 100}<span class="label label-important">{$completion}%</span>{else}<span class="label label-success">{$completion}%</span>{/if}</center></td>
		<td width="80" class="less right">{if $file.size < 100000}{$file.size|fsize_format:"KB"}{else}{$file.size|fsize_format:"MB"}{/if}</td>
	</tr>
	{/foreach}

</table>

