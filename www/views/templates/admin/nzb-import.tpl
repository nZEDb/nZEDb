 
<h1>{$page->title}</h1>

<p>
Import nzbs from a folder or via the browser into the system. Specify the full file path to a folder containing nzbs.
<br />
Importing will add the release to your database, compress the NZB and store it in the nzbfiles folder.
</p>
<ul>
<li>If you are importing a large number of nzb files, run this script from the command line and pass in the folder path as the first argument.</li>
<li>If you are running this script from the command line you can pass "true" (no quotes) as the second argument to use the nzb filename as the release name.</li>
<li>Groups contained in the nzbs should be added to the site before the import is run.</li>
<li>If you re-import the same NZB you will get 2 identical releases.</li>
<li>If imported sucessfully the nzb will be deleted.</li>
</ul>

<fieldset>
<legend>Import From Directory</legend>

<form action="{$SCRIPT_NAME}#results" method="POST">

<table class="input">

<tr>
	<td width="100"><label for="folder">Folder</label>:</td>
	<td>
		<input id="folder" class="long" name="folder" type="text" value="" />
		<div class="hint">Windows file paths should be specified with forward slashes e.g. c:/temp/</div>
	</td>
</tr>

<tr>
	<td><label for="usefilename">Use Filename</label>:</td>
	<td>
		<input type="checkbox" name="usefilename" />
		<div class="hint">Use the nzb's filename as the release name. Else the name inside the NZB will be used.</div>
	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="submit" value="Import" />&nbsp;&nbsp;&nbsp;<b>Once imported the nzb will be deleted.</b>
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
	<td width="100"><label for="uploadedfiles[]">File</label>:</td>
	<td>
		<input name="uploadedfiles[]" type="file" class="multi accept-nzb"/>
		<div class="hint">Select one or more .nzb files.</div>
	</td>
</tr>

<tr>
	<td><label for="usefilename">Use Filename</label>:</td>
	<td>
		<input type="checkbox" name="usefilename" />
		<div class="hint">Use the nzb's filename as the release name. Else the name inside the NZB will be used.</div>
	</td>
</tr>


<tr>
	<td></td>
	<td>
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
