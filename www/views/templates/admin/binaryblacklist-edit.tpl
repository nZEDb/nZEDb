
<h1>{$page->title}</h1>

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<form action="{$SCRIPT_NAME}?action=submit" method="POST">

<table class="input">


<tr>
	<td>Group:</td>
	<td>
		<input type="hidden" name="id" value="{$regex.ID}" />
		<input type="text" id="groupname" name="groupname" value="{$regex.groupname|escape:html}" />
		<div class="hint">The full name of a valid newsgroup. (Wildcard in the format 'alt.binaries.*')</div>		
	</td>
</tr>

<tr>
	<td>Regex:</td>
	<td>
		<textarea id="regex" name="regex" >{$regex.regex|escape:html}</textarea>
		<div class="hint">The regex to be applied. (Note: Beginning and Ending / are already included)</div>		
	</td>
</tr>

<tr>
	<td>Description:</td>
	<td>
		<textarea id="description" name="description" >{$regex.description|escape:html}</textarea>
		<div class="hint">A description for this regex</div>		
	</td>
</tr>

<tr>
	<td><label for="msgcol">Message Field</label>:</td>
	<td>
		{html_radios id="msgcol" name='msgcol' values=$msgcol_ids output=$msgcol_names selected=$regex.msgcol separator='<br />'}
		<div class="hint">Which field in the message to apply the black/white list to.</div>		
	</td>
</tr>

<tr>
	<td><label for="status">Active</label>:</td>
	<td>
		{html_radios id="status" name='status' values=$status_ids output=$status_names selected=$regex.status separator='<br />'}
		<div class="hint">Only active regexes are applied during the release process.</div>		
	</td>
</tr>

<tr>
	<td><label for="optype">Type</label>:</td>
	<td>
		{html_radios id="optype" name='optype' values=$optype_ids output=$optype_names selected=$regex.optype separator='<br />'}
		<div class="hint">Black will exclude all messages for a group which match this regex. White will include only those which match.</div>		
	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="submit" value="Save" />
	</td>
</tr>

</table>

</form>