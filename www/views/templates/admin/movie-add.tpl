 
<h1>{$page->title}</h1>

<form enctype="multipart/form-data" action="{$SCRIPT_NAME}" method="post">


<table class="input">

<tr>
	<td></td>
	<td>Enter the full 7 digit IMDB id into the box below and click Add.</td>
</tr>

<tr>
	<td><label for="title">IMDB ID</label>:</td>
	<td>
		<input id="id" class="long" name="id" type="text" value="" />
	</td>
</tr>

<tr>
	<td></td>
	<td>
		<input type="submit" value="Add" />
	</td>
</tr>

</table>

</form>