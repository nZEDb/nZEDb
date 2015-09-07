<div class="header">
	<h2>Your > <strong>Cart</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Cart
		</ol>
	</div>
</div>
<div class="alert alert-info" role="alert">
	<strong>RSS Feed</strong> <br/>
	Your cart can also be accessed via an <a
			href="{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">RSS
		feed</a>. Some NZB downloaders can read this feed and automatically start downloading.
</div>
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		<div class="nzb_multi_operations">
			<small>With Selected:</small>
			<div class="btn-group">
				<input type="button" class="nzb_multi_operations_cartdelete btn btn-sm btn-danger" value="Delete"/>
				{if isset($sabintegrated)}
					<input type="button" class="nzb_multi_operations_cartsab btn btn-sm btn-info"
						   value="Send to queue"/>
				{/if}
				<input type="button" class="nzb_multi_operations_download btn btn-sm btn-success" value="Download"/>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12 portlets">
				<div class="panel panel-default">
					<div class="panel-body pagination2">
						<table style="width:100%;" class="data table table-condensed table-striped table-responsive table-hover" id="browsetable">
							<tr>
								<th width="50"><input id="chkSelectAll" type="checkbox" class="nzb_check_all"/><label
											for="chkSelectAll" style="display:none;">Select All</label></th>
								<th>Name</th>
								<th>Added</th>
								<th>Action</th>
							</tr>
							{foreach from=$results item=result}
								<tr class="{cycle values=",alt"}">
									<td class="check">
										<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="nzb_check"
											   value="{$result.id}"/>
									</td>
									<td>
										<a title="View details"
										   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|wordwrap:75:"\n":true}</a>
									</td>
									<td class="less"
										title="Added on {$result.createddate}">{$result.createddate|date_format}</td>
									<td><a title="Delete from your cart" href="?delete={$result.id}"
										   class="btn btn-danger btn-sm" style="padding-bottom:2px;">Delete</a></td>
								</tr>
							{/foreach}
						</table>
	</form>
{else}
	<div class="alert alert-danger" role="alert">There are no NZBs in your cart.</div>
{/if}