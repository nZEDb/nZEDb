<h1>{$page->title}</h1>
{if $error != ''}
	<div class="error">{$error}</div>
{/if}
<form action="{$SCRIPT_NAME}?action=submit" method="POST">
	<table class="input">
		<tr>
			<td><label for="group_regex">Group:</label></td>
			<td>
				<input type="hidden" name="id" value="{$regex.id}" />
				<input type="text" id="group_regex" name="group_regex" value="{$regex.group_regex|escape:html}" />
				<div class="hint">
					Regex to match against a group or multiple groups.<br />
					Delimiters are already added, and PCRE_CASELESS is added after for case insensitivity.
					An example of matching a single group: alt\.binaries\.example<br />
					An example of matching multiple groups: alt\.binaries.*
				</div>
			</td>
		</tr>
		<tr>
			<td><label for="regex">Regex:</label></td>
			<td>
				<textarea id="regex" name="regex" >{$regex.regex|escape:html}</textarea>
				<div class="hint">
					Regex to use when categorizing releases.<br />
					The regex delimiters are not added, you MUST add them. See <a href="http://php.net/manual/en/regexp.reference.delimiters.php">this</a> page.<br />
					To make the regex case insensitive, add i after the last delimiter.<br />
				</div>
			</td>
		</tr>
		<tr>
			<td><label for="description">Description:</label></td>
			<td>
				<textarea id="description" name="description" >{$regex.description|escape:html}</textarea>
				<div class="hint">
					Description for this regex.<br />
					You can include an example usenet subject this regex would match on.
				</div>
			</td>
		</tr>
		<tr>
			<td><label for="ordinal">Ordinal:</label></td>
			<td>
				<input class="ordinal" id="ordinal" name="ordinal" type="text" value="{$regex.ordinal}" />
				<div class="hint">
					The order to run this regex in.<br />
					Must be a number, 0 or higher.<br />
					If multiple regex have the same ordinal, MySQL will randomly sort them.
				</div>
			</td>
		</tr>
		<tr>
			<td><label for="status">Active:</label></td>
			<td>
				{html_radios id="status" name='status' values=$status_ids output=$status_names selected=$regex.status separator='<br />'}
				<div class="hint">Only active regex are used during the collection matching process.</div>
			</td>
		</tr>
		<tr>
			<td><label for="category_id">Category:</label></td>
			<td>
				{html_options id="category_id" name='category_id' values=$category_ids output=$category_names selected=$regex.category_id}
				<div class="hint">Select a category which releases matched to this regex will go into.</div>
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