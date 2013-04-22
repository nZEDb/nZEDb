<h1>{$page->title}</h1>

<form action="{$SCRIPT_NAME}?action=submit" method="post">

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<fieldset>
<legend>Main Tmux Settings, HTML Layout, Tags</legend>
<table class="input">

<tr>
	<td><label for="RUNNING">Running</label>:</td>
	<td>
		{html_radios id="RUNNING" name='RUNNING' values=$truefalse_names output=$truefalse_names selected=$ftmux->RUNNING}
		<div class="hint">This is the shutdown, true/false on, it runs, off and no scripts will be RESTARTED, when all panes are DEAD, killall tmux if this is set to false, the script will run 1 loop and terminate</div>
	</td>
</tr>

<tr>
	<td><label for="BINARIES">Binaries</label>:</td>
	<td>
		{html_radios id="BINARIES" name='BINARIES' values=$truefalse_names output=$truefalse_names selected=$ftmux->BINARIES}
		<div class="hint">Choose to run update_binaries true/false</div>
	</td>
</tr>

<tr>
	<td><label for="BACKFILL">Backfill</label>:</td>
	<td>
		{html_radios id="BACKFILL" name='BACKFILL' values=$truefalse_names output=$truefalse_names selected=$ftmux->BACKFILL}
		<div class="hint">Choose to run backfill script true/false</div>
	</td>
</tr>

<tr>
	<td><label for="IMPORT">Import</label>:</td>
	<td>
		{html_radios id="IMPORT" name='text' values=$truefalse_names output=$truefalse_names selected=$ftmux->IMPORT}
		<div class="hint">Choose to run import nzb script true/false</div>
	</td>
</tr>

<tr>
	<td><label for="NZBS">Nzbs</label>:</td>
	<td>
		<input id="NZBS" class="long" type="text" value="{$ftmux->NZBS}" />
		<div class="hint">Set the path to the nzb dump you downloaded from torrents, this is the path to bulk files folder of nzbs this does not recurse through subfolders, unless you set NZB_THREADS to true</div>
	</td>
</tr>

<tr>
	<td><label for="RELEASES">Releases</label>:</td>
	<td>
		{html_radios id="RELEASES" name='RELEASES' values=$truefalse_names output=$truefalse_names selected=$ftmux->RELEASES}
		<div class="hint">Create releases, this is really only necessary to turn off when you only want to post process</div>
	</td>
</tr>

<tr>
	<td><label for="NFOS">Postprocess Nfos</label>:</td>
	<td>
		{html_radios id="NFOS" name='NFOS' values=$truefalse_names output=$truefalse_names selected=$ftmux->NFOS}
		<div class="hint">Choose to postprocess nfos true/false</div>
	</td>
</tr>

<tr>
	<td><label for="POST">Postprocess All Others</label>:</td>
	<td>
		{html_radios id="POST" name='POST' values=$truefalse_names output=$truefalse_names selected=$ftmux->POST}
		<div class="hint">Choose to postprocess movies, music, etc true/false</div>
	</td>
</tr>

<tr>
	<td style="width:160px;"><label for="NICENESS">Niceness</label>:</td>
	<td>
		<input id="NICENESS" type="text" value="{$ftmux->NICENESS}" />
		<div class="hint">these scripts set the 'nice'ness of each script, default is 19, the lowest, the highest is -20 anything between -1 and -20 require root/sudo to run</div>
	</td>
</tr>

<tr>
    <td style="width:160px;"><label for="MONITOR_DELAY">Monitor Loop Timer</label>:</td>
    <td>
        <input id="MONITOR_DELAY" class="text" type="text" value="{$ftmux->MONITOR_DELAY}" />
        <div class="hint">The time between query refreshes of monitor information, in seconds. The lower the number, the more often it queries the database for numbers.</div>
    </td>
</tr>

<tr>
    <td style="width:160px;"><label for="BACKFILL_DELAY">Backfill Delay</label>:</td>
    <td>
        <input id="BACKFILL_DELAY" class="text" type="text" value="{$ftmux->BACKFILL_DELAY}" />
        <div class="hint">If backfill is run on a new group before update_binaries has ran, it will result in error. This is the time, in seconds, between the script starting and the first time backfill runs. this is not a spleep timer between loops.</div>
    </td>
</tr>

<tr>
    <td style="width:160px;"><label for="DEFRAG_CACHE">Defrag Query Cache</label>:</td>
    <td>
        <input id="DEFRAG_CACHE" class="text" type="text" value="{$ftmux->DEFRAG_CACHE}" />
        <div class="hint">The mysql query cache gets frogmented over time. Enter the time, in seconds, to defrag the query cache. <br/ >cmd: FLUSH QUERY CACHE;</div>
    </td>
</tr>

<tr>
    <td style="width:160px;"><label for="COLLECTIONS">Collections Stop</label>:</td>
    <td>
        <input id="COLLECTIONS" class="text" type="text" value="{$ftmux->COLLECTIONS}" />
        <div class="hint">It is possible to overwhelm the collections table to the point that update_releases can not complete a loop in a timely manner. As a precaution, set this to keep update_binaries and backfill from running</div>
    </td>
</tr>

<tr>
    <td><label for="TMUX_SESSION">Tmux Session</label>:</td>
    <td>
        <input id="TMUX_SESSION" class="long" type="text" value="{$ftmux->TMUX_SESSION}" />
        <div class="hint">Enter the session name to be used by tmux, no spaces allowed in the name, this can be changed after scripts start if you are running multiple servers, you could put your hostname here</div>
    </td>
</tr>

</table>
</fieldset>

<input type="submit" value="Save Tmux Settings" />

</form>

