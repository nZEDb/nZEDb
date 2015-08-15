<div class="header">
	<h2>Anime > <strong>List</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Anime List
		</ol>
	</div>
</div>
{$site->adbrowse}
<p><b>Jump to</b>:
	&nbsp;&nbsp;[ {if $animeletter == '0-9'}<b><u>{/if}<a
					href="{$smarty.const.WWW_TOP}/anime/0-9">0-9</a>{if $animeletter == '0-9'}</u></b>{/if}
	{foreach $animerange as $range}
	{if $range == $animeletter}<b><u>{/if}<a
	href="{$smarty.const.WWW_TOP}/anime/{$range}">{$range}</a>{if $range == $animeletter}</u></b>{/if}
	{/foreach}]
</p>
{$site->adbrowse}
{if $animelist|@count > 0}
	<table style="width:100%;" class="data table table-condensed table-striped table-responsive table-hover" id="browsetable">
		{foreach $animelist as $aletter => $anime}
			<tr>
				<td colspan="10">
					<h2>{$aletter}...</h2>
					<form name="anidbsearch" class="form pull-right" action="" method="get" style="margin-top:-35px;">
						<label for="title">Search:</label>
						<input class="form-control" style="width: 150px;" id="title appendedInputButton" type="text"
							   name="title" value="{$animetitle}" placeholder="Search here"/>
						<button type="submit" class="btn btn-default">GO</button>
					</form>
				</td>
			</tr>
			<tr>
				<th width="35%">Name</th>
				<th>Type</th>
				<th width="35%">Categories</th>
				<th>Rating</th>
				<th>View</th>
			</tr>
			{foreach $anime as $a}
				<tr>
					<td><a class="title" title="View anime"
						   href="{$smarty.const.WWW_TOP}/anime/{$a.anidbid}">{$a.title|escape:"htmlall"}</a>{if {$a.startdate} != ''}
						<br/><span class="label label-info">({$a.startdate|date_format}
							- {/if}{if $a.enddate != ''}{$a.enddate|date_format}{/if})</span></td>
					<td>{if {$a.type} != ''}{$a.type|escape:"htmlall"}{/if}</td>
					<td>{if {$a.categories} != ''}{$a.categories|escape:"htmlall"|replace:'|':', '}{/if}</td>
					<td>{if {$a.rating} != ''}{$a.rating}{/if}</td>
					<td><a title="View at AniDB" target="_blank" class="label label-primary"
						   href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$a.anidbid}">AniDB</a>
					</td>
				</tr>
			{/foreach}
		{/foreach}
	</table>
{else}
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Hmm!</strong> No results for this query.
	</div>
{/if}