{if $site->adbrowse}
	{$site->adbrowse}
{/if}
<h1>{$page->title}</h1>
<p>
	<a href="{$smarty.const.WWW_TOP}/upcoming/1">Box Office</a> |
	<a href="{$smarty.const.WWW_TOP}/upcoming/2">In Theatre</a> |
	<a href="{$smarty.const.WWW_TOP}/upcoming/3">Opening</a> |
	<a href="{$smarty.const.WWW_TOP}/upcoming/4">Upcoming</a> |
	<a href="{$smarty.const.WWW_TOP}/upcoming/5">DVD Releases</a>
</p>
{if isset($nodata)}
	{$nodata}
{elseif $data|@count > 0}
	<table style="width:100%;" class="data highlight icons" id="coverstable">
		<tr>
			<th></th>
			<th>Name</th>
		</tr>
		{foreach $data as $result}
			<tr class="{cycle values=",alt"}">
				<td class="mid">
					<div class="movcover">
						<img class="shadow" src="{replace_url($result->posters->original)}" width="120"
						border="0" alt="{$result->title|escape:"htmlall"}"/>
						<div class="movextra">
							<a class="rndbtnsml" target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten Tomatoes</a>
							<a class="rndbtnsml" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result->alternate_ids->imdb}/" name="imdb{$result->alternate_ids->imdb}" title="View imdb page">Imdb</a>
							<a class="rndbtnsml" target="_blank" href="{$site->dereferrer_link}http://trakt.tv/search/imdb/tt{$result->alternate_ids->imdb}/" name="trakt{$result->alternate_ids->imdb}" title="View trakt page">Trakt</a>
							{if $cpurl != '' && $cpapi != ''}
								<a class="rndbtnsml sendtocouch" target="blackhole" href="javascript:;" rel="{$cpurl}/api/{$cpapi}/movie.add/?identifier=tt{$result->alternate_ids->imdb}&title={$result->title}" name="CP{$result->alternate_ids->imdb}" title="Add to CouchPotato">CouchPotato</a>
							{/if}
						</div>
					</div>
				</td>
				<td colspan="3" class="left">
					<h2><a href="{$smarty.const.WWW_TOP}/movies?title={$result->title}&year={$result->year}">{$result->title|escape:"htmlall"}</a> (<a class="title" title="{$result->year}" href="{$smarty.const.WWW_TOP}/movies?year={$result->year}">{$result->year}</a>)</h2>
					{if $result->ratings->critics_score > 0}<br /><b>Critics Rated: </b>{$result->ratings->critics_score}/100{/if}
					{if $result->ratings->audience_score > 0}<br /><b>Audience Rated: </b>{$result->ratings->audience_score}/100{/if}
					{if $result->mpaa_rating}<br /><b>Rated:</b> {$result->mpaa_rating}{/if}
					{if $result->release_dates->theater}
						<br /><b>Released to Theaters:</b> {$result->release_dates->theater}
					{/if}
					{if $result->release_dates->dvd}
						<br /><b>Released to DVD:</b> {$result->release_dates->dvd}
					{/if}
					<br />
					{if $result->synopsis == ""}No synopsis. Check <a target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten Tomatoes</a> for more information.{else}<b>Synopsis:</b><br /> {$result->synopsis}{/if}
					{if $result->critics_consensus != ""}<br /><br /><b>Critics:</b><br /> {$result->critics_consensus}{/if}
					{if $result->abridged_cast|@count > 0}
						<br /><br />
						<b>Starring:</b>
						{foreach from=$result->abridged_cast item=cast name=cast}
							<a href="{$smarty.const.WWW_TOP}/movies?actors={$cast->name|escape:"htmlall"}" title="Search for movies starring {$cast->name|escape:"htmlall"}">{$cast->name|escape:"htmlall"}</a>
							{if $smarty.foreach.cast.last}<br/><br/>{else},{/if}
						{/foreach}
					{else}
						<br/><br/>
					{/if}
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<h2>No results</h2>
{/if}