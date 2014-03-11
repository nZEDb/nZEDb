<h1>{$page->title}</h1>

<p>
	Export nzbs from the system into a folder. Specify the full file path to a folder.
	<br/>
	If you are exporting a large number of nzb files, run this script from the command line and pass in the folder path as the first argument. e.g. php scriptname outputpath from(optional) to(optional) groupid(optional)<br/>
<span style="font-family:courier;display:block;padding:5px 0 15px 0;">
	php admin/nzb-export.php /path/to/export/into 01/01/2008 01/01/2010 123
</span>

</p>


<form action="{$SCRIPT_NAME}" method="POST">

	<table class="input">

		<tr>
			<td><label for="folder">Folder:</label></td>
			<td>
				<input id="folder" class="long" name="folder" type="text" value="{$folder}" />
				<div class="hint">Windows file paths should be specified with forward slashes e.g. c:/temp/</div>
			</td>
		</tr>

		<tr>
			<td><label for="postfrom">Posted Between:</label></td>
			<td>
				<input id="postfrom" class="date" name="postfrom" type="text" value="{$fromdate}" />
				<label for="postto"> and </label>
				<input id="postto" class="date" name="postto" type="text" value="{$todate}" />
				<div class="hint">Posted to usenet inbetween a date range specified in the format dd/mm/yyyy</div>
			</td>
		</tr>

		<tr>
			<td><label for="group">Group:</label></td>
			<td>
				{html_options id="group" name='group' options=$grouplist selected=$group}
				<div class="hint">Posted to this newsgroup</div>
			</td>
		</tr>

		<tr>
			<td></td>
			<td>
				<input type="submit" value="Export" />
			</td>
		</tr>

	</table>

</form>

<div>
	{$output}
</div>