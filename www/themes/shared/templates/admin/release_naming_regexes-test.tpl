<h1>{$page->title}</h1>
<p>This page is used for testing regex for getting release names from usenet subject.
	<br />Maximum releases to display will limit the amount of results displayed on the page. 0 for no limit.
	<br />Query limit will limit the amount of releases to select from MySQL (setting this high can be very slow). 0 for no limit.
</p>

<form name="search" action="" method="post" style="margin-bottom:5px;">
	<label for="group" style="padding-right:1px">Group:</label>
	<input id="group" type="text" name="group" value="{$group|htmlentities}" size="20" /><br />
	<label for="regex" style="padding-right:1px">Regex:</label>
	<input id="regex" type="text" name="regex" value="{$regex|htmlentities}" size="100" /><br/>
	<label for="showlimit" style="padding-right:7px">Maximum releases to display:</label>
	<input id="showlimit" type="text" name="showlimit" value="{$showlimit}" size="8" /><br/>
	<label for="querylimit" style="padding-right:7px">Query limit:</label>
	<input id="querylimit" type="text" name="querylimit" value="{$querylimit}" size="8" /><br/>
	<input type="submit" value="Test" />
</form>
{if $data}
	<table style="margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th>Release ID</th>
			<th>Usenet Subject</th>
			<th>Old Search Name</th>
			<th>New Search Name</th>
		</tr>
		{foreach from=$data key=id item=names}
			<tr id="row-{$id}" class="{cycle values=",alt"}">
				<td>{$id}</td>
				<td>{$names.subject}</td>
				<td>{$names.old_name}</td>
				<td>{$names.new_name}</td>
			</tr>
		{/foreach}
	</table>
{/if}