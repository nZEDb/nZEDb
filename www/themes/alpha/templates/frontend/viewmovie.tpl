{if not $modal}
	<h1>{$page->title}</h1>
	<h3>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}/{$rel.searchname|escape:'htmlall'}">{$rel.searchname|escape:'htmlall'}</a></h3>
{/if}

<div id="backdrop"><img src="{$smarty.const.WWW_TOP}/covers/movies/{if $movie.backdrop == 1}{$movie.imdbid}{else}no{/if}-backdrop.jpg" alt=""></div>

<div id="movieinfo">

	<h1>{$movie.title|ss} {if $movie.year != ''}({$movie.year}){/if}</h1>
	<h3>{if $movie.cover == 1}<img src="{$smarty.const.WWW_TOP}/covers/movies/{$movie.imdbid}-cover.jpg" class="cover" alt="{$movie.title|ss}" align="left" width="180" border="0">{/if}
		{if $movie.tagline != ''}<b>{$movie.tagline|ss}</b>{/if}</h3>

	{if $movie.plot != ''}
		<h3>{$movie.plot|ss}</h3>
	{/if}

	<h4>
		{if $movie.rating != ''}Rating: {$movie.rating}/10<br>{/if}
		{if $movie.director != ''}Director: {$movie.director}<br>{/if}
		{if $movie.genre != ''}Genre: {$movie.genre|ss}{/if}
	</h4>

	{if $movie.actors != ''}<h4>Starring:<br>{$movie.actors}</h4>{/if}

</div>