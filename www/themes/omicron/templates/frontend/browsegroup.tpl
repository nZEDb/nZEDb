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
		<div class="box col-md-12">
			<div class="box-content">
				<div class="row">
					<div class="col-lg-12 portlets">
						<div class="panel panel-default">
							<div class="panel-body pagination2">
								<table class="data table table-condensed table-striped sortable table-responsive table-hover"
									   style="table-layout: auto;" data-sort-order="desc">
									<thead>
									<tr>
										<th data-field="name" data-sortable="true">Name</th>
										<th>Description</th>
										<th data-field="updated" data-sortable="true">Updated</th>
										<th data-firstsort="desc">Releases</th>
									</tr>
									</thead>
									<tbody>
									{foreach from=$results item=result}
										{if $result.num_releases > 0}
											<tr>
												<td>
													<a title="Browse releases from {$result.name|replace:"alt.binaries":"a.b"}"
													   href="{$smarty.const.WWW_TOP}/browse?g={$result.name}">{$result.name|replace:"alt.binaries":"a.b"}</a>
												</td>
												<td>{$result.description}</td>
												<td>{$result.last_updated|timeago} ago</td>
												<td>{$result.num_releases}</td>
											</tr>
										{/if}
									{/foreach}
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}