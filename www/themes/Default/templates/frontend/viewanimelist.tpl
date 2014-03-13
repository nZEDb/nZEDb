{if $site->adbrowse}
	{$site->adbrowse}
{/if}
<h1>{$page->title}</h1>

<div style="float:right;">

	<form name="anidbsearch" action="" method="get">
		<label for="title">Search:</label>
		&nbsp;&nbsp;<input id="title" type="text" name="title" value="{$animetitle}" size="25" />
		&nbsp;&nbsp;
		<input type="submit" value="Go" />
	</form>
</div>

<p><b>Jump to</b>:
&nbsp;&nbsp;[ {if $animeletter == '0-9'}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/anime/0-9">0-9</a>{if $animeletter == '0-9'}</u></b>{/if}
{foreach $animerange as $range}
{if $range == $animeletter}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/anime/{$range}">{$range}</a>{if $range == $animeletter}</u></b>{/if}
{/foreach}]
</p>

{if $animelist|@count > 0}

<table style="width:100%;" class="data highlight icons" id="browsetable">
	{foreach $animelist as $aletter => $anime}
		<tr>
			<td style="padding-top:15px;" colspan="10"><a href="#top" class="top_link">Top</a><h2>{$animeletter}...</h2></td>
		</tr>
		<tr>
			<th width="40%">Name</th>
			<th width="10%">Type</th>
			<th width="35%">Categories</th>
			<th width="5%">Rating</th>
			<th>View</th>
		</tr>
		{foreach $anime as $a}
			<tr class="{cycle values=",alt"}">
				<td><a class="title" title="View anime" href="{$smarty.const.WWW_TOP}/anime/{$a.anidbid}">{$a.title|escape:"htmlall"}</a>{if {$a.startdate} != ''}<br />({$a.startdate|date_format} - {/if}{if $a.enddate != ''}{$a.enddate|date_format}{/if})</td>
				<td style="text-align: center;">{if {$a.type} != ''}{$a.type|escape:"htmlall"}{/if}</td>
				<td>{if {$a.categories} != ''}{$a.categories|escape:"htmlall"|replace:'|':', '}{/if}</td>
				<td style="text-align: center;">{if {$a.rating} != ''}{$a.rating}{/if}</td>
				<td><a title="View anime" href="{$smarty.const.WWW_TOP}/anime/{$a.anidbid}">Anime</a>&nbsp;&nbsp;{if $a.anidbid > 0}<a title="View at AniDB" target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$a.anidbid}">AniDB</a>{/if}</td>
			</tr>
		{/foreach}
	{/foreach}
</table>

{else}
<h2>No results</h2>
{/if}
