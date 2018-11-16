<div class="header">
	<h2>Browse > <strong>Groups</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Browse / Groups
		</ol>
	</div>
</div>
{$site->adbrowse}
{if $results|@count > 0}
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="col-lg-12 portlets">
					<div class="panel panel-default">
						<div class="panel-body pagination2">
							{$pager}
							<table class="data table table-striped responsive-utilities jambo-table Sortable"
								   style="table-layout: auto;" data-sort-order="desc">
								<thead>
								<tr>
									<th data-field="name" data-sortable="true">Name</th>
									<th>Description</th>
									<th data-field="updated" data-sortable="true">Updated</th>
								</tr>
								</thead>
								<tbody>
								{foreach $results as $result}
									{if $pagertotalitems > 0}
										<tr>
											<td>
												<a title="Browse releases from {$result.name|replace:"alt.binaries":"a.b"}"
												   href="{$smarty.const.WWW_TOP}/browse?g={$result.name}">{$result.name|replace:"alt.binaries":"a.b"}</a>
											</td>
											<td>{$result.description}</td>
											<td>{$result.last_updated|timeago} ago</td>
										</tr>
									{/if}
								{/foreach}
								</tbody>
							</table>
							{$pager}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}
