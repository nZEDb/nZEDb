<div id="musicinfo">

<img src="{$smarty.const.WWW_TOP}/covers/music/{if $music.cover == 1}{$music.ID}{else}no-cover{/if}.jpg" class="cover" alt="{$movie.title|ss}"/>

<h1>{$music.title} {if $music.year != ""}({$music.year}){/if}</h1>

{if $music.artist != ""}<h3>Artist: {$music.artist}</h3>{/if}

{if $music.genres != ""}<h3>Genre: {$music.genres}</h3>{/if}

{if $music.publisher != ""}<h3>Publisher: {$music.publisher}</h3>{/if}

{if $music.releasedate != ""}<h3>Released: {$music.releasedate|date_format}</h3>{/if}

{if $music.tracks != ""}
<h3>Track Listing:</h3>
<ol class="tracklist">
	{assign var="tracksplits" value="|"|explode:$music.tracks}
	{foreach from=$tracksplits item=tracksplit}
	<li>{$tracksplit|trim}</li>
	{/foreach}		
</ol>
{/if}

{if $music.review != ""}
<h3>Review: </h3>
<p>{$music.review}</p>
{/if}

</div>
