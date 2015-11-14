
<div class="header">
	<h2> Series > <strong>List</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li> / TV Series List
		</ol>
	</div>
</div>
<p>
	<b>Jump to</b>:
	&nbsp;[ {if $seriesletter == '0-9'}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/series/0-9">0-9</a>{if $seriesletter == '0-9'}</u></b>{/if}
	{foreach $seriesrange as $range}
	{if $range == $seriesletter}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/series/{$range}">{$range}</a>{if $range == $seriesletter}</u></b>{/if}
	{/foreach}]
</p>
<div class="btn-group">
	<a class="btn btn-default" href="{$smarty.const.WWW_TOP}/myshows" title="List my watched shows">My shows</a>
	<a class="btn btn-default" href="{$smarty.const.WWW_TOP}/myshows/browse" title="browse your shows">Find all my shows</a>
</div>
{$site->adbrowse}
{if $serieslist|@count > 0}
	<table class="data table table-condensed table-striped table-responsive table-hover icons" id="browsetable">
		{foreach $serieslist as $sletter => $series}
			<tr>
				<td colspan="10">
					<h2>{$sletter}...</h2>
					<form class="form pull-right" style="margin-top:-35px;">
						<form name="showsearch" class="navbar-form" action="" method="get">
							<div class="input-append">
								<input class="form-control" style="width: 150px;" id="title appendedInputButton" type="text" name="title" value="{$showname}" placeholder="Search here"/>
								<button type="submit" class="btn btn-default">GO</button>
							</div>
						</form>
					</form>
				</td>
			</tr>
			<tr>
				<th><div class="text-left">Name</div></th>
				<th style="width:80px"><div class="text-center">Network</div></th>
				<th style="width:80px"><div class="text-center">Country</div></th>
				<th style="width:120px"><div class="text-center">Option</div></th>
				<th style="width:180px"><div class="text-center">View</div></th>
			</tr>
			{foreach $series as $s}
				<tr>
					<td><a class="title" title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.id}">{$s.title|escape:"htmlall"}</a>{if $s.prevdate != ''}<br /><span class="label label-info">Last: {$s.previnfo|escape:"htmlall"} aired {$s.prevdate|date_format}</span>{/if}</td>
					<td>{$s.countries_id|escape:"htmlall"}</td>
					<td class="mid">
						{if $s.userseriesid != null}
							<a href="{$smarty.const.WWW_TOP}/myshows/edit/{$s.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows btn btn-sm btn-warning" rel="edit" name="series{$s.id}" title="Edit">Edit</a>
							<br />
							<a href="{$smarty.const.WWW_TOP}/myshows/delete/{$s.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows btn btn-sm btn-danger" rel="remove" name="series{$s.id}" title="Remove from My Shows">Remove</a>
						{else}
							<a href="{$smarty.const.WWW_TOP}/myshows/add/{$s.id}?from={$smarty.server.REQUEST_URI|escape:"url"}" class="myshows btn btn-sm btn-primary" rel="add" name="series{$s.id}" title="Add to My Shows">Add</a>
						{/if}
					</td>
					<td class="mid">
						<a title="View series" href="{$smarty.const.WWW_TOP}/series/{$s.id}">Series</a><br />
							{if $s.id > 0}
									{if $s.tvdb > 0}
										<a title="View at TVDB" target="_blank" href="{$site->dereferrer_link}http://thetvdb.com/?tab=series&id={$s.tvdb}">TVDB</a>
									{/if}
									{if $s.tvmaze > 0}
										<a title="View at TVMaze" target="_blank" href="{$site->dereferrer_link}http://tvmaze.com/shows/{$s.tvmaze}">TVMaze</a>
										{/if}
									{if $s.trakt > 0}
										<a title="View at Trakt" target="_blank" href="{$site->dereferrer_link}http://www.trakt.tv/shows/{$s.trakt}">Trakt</a>
										{/if}
									{if $s.tvrage > 0}
										<a title="View at TVRage" target="_blank" href="{$site->dereferrer_link}http://www.tvrage.com/shows/id-{$s.tvrage}">TVRage</a>
										{/if}
									{if $s.tmdb > 0}
										<a title="View at TheMovieDB" target="_blank" href="{$site->dereferrer_link}https://www.themoviedb.org/shows/{$s.tmdb}">TMDB</a>
									{/if}
								<a title="RSS Feed for {$s.title|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/rss?show={$s.id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}"><i class="fa fa-rss"></i></a>
							{/if}
							<a title="RSS Feed for {$s.title|escape:"htmlall"}" href="{$smarty.const.WWW_TOP}/rss?show={$s.id}&amp;dl=1&amp;i={$userdata.id}&amp;r={$userdata.rsstoken}"><i class="fa fa-rss"></i></a>
					</td>
				</tr>
			{/foreach}
		{/foreach}
	</table>
{else}
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Hmm!</strong> No result on that search term.
	</div>
{/if}
