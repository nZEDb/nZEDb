{if !isset($modal)}
	<div class="header">
		<h2>View > <strong>NFO</strong></h2>
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ NFO
			</ol>
		</div>
	</div>
	<h4>
		<a href="{$smarty.const.WWW_TOP}/details/{$rel.guid}">{$rel.searchname|escape:'htmlall'}</a>
	</h4>
{/if}
<pre id="nfo">{$nfo.nfoUTF|magicurl:$site->dereferrer_link}</pre>