 
<h1>{$page->title}</h1>

<form action="{$SCRIPT_NAME}?action=submit" method="POST">

<table class="input">

<tr>
	<td><label for="title">Title</label>:</td>
	<td>
		<input type="hidden" name="id" value="{$menu.ID}" />
		<input id="title" class="long" name="title" type="text" value="{$menu.title|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="href">Href</label>:</td>
	<td>
		<input id="href" class="long" name="href" type="text" value="{$menu.href|escape:'htmlall'}" />
		<div class="hint">Use full http:// path for external URLs, otherwise use no prefix.</div>
	</td>
</tr>

<tr>
	<td><label for="tooltip">Tooltip</label>:</td>
	<td>
		<input id="tooltip" class="long" name="tooltip" type="text" value="{$menu.tooltip|escape:'htmlall'}" />
	</td>
</tr>

<tr>
	<td><label for="menueval">Evaluate</label>:</td>
	<td>
		<input id="menueval" class="long" name="menueval" type="text" value="{$menu.menueval|escape:'htmlall'}" />
		<div class="hint">Smarty expression returning -1 if the menu item should be disabled.</div>
	</td>
</tr>

<tr>
	<td><label for="role">Role</label>:</td>
	<td>
		{html_radios id="role" name='role' values=$role_ids output=$role_names selected=$menu.role separator='<br />'}
	</td>
</tr>

<tr>
	<td><label for="ordinal">Ordinal</label>:</td>
	<td>
		<input id="ordinal" class="short" name="ordinal" type="text" value="{$menu.ordinal}" />
	</td>
</tr>

<tr>
	<td><label for="newwindow">New Window</label>:</td>
	<td>
		{html_radios id="newwindow" name='newwindow' values=$yesno_ids output=$yesno_names selected=$menu.newwindow separator='<br />'}
		<div class="hint">Whether the menu item should open in a new window.</div>
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