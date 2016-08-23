<div class="header">
	<h2><strong>My Download Basket</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Download Basket
		</ol>
	</div>
</div>
<div class="alert alert-info" role="alert">
	<strong>RSS Feed</strong> <br/>
	Your download basket can also be accessed via an <a
			href="{$smarty.const.WWW_TOP}/rss?t=-2&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}&amp;del=1">RSS
		feed</a>. Some NZB downloaders can read this feed and automatically start downloading.
</div>
{if $results|@count > 0}
	<form id="nzb_multi_operations_form" action="get">
		<div class="nzb_multi_operations">
			<small>With Selected:</small>
			<div class="btn-group">
				<input type="button" class="nzb_multi_operations_cartdelete btn btn-sm btn-danger" value="Delete"/>
				{if isset($sabintegrated) && $sabintegrated !=""}
					<input type="button" class="nzb_multi_operations_cartsab btn btn-sm btn-info"
						   value="Send to queue"/>
				{/if}
				<input type="button" class="nzb_multi_operations_download_cart btn btn-sm btn-success" value="Download"/>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12 portlets">
				<div class="panel panel-default">
					<div class="panel-body pagination2">
						<table class="data table table-striped responsive-utilities jambo-table bulk-action">
							<thead>
							<tr class="headings">
								<th><input id="check-all" type="checkbox" class="flat-all"/> Select All</th>
								<th class="column-title" style="display: table-cell;">Name</th>
								<th class="column-title" style="display: table-cell;">Added</th>
								<th class="column-title" style="display: table-cell;">Action</th>
							</tr>
							</thead>
							<tbody>
							{foreach $results as $result}
								<tr class="{cycle values=",alt"}">
									<td class="a-center ">
										<input id="chk{$result.guid|substr:0:7}" type="checkbox" class="flat"
											   value="{$result.guid}"/>
									</td>
									<td>
										<a title="View details"
										   href="{$smarty.const.WWW_TOP}/details/{$result.guid}">{$result.searchname|escape:"htmlall"|wordwrap:75:"\n":true}</a>
									</td>
									<td class="less"
										title="Added on {$result.createddate}">{$result.createddate|date_format}</td>
									<td><a title="Delete from your cart" href="?delete={$result.guid}"
										   class="btn btn-danger btn-sm" style="padding-bottom:2px;">Delete</a></td>
								</tr>
							{/foreach}
							</tbody>
						</table>
	</form>
{else}
	<div class="alert alert-danger" role="alert">There are no NZBs in your download basket.</div>
{/if}
