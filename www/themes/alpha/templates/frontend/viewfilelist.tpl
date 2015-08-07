<h4>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}">{$rel.searchname|escape:'htmlall'}</a></h4>

<table class="table table-striped table-condensed data">
	<thead>
	<tr>
		<th>#</th>
		<th>filename</th>
		<th>type</th>
		<th>completion</th>
		<th>size</th>
	</tr>
	</thead>
	<tbody>
	{foreach item=i name=iteration from=$files item=file}
		<tr>
			<td>{$smarty.foreach.iteration.index+1}</td>
			<td>{$file.title|escape:'htmlall'}</td>

			{assign var="icon" value='themes_shared/images/fileicons/'|cat:$file.ext|cat:".png"}
			{if $file.ext == "" || !is_file("$icon")}
				{assign var="icon" value='file'}
			{else}
				{assign var="icon" value=$file.ext}
			{/if}

			{assign var="completion" value=($file.partsactual/$file.partstotal*100)|number_format:1}

			<td><img title=".{$file.ext}" alt="{$file.ext}" src="{$smarty.const.WWW_TOP}/themes_shared/images/fileicons/{$icon}.png"></td>
			<td><span class="label {if $completion < 100}label-danger{else}label-success{/if}">{$completion}%</span></td>
			<td>{if $file.size < 100000}{$file.size|fsize_format:"KB"}{else}{$file.size|fsize_format:"MB"}{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>