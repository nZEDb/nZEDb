 
<h1>{$page->title}</h1>

<form enctype="multipart/form-data" action="{$SCRIPT_NAME}?action=submit" method="POST">

<input type="hidden" name="from" value="{$smarty.get.from}" />

<table class="input">

<tr>
	<td><label for="rageid">Rage Id:</label></td>
	<td>
		<input id="rageid" class="short" name="rageid" type="text" value="{$rage.rageid}" />
		<input type="hidden" name="id" value="{$rage.id}" />
		<div class="hint">The numeric TVRage Id.</div>
	</td>
</tr>

<tr>
	<td><label for="releasetitle">Show Name:</label></td>
	<td>
		<input id="releasetitle" class="long" name="releasetitle" type="text" value="{$rage.releasetitle|escape:'htmlall'}" />
		<div class="hint">The title of the TV show.</div>
	</td>
</tr>

<tr>
	<td><label for="description">Description:</label></td>
	<td>
		<textarea id="description" name="description">{$rage.description|escape:'htmlall'}</textarea>
	</td>
</tr>

<tr>
	<td><label for="genre">Show Genres:</label></td>
	<td>
		<input id="genre" class="long" name="genre" type="text" value="{$rage.genre|escape:'htmlall'}" />
		<div class="hint">The genres for the TV show. Separated by pipes ( | )</div>
	</td>
</tr>

<tr>
	<td><label for="country">Show Country:</label></td>
	<td>
		<input id="country" name="country" type="text" value="{$rage.country|escape:'htmlall'}" maxlength="2" />
		<div class="hint">The country for the TV show.</div>
	</td>
</tr>

<tr>
	<td><label for="imagedata">Series Image:</label></td>
	<td>
		{if $rage.imgdata != ""}
			<img style="max-width:200px; display:block;" src="{$smarty.const.WWW_TOP}/../getimage?type=tvrage&id={$rage.id}">
		{/if}
		<input type="file" id="imagedata" name="imagedata">
		<div class="hint">Shown in the TV series view page.</div>
	</td>
</tr>


<tr>
	<td></td>
	<td>
		<input class="rndbtn" type="submit" value="Save" />
	</td>
</tr>

</table>

</form>
