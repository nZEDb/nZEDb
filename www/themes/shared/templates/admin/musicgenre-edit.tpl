<h1>{$page->title}</h1>
<form action="{$SCRIPT_NAME}?action=submit" method="POST">
	<table class="input">
		<tr>
			<td>Title:</td>
			<td>
				<input type="hidden" name="id" value="{$genre.id}" />
				{$genre.title}
			</td>
		</tr>
		<tr>
			<td><label for="disabled">Disabled:</label></td>
			<td>
				{html_radios id="disabled" name='disabled' values=$status_ids output=$status_names selected=$genre.disabled separator='<br />'}
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