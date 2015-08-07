{if not $modal}
	<h1>{$page->title}</h1>
	<h3>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}">{$rel.searchname|escape:'htmlall'}</a></h3>
{/if}

<div id="xxxbackdrop"><img src="{$smarty.const.WWW_TOP}/covers/xxx/{if $movie.backdrop == 1}{$movie.id}{else}no{/if}-backdrop.jpg" alt=""></div>

<div id="xxxinfo">

	<h1>{$movie.title|ss}</h1>
	<h3>{if $movie.cover == 1}<img src="{$smarty.const.WWW_TOP}/covers/xxx/{$movie.id}-cover.jpg" class="cover" alt="{$movie.title|ss}" align="left">{/if}
		{if $movie.tagline != ''}<b>{$movie.tagline|ss}</b>{/if}</h3>

	{if $movie.plot != ''}
		<h3>{$movie.plot|ss}</h3>
	{/if}

	<h4>
		{if $movie.director != ''}Director: {$movie.director}<br>{/if}
		{if $movie.genre != ''}Genre: {$movie.genre|ss}{/if}
	</h4>

	{if $movie.actors != ''}<h4>Starring:<br>{$movie.actors}</h4>{/if}

</div>
