 
<h1>{$page->title}</h1>

<form action="{$SCRIPT_NAME}?action=submit" method="POST">

<table class="input">

<tr>
	<td>Name:</td>
	<td>
		<input type="hidden" name="id" value="{$group.ID}" />
		<input id="name" class="long" name="name" type="text" value="{$group.name}" />
		<div class="hint">Changing the name to an invalid group will break things.</div>		
	</td>
</tr>

<tr>
	<td><label for="description">Description</label>:</td>
	<td>
		<textarea id="description" name="description">{$group.description}</textarea>
	</td>
</tr>

<tr>
	<td><label for="backfill_target">Backfill Days</label></td>
	<td>
		<input class="small" id="backfill_target" name="backfill_target" type="text" value="{$group.backfill_target}" />
		<div class="hint">Number of days to attempt to backfill this group.  Adjust as necessary.</div>
	</td>
</td>

<tr>
	<td><label for="minfilestoformrelease">Minimum Files <br/>To Form Release</label></td>
	<td>
		<input class="small" id="minfilestoformrelease" name="minfilestoformrelease" type="text" value="{$group.minfilestoformrelease}" />
		<div class="hint">The minimum number of files to make a release. i.e. if set to two, then releases which only contain one file will not be created. If left blank, will use the site wide setting.</div>
	</td>
</td>

<tr>
	<td><label for="minsizetoformrelease">Minimum File Size to Make a Release</label>:</td>
	<td>
		<input class="small" id="minsizetoformrelease" name="minsizetoformrelease" type="text" value="{$group.minsizetoformrelease}" />
		<div class="hint">The minimum total size in bytes to make a release. If left blank, will use the site wide setting.</div>
	</td>
</tr>

<tr>
	<td><label for="active">Active</label>:</td>
	<td>
		{html_radios id="active" name='active' values=$yesno_ids output=$yesno_names selected=$group.active separator='<br />'}
		<div class="hint">Inactive groups will not have headers downloaded for them.</div>		
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
