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
	{foreach $files as $i=>$file name="iteration"}
		<tr>
			<td>{$smarty.foreach.iteration.index+1}</td>
			<td>{$file.title|escape:'htmlall'}</td>

			{assign var="icon" value='themes/shared/img/fileicons/'|cat:$file.ext|cat:".png"}
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

			<td><img title=".{$file.ext}" alt="{$file.ext}" src="{$smarty.const.WWW_TOP}/themes/shared/img/fileicons/{$icon}.png"></td>
			<td><span class="label {if $completion < 100}label-danger{else}label-success{/if}">{$completion}%</span></td>
			<td>{if $file.size < 100000}{$file.size|fsize_format:"KB"}{else}{$file.size|fsize_format:"MB"}{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>
