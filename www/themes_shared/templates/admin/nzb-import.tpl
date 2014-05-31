<h1>{$page->title}</h1>
<p>
	Import NZB's from a folder or via the browser into the system. Specify the full file path to a folder containing NZB's.
	<br />
	Importing will add the release to your database, compress the NZB and store it in the nzbfiles/ folder.
</p>
<ul>
	<li>If you are importing a large number of NZB files, run the nzb-import.php script in misc/testing/ from the command line and pass in the folder path as the first argument.</li>
	<li>If you are running the script in misc/testing/ from the command line you can pass "true" (no quotes) as the second argument to use the NZB filename as the release name.</li>
	<li>Groups contained in the NZB's should be added to the site before the import is run.</li>
	<li>If you re-import the same NZB it will not be added a second time.</li>
	<li>If imported sucessfully the NZB will be deleted.</li>
</ul>
<fieldset>
	<legend>Import From Directory</legend>
	<form action="{$SCRIPT_NAME}#results" method="POST">
		<table class="input">
			<tr>
				<td style="width:100px;"><label for="folder">Folder:</label></td>
				<td>
					<input id="folder" class="long" name="folder" type="text" value="" />
					<div class="hint">Windows file paths should be specified with forward slashes e.g. c:/temp/</div>
				</td>
			</tr>
			<tr>
				<td><label for="usefilename">Use Filename:</label></td>
				<td>
					<input type="checkbox" name="usefilename" />
					<div class="hint">Use the NZB's filename as the release name. Else the name inside the NZB will be used.</div>
				</td>
			</tr>
			<tr>
				<td><label for="deleteNZB">Delete NZBs:</label></td>
				<td>
					<input type="checkbox" name="deleteNZB" />
					<div class="hint">Delete the NZB when we have successfully imported it?</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input type="submit" value="Import" />
				</td>
			</tr>
		</table>
	</form>
</fieldset>
<fieldset>
	<legend>Import From Browser</legend>
	<form action="{$SCRIPT_NAME}#results" method="POST" enctype="multipart/form-data">
		<table class="input">
			<tr>
				<td style="width:100px;"><label for="uploadedfiles[]">File:</label></td>
				<td>
					<input name="uploadedfiles[]" type="file" class="multi accept-nzb"/>
					<div class="hint">Select one or more .nzb files.</div>
				</td>
			</tr>
			<tr>
				<td></td>
				<td>
					<b>These NZBs will not be deleted once imported.</b><br />
					<input type="submit" value="Import" />
				</td>
			</tr>
		</table>
</fieldset>
{if $output != ""}
	<div>
		<a id="results"></a>
		<h1>Import Results</h1>
		{$output}
	</div>
{/if}