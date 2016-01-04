<h1>{$page->title}</h1>
<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="post">
	<input type="hidden" name="id" value="{$xxxmovie.id}" />
	<table class="input">
		<tr>
			<td><label for="xxxinfoid">XXXInfo ID:</label></td>
			<td id="xxxinfoid">{$xxxmovie.id}</td>
		</tr>
		<tr>
			<td><label for="title">Title:</label></td>
			<td>
				<input id="title" class="long" name="title" type="text" value="{$xxxmovie.title|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="tagline">Tagline:</label></td>
			<td>
				<input id="tagline" class="long" name="tagline" type="text" value="{$xxxmovie.tagline|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="plot">Plot:</label></td>
			<td>
				<textarea id="plot" name="plot">{$xxxmovie.plot|escape:'htmlall'}</textarea>
			</td>
		</tr>
		<tr>
			<td><label for="genre">Genre:</label></td>
			<td>
				<select multiple="multiple" id="xxxgenre_list" name="genre[]">
					{foreach from=$genres item=gen}
						<option value="{$gen.id}" {if in_array($gen.id, $xxxmovie.genre)}selected="selected"{/if}>
							{$gen.title|escape:'htmlall'}
						</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="director">Director:</label></td>
			<td>
				<input id="director" class="long" name="director" type="text" value="{$xxxmovie.director|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="actors">Actors:</label></td>
			<td>
				<textarea id="actors" name="actors">{$xxxmovie.actors|escape:'htmlall'}</textarea>
			</td>
		</tr>
		<tr>
		<td><label for="trailerurl">Trailer Url:</label></td>
			<td>
				<input id="trailerurl" style="width:800px;" name="trailerurl" type="text" value="{$xxxmovie.trailers|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="directurl">XXX Movie Url:</label></td>
			<td>
				<input id="directurl" style="width:800px;" name="directurl" type="text" value="{$xxxmovie.directurl|escape:'htmlall'}" />
			</td>
		</tr>
		<tr>
			<td><label for="cover">Cover Image:</label></td>
			<td>
				<input type="file" id="cover" name="cover" />
				{if $xxxmovie.cover == 1}
					<img style="max-width:200px; display:block;" src="{$smarty.const.WWW_TOP}/../covers/xxx/{$xxxmovie.id}-cover.jpg" alt="" />
				{/if}
			</td>
		</tr>
		<tr>
			<td><label for="backdrop">Backdrop Image:</label></td>
			<td>
				<input type="file" name="backdrop" />
				{if $xxxmovie.backdrop == 1}
					<img style="max-width:200px; display:block;" src="{$smarty.const.WWW_TOP}/../covers/xxx/{$xxxmovie.id}-backdrop.jpg" alt="" />
				{/if}
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Save" />
			</td>
		</tr>
	</table>
	<input type="hidden" name="productinfo" value="{$xxxmovie.productinfo|escape:'htmlall'}">
	<input type="hidden" name="extras" value="{$xxxmovie.extras|escape:'htmlall'}">
	<input type="hidden" name="classused" value="{$xxxmovie.classused|escape:'htmlall'}">
</form>
