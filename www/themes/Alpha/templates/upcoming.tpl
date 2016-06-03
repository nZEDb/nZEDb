{include file='elements/ads.tpl' ad=$site->adbrowse}

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
	<table class="table table-condensed data icons" id="coverstable">
		<thead>
		<tr>
			<th></th>
			<th>Name</th>
		</tr>
		</thead>
		<tbody>
		{foreach $data as $result}
			<tr>
				<td style="width:150px;padding:10px;text-align:center;">
					<div class="movcover">
						<img class="shadow img-thumbnail" src="{$result->posters->original}" width="120"
						border="0" alt="{$result->title|escape:"htmlall"}"/>

						<div class="movextra">
							{if $result->ratings->critics_score > 60}
								<a
										target="_blank"
										href="{$site->dereferrer_link}{$result->links->alternate}"
										title="View Rotten Tomatoes Details"><img
											src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/fresh.png"></a>
							{else}
								<a
										target="_blank"
										href="{$site->dereferrer_link}{$result->links->alternate}"
										title="View Rotten Tomatoes Details"><img
											src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/rotten.png"></a>
							{/if}
							{if !empty($result->alternate_ids)}
								<a
									target="_blank"
									href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result->alternate_ids->imdb}/"
									name="imdb{$result->alternate_ids->imdb}"
									title="View imdb page"><img
									src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/imdb.png">
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
				<td colspan="3">
					<h2>
						<a href="{$smarty.const.WWW_TOP}/movies?title={$result->title}&year={$result->year}">{$result->title|escape:"htmlall"}</a>
						(<a class="title" title="{$result->year}"
						    href="{$smarty.const.WWW_TOP}/movies?year={$result->year}">{$result->year}</a>) {if $result->ratings->critics_score > 0}{$result->ratings->critics_score}/100{/if}
					</h2>
					{if $result->synopsis == ""}No synopsis. Check
						<a target="_blank" href="{$site->dereferrer_link}{$result->links->alternate}"
						   title="View Rotten Tomatoes Details">Rotten Tomatoes</a>
						for more information.{else}{$result->synopsis}{/if}
					{if $result->abridged_cast|@count > 0}
						<br/>
						<br/>
						<b>Starring:</b>
						{foreach $result->abridged_cast as $cast name="cast"}
							<a href="{$smarty.const.WWW_TOP}/movies?actors={$cast->name|escape:"htmlall"}"
							   title="Search for movies starring {$cast->name|escape:"htmlall"}">{$cast->name|escape:"htmlall"}</a>
							{if $smarty.foreach.cast.last}<br/>{else},{/if}
						{/foreach}
					{else}
						<br/>
					{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{else}
	<h2>No results</h2>
{/if}
