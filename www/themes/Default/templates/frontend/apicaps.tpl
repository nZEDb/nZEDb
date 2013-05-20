<?xml version="1.0" encoding="UTF-8" ?> 
<caps>
	<server appversion="{$site->version}" version="0.1" title="{$site->title|escape}" strapline="{$site->strapline|escape}" email="{$site->email}" url="{$serverroot}" image="{if $site->style != "" && $site->style != "/"}{$serverroot}templates/{$site->style}/images/logo.png{else}{$serverroot}templates/Default/images/logo.png{/if}" />
	<limits max="100" default="100"/>

	<registration available="yes" open="{if $site->registerstatus == 0}yes{else}no{/if}" />
	
	<searching>
		<search available="yes"/>
		<tv-search available="yes"/>
		<movie-search available="yes"/>
		<audio-search available="no"/>
	</searching>
	
	<categories>
	{foreach from=$parentcatlist item=parentcat}
<category id="{$parentcat.ID}" name="{$parentcat.title|escape:html}"{if $parentcat.description != ""} description="{$parentcat.description|escape:html}"{/if}>
{foreach from=$parentcat.subcatlist item=subcat}
		<subcat id="{$subcat.ID}" name="{$subcat.title|escape:html}"{if $subcat.description != ""} description="{$subcat.description|escape:html}"{/if}/>
{/foreach}
	</category>
	{/foreach}
</categories>
</caps>
