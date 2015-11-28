<div id="musicinfo">
	<img src="{$smarty.const.WWW_TOP}/covers/console/{if $console.cover == 1}{$console.id}{else}no-cover{/if}.jpg"
		 class="cover" alt="{$console.title|ss}"/>
	<h1>{$console.title} {if $console.year != ""}({$console.year}){/if}</h1>
	{if $console.genres != ""}<h3>Genre: {$console.genres}</h3>{/if}
	{if $console.publisher != ""}<h3>Publisher: {$console.publisher}</h3>{/if}
	{if $console.releasedate != ""}<h3>Released: {$console.releasedate|date_format}</h3>{/if}
	{if $console.review != ""}
		<h3>Review: </h3>
		<p>{$console.review}</p>
	{/if}
</div>