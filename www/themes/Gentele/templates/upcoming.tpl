<div class="header">
	<h2>Upcoming > <strong>Movies</strong></h2>
	<div class="breadcrumb-wrapper">
		<ol class="breadcrumb">
			<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
			/ Upcoming
		</ol>
	</div>
</div>
<div style="text-align: center;">
	<div class="btn-group">
		<a class="btn btn-sm btn-primary btn-transparent" href="{$smarty.const.WWW_TOP}/upcoming/1">Box Office</a>
		<a class="btn btn-sm btn-primary btn-transparent" href="{$smarty.const.WWW_TOP}/upcoming/2">In Theatre</a>
		<a class="btn btn-sm btn-primary btn-transparent" href="{$smarty.const.WWW_TOP}/upcoming/3">Opening</a>
		<a class="btn btn-sm btn-primary btn-transparent" href="{$smarty.const.WWW_TOP}/upcoming/4">Upcoming</a>
		<a class="btn btn-sm btn-primary btn-transparent" href="{$smarty.const.WWW_TOP}/upcoming/5">DVD Releases</a>
	</div>
</div>
<br/>
{$site->adbrowse}
{if isset($data) && $data|@count > 0}
	<table class="data table table-striped responsive-utilities jambo-table highlight icons " id="coverstable">
		{foreach $data as $result}
			<tr class="{cycle values=",alt"}">
				<td class="mid">
					<div class="movcover">
						<img class="shadow" src="{$result->posters->original}" width="120" border="0"
							 alt="{$result->title|escape:"htmlall"}"/>
						<div class="movextra">
							<a class="rndbtn badge badge-success" target="_blank"
							   href="{$site->dereferrer_link}{$result->links->alternate}"
							   title="View Rotten Tomatoes Details">Rotten</a>
							<a class="rndbtn badge badge-imdb" target="_blank"
							   href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result->alternate_ids->imdb}"
							   title="View Imdb Details">IMDB</a>
							{if !empty($cpurl) && !empty($cpapi)}
								<a
									id="imdb{$result->alternate_ids->imdb}"
									href="javascript:;"
									class="sendtocouch"
									title="Add to CouchPotato">
									<img src="{$smarty.const.WWW_TOP}/themes/shared/img/icons/couch.png">
								</a>
							{/if}
						</div>
					</div>
				</td>
				<td colspan="3" class="left">
					<h4>
						<a href="{$smarty.const.WWW_TOP}/movies?imdb={$result->alternate_ids->imdb}">{$result->title|escape:"htmlall"}</a>
						(<a class="title" title="{$result->year}"
							href="{$smarty.const.WWW_TOP}/movies?year={$result->year}">{$result->year}</a>) {if $result->ratings->critics_score > 0}{$result->ratings->critics_score}/100{/if}
					</h4>
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
							{if $smarty.foreach.cast.last}<br/><br/>{else},{/if}
						{/foreach}
					{else}
						<br/>
						<br/>
					{/if}
					{if isset($ourmovies) && $ourmovies[$result->alternate_ids->imdb] != ""}
						<a class="rndbtn btn btn-sm btn-success"
						   href="{$smarty.const.WWW_TOP}/movies?imdb={$result->alternate_ids->imdb}">Download</a>
					{else}
						<a {if !empty($userimdbs[$result->alternate_ids->imdb])}style="display:none;"{/if}
						   class="rndbtn btn btn-sm btn-info" href="{$smarty.const.WWW_TOP}/mymovies/add/{$result->alternate_ids->imdb}?from={$smarty.server.REQUEST_URI|escape:"url"}" rel="add" name="movies{$result->alternate_ids->imdb}" title="Add to My Movies">Add to My Movies</a>
					{/if}
					<br/>
				</td>
			</tr>
		{/foreach}
	</table>
{else}
	<div class="alert">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<strong>Sorry!</strong>
		No results found.
	</div>
{/if}
