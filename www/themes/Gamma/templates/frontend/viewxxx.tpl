{if not $modal} 
<h1>{$page->title}</h1>
<h2>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}/{$rel.searchname|escape:'seourl'}">{$rel.searchname|escape:'htmlall'}</a></h2>
{/if}

{if $movie.backdrop == 1}
		<div id="backdrop"><img src="{$smarty.const.WWW_TOP}/covers/xxx/{$movie.id}-backdrop.jpg" alt=""/></div>
	{else}
		{if $guid != ''}
			<div id="backdrop"><img src="{$smarty.const.WWW_TOP}/covers/preview/{$guid}_thumb.jpg" alt=""/></div>
		{/if}
{/if}

<div id="movieinfo">

<h1>{$movie.title|ss} {if $movie.year != ''}({$movie.year}){/if}</h1>
<h2>{if $movie.cover == 1}<img src="{$smarty.const.WWW_TOP}/covers/xxx/{$movie.id}-cover.jpg" class="cover" alt="{$movie.title|ss}" align="left" />{/if}
{if $movie.tagline != ''}<b>{$movie.tagline|ss}</b>{/if}</h2>

{if $movie.plot != ''}
	<h2>{$movie.plot|ss}</h2>
{/if}

<h3>
	{if $movie.rating != ''}Rating: {$movie.rating}/10<br />{/if}
	{if $movie.director != ''}Director: {$movie.director}<br />{/if}
	{if $movie.genre != ''}Genre: {$movie.genre|ss}{/if}
</h3>

{if $movie.actors != ''}<h3>Starring:<br />{$movie.actors}</h3>{/if}

</div>