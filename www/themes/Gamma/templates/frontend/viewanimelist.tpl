<h2>{$page->title}</h2>
<p>
<b>Jump to</b>:

&nbsp;[ {if $animeletter == '0-9'}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/anime/0-9">0-9</a>{if $animeletter == '0-9'}</u></b>{/if}
{foreach $animerange as $range}
{if $range == $animeletter}<b><u>{/if}<a href="{$smarty.const.WWW_TOP}/anime/{$range}">{$range}</a>{if $range == $animeletter}</u></b>{/if}
{/foreach}]
</p>
<form class="form pull-right" style="margin-top:-35px;">
	<form name="anidbsearch" class="navbar-form" action="" method="get">
		<div class="input-append">
			<input class="input-medium" id="title appendedInputButton" type="text" name="title" value="{$animetitle}" class="span2" placeholder="Search anime title"/>
			<button type="submit" class="btn">GO</button>
		</div>
	</form>
</form
<center>
</center>
{$site->adbrowse}

{if $animelist|@count > 0}

<table style="width:100%;" class="data highlight icons table table-striped" id="browsetable">
		<tr>
			<th width="40%">Name</th>
			<th width="10%" style="text-align: center;">Type</th>
			<th width="35%" style="text-align: center;">Categories</th>
			<th width="5%" style="text-align: center;">Rating</th>
			<th style="text-align: center;">View</th>
		</tr>
	{foreach $animelist as $aletter => $anime}
		{foreach $anime as $a}
			<tr class="{cycle values=",alt"}">
				<td width="40%"><a align="" class="title" title="View anime" href="{$smarty.const.WWW_TOP}/anime/{$a.anidbid}"><img class="shadow" width="35%" src="{$smarty.const.WWW_TOP}/covers/anime/{$a.anidbid}.jpg" />  {$a.title|escape:"htmlall"}</a></td>
				<td style="text-align: center;">{if {$a.type} != ''}{$a.type|escape:"htmlall"}{/if}</td>
				<td width>{if {$a.categories} != ''}{$a.categories|escape:"htmlall"|replace:'|':', '}{/if}{if {$a.startdate} != ''}<br><br><b>Air date: {$a.startdate|date_format} - {/if}{if $a.enddate != ''}{$a.enddate|date_format}</b>{/if}</td>
				<td style="text-align: center;">{if {$a.rating} != ''}{$a.rating}{/if}</td>
				<td style="text-align: center;"><a title="View at AniDB" target="_blank" href="{$site->dereferrer_link}http://anidb.net/perl-bin/animedb.pl?show=anime&aid={$a.anidbid}">AniDB</a></td>
			</tr>
		{/foreach}
	{/foreach}
</table>

{else}
<h2>No results</h2>
{/if}
