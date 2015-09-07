<?xml version="1.0" encoding="UTF-8" ?>
<caps>
	<server appversion="{$site->version}" version="0.1" title="{$site->title|escape}"
			strapline="{$site->strapline|escape}" email="{$site->email}" url="{$serverroot}"
			image="{if $site->style != "" && $site->style != "/"}{$serverroot}theme/{$site->style}/images/banner.jpg{else}{$serverroot}images/banner.jpg{/if}"/>
	<limits max="100" default="100"/>
	<registration available="yes" open="{if $site->registerstatus == 0}yes{else}no{/if}"/>
	<searching>
		<search available="yes"/>
		<tv-search available="yes"/>
		<movie-search available="yes"/>
		<audio-search available="yes"/>
	</searching>
	<categories>
		{foreach from=$parentcatlist item=parentcat}
			<category id="{$parentcat.id}"
					  name="{$parentcat.title|escape:html}"{if $parentcat.description != ""} description="{$parentcat.description|escape:html}"{/if}>
				{foreach from=$parentcat.subcatlist item=subcat}
					<subcat id="{$subcat.id}"
							name="{$subcat.title|escape:html}"{if $subcat.description != ""} description="{$subcat.description|escape:html}"{/if}/>
				{/foreach}
			</category>
		{/foreach}
	</categories>
	<groups>
		{foreach from=$grps item=grp}
			<group id="{$grp.id}"
				   name="{$grp.name|escape:html}"{if $grp.description != ""} description="{$grp.description|escape:html}"{/if}
				   lastupdate="{$grp.last_updated|phpdate_format:"DATE_RSS"}"/>
		{/foreach}
	</groups>
	<genres>
		{foreach from=$genres item=genre}
			<genre id="{$genre.id}" categoryid="{$genre.type}" name="{$genre.title|escape:html}"/>
		{/foreach}
	</genres>
</caps>