
{if not $modal}
<h1>{$page->title}</h1>
<h2>For <a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}">{$rel.searchname|escape:'htmlall'}</a></h2>
{/if}

<pre id="nfo">{$nfo.nfoUTF|magicurl:$site->dereferrer_link}</pre>
