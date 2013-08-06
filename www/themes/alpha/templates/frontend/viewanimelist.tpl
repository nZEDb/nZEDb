<div class="row">
<p style="font-size:16px"><b>Jump to</b>:
&nbsp;&nbsp;[ {if $animeletter == '0-9'}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/anime/0-9">0-9</a>{if $animeletter == '0-9'}</u></b>{/if}&nbsp;
{foreach $animerange as $range}
&nbsp;{if $range == $animeletter}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/anime/{$range}">{$range}</a>{if $range == $animeletter}</u></b>{/if}&nbsp;
{/foreach} ]
</p>
</div>

<div class="row" style="float:right;">
<form name="anidbsearch" action="" method="get" id="custom-search-form" class="form-search form-horizontal pull-right">
<div class="input-append span12">
<input type="text" class="search-query" placeholder="Search" id="title" name="title" value="{$animetitle}">
<button type="submit" value="Go" class="btn"><i class="icon-search"></i></button>
</div>
</form>
</div>


{$site->adbrowse}	

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
				<td><a class="title" title="View anime" href="{$smarty.const.WWW_TOP}/anime/{$a.anidbID}">{$a.title|escape:"htmlall"}</a>{if {$a.startdate} != ''}<br />({$a.startdate|date_format} - {/if}{if $a.enddate != ''}{$a.enddate|date_format}{/if})</td>
				<td style="text-align: center;">{if {$a.type} != ''}{$a.type|escape:"htmlall"}{/if}</td>
				<td>{if {$a.categories} != ''}{$a.categories|escape:"htmlall"|replace:'|':', '}{/if}</td>
				<td style="text-align: center;">{if {$a.rating} != ''}{$a.rating}{/if}</td>
				<td><a title="View anime" href="{$smarty.const.WWW_TOP}/anime/{$a.anidbID}">Anime</a>&nbsp;&nbsp;{if $a.anidbID > 0}<a title="View at AniDB" target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$a.anidbID}">AniDB</a>{/if}</td>
			</tr>
		{/foreach}
	{/foreach}
</table>

{else}
<h2>No results</h2>
{/if}
