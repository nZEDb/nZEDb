{if not $modal}
	<h1>{$page->title}</h1>
	<h2>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}/{$rel.searchname|escape:'seourl'}">{$rel.searchname|escape:'htmlall'}</a></h2>
{/if}
{if $movie.backdrop == 1}<div id="backdrop"><img src="{$smarty.const.WWW_TOP}/covers/xxx/{$movie.id}-backdrop.jpg" alt=""/></div>{/if}
<div id="movieinfo">
	<h1>{$movie.title|ss}</h1>
	<h2>{if $movie.cover == 1}<img src="{$smarty.const.WWW_TOP}/covers/xxx/{$movie.id}-cover.jpg" class="cover" alt="{$movie.title|ss}" align="left" />{/if}
		{if isset($movie.tagline) && $movie.tagline != ''}<b>{$movie.tagline|ss}</b>{/if}</h2>
	{if isset($movie.plot) && $movie.plot != ''}<h2>{$movie.plot|ss}</h2>{/if}
	<h3>{if isset($movie.director) && $movie.director != ''}Director: {$movie.director}<br />{/if}
		{if isset($movie.genre) && $movie.genre != ''}Genre: {$movie.genre|ss}{/if}
	</h3>
	{if $movie.actors != ''}<h3>Starring:<br />{$movie.actors}</h3>{/if}
</div>
