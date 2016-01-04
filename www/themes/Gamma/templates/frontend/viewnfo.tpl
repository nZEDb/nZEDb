{if not $modal}
<div class="page-header">
	<h1>{$page->title}</h1>
</div>
<h2>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}/{$rel.searchname|escape:'seourl'}">{$rel.searchname|escape:'htmlall'}</a></h2>
{/if}

<pre id="nfo">{$nfo.nfoUTF|magicurl:$site->dereferrer_link}</pre>
