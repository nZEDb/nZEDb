<p>
<i class="icon-rss-sign icon-2x" style="color:orange;"></i> Download your cart as an <a href="{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.ID}&amp;r={$userdata.rsstoken}&amp;del=1">Rss Feed</a>.
</p>

{if $results|@count > 0}
<div class="row">
		<form id="nzb_multi_operations_form" action="get">
			<div class="nzb_multi_operations pull-right">
					With Selected: <input type="button" class="btn btn-danger btn-small nzb_multi_operations_cartdelete" value="Delete">
			</div>
		</form>
</div>

<table class="table table-condensed table-highlight table-striped data" id="browsetable">
	<thead>
	<tr>
		<th style="text-align:center;"><input id="chkSelectAll" type="checkbox" class="nzb_check_all"></th>
		<th style="text-align:left;">name</th>
		<th style="text-align:center;">added</th>
		<th style="text-align:center;">options</th>
	</tr>
</thead>
<tbody>
	{foreach from=$results item=result}
		<tr class="{cycle values=",alt"}">
			<td style="text-align:center;" class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.ID}" /></td>
			<td style="text-align:left;">
				<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}/{$result.searchname|escape:"htmlall"}">{$result.searchname|escape:"htmlall"|wordwrap:75:"\n":true}</a>
			</td>
			<td style="text-align:center;" class="less" title="Added on {$result.createddate}">{$result.createddate|date_format}</td>
			<td style="text-align:center;"><a class="label label-important" title="Delete from your cart" href="?delete={$result.ID}">delete</a></td>
		</tr>
	{/foreach}
</tbody>
</table>


{else}
<h2>No NZBs in cart</h2>
{/if}