<h1>{$page->title}</h1>
<p>
	Delete releases with their covers/nzbs/etc based on these criteria (all fields are optional, leave them blank if you want).<br />
	With "like" you can specify multiple words separated by spaces.<br />
	It's faster and you have more options using the script in /misc/testing/release/delete_releases.php<br />
	<strong>This can take a long time, don't reload the page! Consider running this in another tab or running the above script.</strong>
</p>
<form action="{$SCRIPT_NAME}?action=submit" method="POST">
	{if $error != ''}
		<div class="error" style="width:578px;">{$error}</div>
	{/if}
	{if $done != ''}
		<div style="color:#2BC645;font-size:16px;font-weight:bold;">{$done}</div>
	{/if}
	<fieldset style="width:630px;">
		<legend>Name</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Name:</td>
				<td>
					<input type="hidden" name="id" value="1" />
					<input id="name" class="long" name="name" type="text" value="{$release.name}" />
					<div class="hint">The usenet subject.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="nametypesel">Name search type:</label></td>
				<td>
					<input type="hidden" name="nametypesel" value={$release.nametypesel} />
					{html_radios id="nametypesel" name='nametypesel' values=$type1_ids output=$type1_names selected=$release.nametypesel}
					<div class="hint">Pick the type of search for the name, equals is strict, like is loose.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Search name</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Search Name:</td>
				<td>
					<input id="searchname" class="long" name="searchname" type="text" value="{$release.searchname}" />
					<div class="hint">The search name.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="snametypesel">Search Name search type:</label></td>
				<td>
					<input type="hidden" name="snametypesel" value={$release.snametypesel} />
					{html_radios id="snametypesel" name='snametypesel' values=$type1_ids output=$type1_names selected=$release.snametypesel}
					<div class="hint">Pick the type of search for the search name, equals is strict, like is loose.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Poster name</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Poster Name:</td>
				<td>
					<input id="fromname" class="long" name="fromname" type="text" value="{$release.fromname}" />
					<div class="hint">The poster name (the person who posted it to usenet).</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="fnametypesel">Poster Name search type:</label></td>
				<td>
					<input type="hidden" name="fnametypesel" value={$release.fnametypesel} />
					{html_radios id="fnametypesel" name='fnametypesel' values=$type1_ids output=$type1_names selected=$release.fnametypesel}
					<div class="hint">Pick the type of search for the poster name, equals is strict, like is loose.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Group name</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Group Name:</td>
				<td>
					<input id="groupname" class="long" name="groupname" type="text" value="{$release.groupname}" />
					<div class="hint">The group name (alt.binaries.example).</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="gnametypesel">Group Name search type:</label></td>
				<td>
					<input type="hidden" name="gnametypesel" value={$release.gnametypesel} />
					{html_radios id="gnametypesel" name='gnametypesel' values=$type1_ids output=$type1_names selected=$release.gnametypesel}
					<div class="hint">Pick the type of search for the group name, equals is strict, like is loose.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Size</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Size:</td>
				<td>
					<input id="relsize" class="long" name="relsize" type="text" value="{$release.relsize}" />
					<div class="hint">The max or min size in bytes.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="sizetypesel">Size search type:</label></td>
				<td>
					<input type="hidden" name="sizetypesel" value={$release.sizetypesel} />
					{html_radios id="sizetypesel" name='sizetypesel' values=$type2_ids output=$type2_names selected=$release.sizetypesel}
					<div class="hint">Bigger means releases bigger than specified value, inverse for smaller, equals means exactly this size.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Added date</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Added date:</td>
				<td>
					<input id="adddate" class="long" name="adddate" type="text" value="{$release.adddate}" />
					<div class="hint">(hours) Date added to our DB.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="adatetypesel">Added date type:</label></td>
				<td>
					<input type="hidden" name="adatetypesel" value={$release.adatetypesel} />
					{html_radios id="adatetypesel" name='adatetypesel' values=$type1_ids output=$type3_names selected=$release.adatetypesel}
					<div class="hint">Bigger means older than x hours, Smaller newer than x hours.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Posted date</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Posted date:</td>
				<td>
					<input id="postdate" class="long" name="postdate" type="text" value="{$release.postdate}" />
					<div class="hint">(hours) Date added to usenet.</div>
				</td>
			</tr>
			<tr>
				<td style="width:180px;"><label for="pdatetypesel">Added date type:</label></td>
				<td>
					<input type="hidden" name="pdatetypesel" value={$release.pdatetypesel} />
					{html_radios id="pdatetypesel" name='pdatetypesel' values=$type1_ids output=$type3_names selected=$release.pdatetypesel}
					<div class="hint">Bigger means older than x hours, Smaller newer than x hours.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Completion</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Completion %:</td>
				<td>
					<input id="completion" class="long" name="completion" type="text" value="{$release.completion}" />
					<div class="hint">Releases smaller than this completion % will be deleted. Can be a number between 1 and 99, everything else is ignored.</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<fieldset style="width:630px;">
		<legend>Guid</legend>
		<table class="input">
			<tr>
				<td style="width:180px;">Guid:</td>
				<td>
					<input id="relguid" class="long" name="relguid" type="text" value="{$release.relguid}" />
					<div class="hint">The release guid (for a single release).</div>
				</td>
			</tr>
		</table>
	</fieldset>
	<input type="submit" value="Submit" />
</form>