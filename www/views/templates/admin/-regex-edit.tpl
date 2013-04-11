 
<h1>{$page->title}</h1>

{if $regex.ID > 0 && $regex.ID < 100000 && $site->reqidurl != ""}
	<div class="error">Warning: Editing system regex, these changes will be overwritten next update releases.</div>
{/if}

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<form action="{$SCRIPT_NAME}?action=submit" method="POST">

<table class="input">


<tr>
	<td>Group:</td>
	<td>
		<input type="hidden" name="id" value="{$regex.ID}" />
		<input id="groupname" name="groupname" value="{$regex.groupname|escape:html}" />
		<div class="hint">The full name of a valid newsgroup. Leave blank to apply regex to all newsgroups.</div>		
	</td>
</tr>

<tr>
	<td>Regex:</td>
	<td>
		<textarea id="regex" name="regex" >{$regex.regex|escape:html}</textarea>
		<div class="hint">The regex to be applied.<br />
		Regex requires at least 1 named capturing group in the form of (?P&lt;name&gt;) to work.<br />
		If the subjects contains the number of parts (ie [1/10]) then it is wise to also use (?P&lt;parts&gt;) to match the parts.
		</div>		
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
	<td><label for="category">Category</label>:</td>
	<td>
		{html_options id="category" name=category options=$catlist selected=$regex.categoryID}
		<div class="hint">If this regex indicates the release category then supply it here. If left blank the standard method of determining the category will apply.</div>	
	</td>
</tr>

<tr>
	<td>Ordinal:</td>
	<td>
		<input id="ordinal" class="short" name="ordinal" value="{$regex.ordinal|escape:html}" />
		<div class="hint">The zero-based order in which the regex should be applied.</div>		
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
	<td></td>
	<td>
		<input type="submit" value="Save" />
	</td>
</tr>

</table>

</form>