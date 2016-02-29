{if $results|@count > 0}
	<div class="container">
		<div class="pull-left"><i class="icon-rss-sign icon-2x" style="color:orange;"></i> Download your cart as an <a href="{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">Rss Feed</a>.</div>
	</div>
	<br>
	<form id="nzb_multi_operations_form" action="get">
		<div class="container nzb_multi_operations text-right" style="margin-bottom:5px;">
			With Selected: <button type="button" class="btn btn-danger btn-sm nzb_multi_operations_cartdelete">Delete</button>
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
				<tr>
					<td style="text-align:center;" class="check"><input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check" value="{$result.id}" /></td>
					<td style="text-align:left;">
						<a title="View details" href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|wordwrap:75:"\n":true}</a>
					</td>
					<td style="text-align:center;" class="less" title="Added on {$result.createddate}">{$result.createddate|date_format}</td>
					<td style="text-align:center;"><a class="label label-danger" title="Delete from your cart" href="?delete={$result.id}">delete</a></td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</form>

{else}
	<h2>No NZBs in cart</h2>
{/if}
</div>