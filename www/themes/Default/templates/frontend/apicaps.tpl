<?xml version="1.0" encoding="UTF-8" ?>
<caps>
    <server appversion="{$serverconf.version}" version="0.1" title="{$serverconf.title|escape}" strapline="{$serverconf.strapline|escape}" email="{$serverconf.email}" url="{$serverconf.url}" image="{$serverconf.image}" />

    <limits max="{$limit.max}" default="{$limit.default}"/>

    <registration available="{$registration.available}" open="{$registration.open}" />

    <searching>
        <search available="{searchcap.search}"/>
        <tv-search available="{searchcap.tv-search}"/>
        <movie-search available="{searchcap.movie-search}"/>
        <audio-search available="{searchcap.audio-search}"/>
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