<div class="header">
	{assign var="catsplit" value=">"|explode:$catname}
	<h2>My > <strong>TV Shows</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ My TV Shows
		</ol>
	</div>
</div>
<center>
	<div class="btn-group">
		<a class="btn btn-sm btn-default" title="View available TV series" href="{$smarty.const.WWW_TOP}/series">View
			all series</a>
		<a class="btn btn-sm btn-default" title="View a list of all releases in your shows"
		   href="{$smarty.const.WWW_TOP}/myshows/browse">View releases for My Shows</a>
		<a class="btn btn-sm btn-default" title="All releases in your shows as an RSS feed"
		   href="{$smarty.const.WWW_TOP}/rss?t=-3&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}">RSS Feed for
			My Shows <i class="fa fa-rss"></i></a>
	</div>
</center>
<br>
{if $shows|@count > 0}
<div class="row">
	<div class="col-lg-12 portlets">
		<div class="panel panel-default">
			<div class="panel-body pagination2">
				<table class="table table-striped table-condensed">
					<tr>
						<th>Name</th>
						<th width="80">Category</th>
						<th width="110">Added</th>
						<th width="130" class="mid">Options</th>
					</tr>
					{foreach from=$shows item=show}
						<tr>
							<td>
								<a title="View details"
								   href="{$smarty.const.WWW_TOP}/series/{$show.rageid}{if $show.categoryid != ''}?t={$show.categoryid|replace:"|":","}{/if}">{$show.releasetitle|escape:"htmlall"|wordwrap:75:"\n":true}</a>
							</td>
							<td>
								<span class="label label-default">{if $show.categoryNames != ''}{$show.categoryNames|escape:"htmlall"}{else}All{/if}</span>
							</td>
							<td title="Added on {$show.createddate}">{$show.createddate|date_format}</td>
							<td>
								<div class="btn-group">
									<a class="btn btn-xs btn-warning"
									   href="{$smarty.const.WWW_TOP}/myshows/edit/{$show.rageid}" class="myshows"
									   rel="edit" name="series{$show.rageid}" title="Edit Categories">Edit</a>
									<a class="btn btn-xs btn-danger confirm_action"
									   href="{$smarty.const.WWW_TOP}/myshows/delete/{$show.rageid}" class="myshows"
									   rel="remove" name="series{$show.rageid}" title="Remove from My Shows">Remove</a>
								</div>
							</td>
						</tr>
					{/foreach}
				</table>
				{else}
				<div class="alert alert-danger">
					<strong>Sorry!</strong> No shows bookmarked yet!
				</div>
				{/if}
			</div>
		</div>
	</div>
</div>