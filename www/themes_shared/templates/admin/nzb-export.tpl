<h1>{$page->title}</h1>
<p>
	Export nzbs from the system into a folder, sub folders will be created by group. Specify the full file path to a folder.
	<br/>
	<strong>If you are exporting a large number of nzb files, run the script misc/testing/nzb-export.php from the command line.</strong>
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
				<div class="hint">Posted to usenet inbetween a date range specified in the format dd/mm/yyyy (optional, you can blank these.)</div>
			</td>
		</tr>
		<tr>
			<td><label for="group">Group:</label></td>
			<td>
				{html_options id="group" name='group' options=$grouplist selected=$group}
				<div class="hint">Posted to this newsgroup (optional, leave to All groups to export all)</div>
			</td>
		</tr>
		<tr>
			<td><label for="gzip">Gzip:</label></td>
			<td>
				{html_options id="gzip" name='gzip' options=$gziplist selected=$gzip}
				<div class="hint">True: The compressed NZB files will be copied to the new folder with new names, this is faster and takes roughly 10x less disk space. (recommended)
				<br />False: The compressed NZB files will be decompressed, the contents will then be written uncompressed to a file in the new folder.</div>
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