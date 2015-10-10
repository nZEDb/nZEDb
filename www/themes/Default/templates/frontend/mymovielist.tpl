{if $data|@count > 0}

<table style="width:100%;" class="data highlight icons" id="coverstable">
		<tr>
			<th></th>
			<th>Name</th>
		</tr>

		{foreach $data as $result}
		{if $result['imdb_id'] != ""}
		{assign var=imdbid value=$result['imdb_id']|replace:"tt":""}
		<tr class="{cycle values=",alt"}">
			<td class="mid">
			
				<div class="movcover">
				
					<img class="shadow" src="{if $result['cover'] ==""}{$smarty.const.WWW_TOP}/covers/movies/no-cover.jpg{else}{$result['cover']}{/if}" width="120" border="0" alt="{$result['title']|escape:"htmlall"}" />
					<div class="movextra">
						{if $ourmovies[$imdbid] != ""}
						<a href="#" name="name{$imdbid}" title="View movie info" class="rndbtn modal_imdb" rel="movie" >Cover</a>
						{/if}
						<a class="rndbtn" target="_blank" href="{$site->dereferrer_link}http://www.imdb.com/title/{$result['imdb_id']}" title="View IMDB">IMDB</a>
					</div>
				</div>
			</td>
			<td colspan="3" class="left">
				<h2>
				<a href="{$smarty.const.WWW_TOP}/movies?title={$result['title']}">{$result['title']|escape:"htmlall"}</a> 
				{if $result['year'] != ""}(<a class="title" href="{$smarty.const.WWW_TOP}/movies?year={$result['year']}">{$result['year']}</a>){/if}
				{if $result['rating'] > 0}{$result['rating']}/10{/if}
				</h2>				
				
				{$result['plot']}

				<br/><br/>
				{if $ourmovies[$imdbid] != ""}
					<a class="rndbtn" href="{$smarty.const.WWW_TOP}/movies?imdb={$imdbid}">Download</a>
				{/if}
				<a style="display:{if $userimdbs[$imdbid] == ""}inline{else}none;{/if}" onclick="mymovie_add('{$imdbid}', this);return false;" class="rndbtn" href="#">Add To My Movies</a>
				<a style="display:{if $userimdbs[$imdbid] != ""}inline{else}none;{/if}" onclick="mymovie_del('{$imdbid}', this);return false;" href="#" class="rndbtn">Remove From My Movies</a>
			</td>
		</tr>
		{/if}
		{/foreach}
</table>

{else}
<h2>No results</h2>
{/if}
