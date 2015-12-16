<?xml version="1.0" encoding="UTF-8" ?>
<caps>
	<server appversion="{$site->version}" version="0.1" title="{$site->title|escape}" strapline="{$site->strapline|escape}" email="{$site->email}" url="{$serverroot}" image="{$serverroot}themes_shared/images/logo.png" />
	<limits max="100" default="100"/>

	<registration available="yes" open="{if $site->registerstatus == 0}yes{else}no{/if}" />

	<searching>
		<search available="yes" supportedParams="q,group"/>
		<tv-search available="yes" supportedParams="q,rid,tvdbid,vid,traktid,tvmazeid,imdbid,tmdbid,season,ep"/>
		<movie-search available="yes" supportedParams="q,imdbid"/>
		<audio-search available="no" supportedParams=""/>
	</searching>

	<categories>
		{foreach from=$parentcatlist item=parentcat}
			<category id="{$parentcat.id}" name="{$parentcat.title|escape:html}"{if $parentcat.description != ""} description="{$parentcat.description|escape:html}"{/if}>
				{foreach from=$parentcat.subcatlist item=subcat}
					<subcat id="{$subcat.id}" name="{$subcat.title|escape:html}"{if $subcat.description != ""} description="{$subcat.description|escape:html}"{/if}/>
				{/foreach}
			</category>
		{/foreach}
	</categories>
</caps>