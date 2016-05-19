<h1>{$page->title}</h1>
{$site->adbrowse}
{if $data|@count > 0}
<table style="width:100%;" class="data highlight icons" id="coverstable">
		<tr>
			<th></th>
			<th>&nbsp;</th>
		</tr>
		{foreach $data as $result}
		<tr class="{cycle values=",alt"}">
			<td class="mid">
				<div class="movcover">
					<div style="text-align: center;">
					<img class="shadow img img-polaroid" src="{$result->posters->original}" width="120" border="0"
						 alt="{$result->title|escape:"htmlall"}"/>
					</div>
					<div class="movextra">
						<div style="text-align: center;">
						<a class="rndbtn badge badge-success" target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten</a>
						</div>
						{if !empty($result->alternate_ids)}
							<a
								class="rndbtn badge badge-imdb"
								target="_blank"
								href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result->alternate_ids->imdb}"
								title="View Imdb Details">Imdb
							</a>
							<a
								target="_blank"
								href="{$site->dereferrer_link}http://trakt.tv/search/imdb/tt{$result->alternate_ids->imdb}/"
								name="trakt{$result->alternate_ids->imdb}"
								title="View trakt page"><img
								src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/trakt.png">
							</a>
							{if !empty($cpurl) && !empty($cpapi)}
								<a
									id="imdb{$result->alternate_ids->imdb}"
									href="javascript:;"
									class="sendtocouch"
									title="Add to CouchPotato">
									<img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/couch.png">
								</a>
							{/if}
						{/if}
					</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h4><a href="{$smarty.const.WWW_TOP}/movies?imdb={$result->alternate_ids->imdb}">{$result->title|escape:"htmlall"}</a> (<a class="title" title="{$result->year}" href="{$smarty.const.WWW_TOP}/movies?year={$result->year}">{$result->year}</a>) {if $result->ratings->critics_score > 0}{$result->ratings->critics_score}/100{/if}</h4>
				{if $result->synopsis == ""}No synopsis. Check <a target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}" title="View Rotten Tomatoes Details">Rotten Tomatoes</a> for more information.{else}{$result->synopsis}{/if}
				{if $result->abridged_cast|@count > 0}
					<br /><br />
					<b>Starring:</b>
					{foreach $result->abridged_cast as $cast name="cast"}
						<a href="{$smarty.const.WWW_TOP}/movies?actors={$cast->name|escape:"htmlall"}" title="Search for movies starring {$cast->name|escape:"htmlall"}">{$cast->name|escape:"htmlall"}</a>
						{if $smarty.foreach.cast.last}<br/><br/>{else},{/if}
					{/foreach}
				{else}
					<br/><br/>
				{/if}
				<br/>
			</td>
		</tr>
		{/foreach}
</table>
{else}
<h2>No results</h2>
{/if}
