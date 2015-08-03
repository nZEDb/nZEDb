{if $data|@count > 0}
	<table class="data table table-striped table-condensed table-responsive table-hover" id="coverstable">
		<tr>
			<th></th>
			<th>Name</th>
		</tr>
		{foreach $data as $result}
			{if $result['imdb_id'] != ""}
				<tr>
					<td>
						<div>
							<img class="shadow"
								 src="{if $result['cover'] ==""}{$serverroot}themes/omicron/images/nocover.png{else}{$result['cover']}{/if}"
								 width="120" border="0" alt="{$result['title']|escape:"htmlall"}"/>
							<div>
								<a class="label label-default" target="_blank"
								   href="{$site->dereferrer_link}http://www.imdb.com/title/tt{$result['imdb_id']}"
								   title="View on IMDB">IMDB</a>
							</div>
						</div>
					</td>
					<td colspan="3">
						<h2>
							<a href="{$smarty.const.WWW_TOP}/movies?title={$result['title']}">{$result['title']|escape:"htmlall"}</a>
							{if $result['year'] != ""}(
								<a class="title"
								   href="{$smarty.const.WWW_TOP}/movies?year={$result['year']}">{$result['year']}</a>
								){/if}
							{if $result['rating'] > 0}{$result['rating']}/10{/if}
						</h2>
						{$result['plot']}
						<br/>
						{if $ourmovies[$result['imdb_id']] != ""}<a class="btn btn-sm btn-default"
																	href="{$smarty.const.WWW_TOP}/movies?imdb={$result['imdb_id']}">
								Download <i class="fa fa-download"></i></a>{/if}
						<a style="display:{if $userimdbs[$result['imdb_id']] == ""}inline{else}none;{/if}"
						   onclick="mymovie_add('{$result['imdb_id']}', this);return false;"
						   class="btn btn-sm btn-success" href="#">Add To My Movies</a>
						<a style="display:{if $userimdbs[$result['imdb_id']] != ""}inline{else}none;{/if}"
						   onclick="mymovie_del('{$result['imdb_id']}', this);return false;" href="#"
						   class="btn btn-sm btn-danger">Remove From My Movies</a>
					</td>
				</tr>
			{/if}
		{/foreach}
	</table>
{else}
	<h2>No results</h2>
{/if}