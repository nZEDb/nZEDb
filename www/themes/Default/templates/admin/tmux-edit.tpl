<h1>{$page->title}</h1>

<form action="{$SCRIPT_NAME}?action=submit" method="post">

{if $error != ''}
	<div class="error">{$error}</div>
{/if}

<fieldset>
	<legend>Monitor</legend>
		<table class="input">
			<tr>
				<td><label for="RUNNING">Tmux Scripts Running</label>:</td>
				<td>
					{html_radios id="RUNNING" name='RUNNING' values=$truefalse_names output=$truefalse_names selected=$ftmux->RUNNING}
					<div class="hint">This is the shutdown, true/on, it runs, false/off and all scripts are terminated. This will start/stop all panes without terminating the monitor pane.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="MONITOR_DELAY">Monitor Loop Timer</label>:</td>
				<td>
					<input id="MONITOR_DELAY" name="MONITOR_DELAY" class="text" type="text" value="{$ftmux->MONITOR_DELAY}" />
					<div class="hint">The time between query refreshes of monitor information, in seconds. The lower the number, the more often it queries the database for numbers.</div>
				</td>
			</tr>

			<tr>
				<td><label for="TMUX_SESSION">Tmux Session</label>:</td>
				<td>
					<input id="TMUX_SESSION" name="TMUX_SESSION" class="long" type="text" value="{$ftmux->TMUX_SESSION}" />
					<div class="hint">Enter the session name to be used by tmux, no spaces allowed in the name, this can't be changed after scripts start. If you are running multiple servers, you could put your hostname here</div>
				</td>
			</tr>

			<tr>
				<td><label for="MONITOR_PATH">Monitor a Ramdisk</label>:</td>
				<td>
					<input id="MONITOR_PATH" name="MONITOR_PATH" class="long" type="text" value="{$ftmux->MONITOR_PATH}" />
					<div class="hint">Enter a path here to have Monitor monitor its usage and free space. Must be a valid path.<br />To use this example, add to fstab and edit path, gid and uid, then mount as user not root:<br />tmpfs /var/www/nZEDb/nzbfiles/tmpunrar tmpfs user,uid=1000,gid=33,nodev,nodiratime,nosuid,size=512M,mode=777 0 0</div>
				</td>
			</tr>

		</table>
</fieldset>

<fieldset>
	<legend>Sequential</legend>
		<table class="input">
			<tr>
				<td><label for="SEQUENTIAL">Run Sequential</label>:</td>
				<td>
					{html_radios id="SEQUENTIAL" name='SEQUENTIAL' values=$truefalse_names output=$truefalse_names selected=$ftmux->SEQUENTIAL}
					<div class="hint">Choose to run update_binaries, backfill and update releases_sequentially. Changing requires restart. true/false</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="SEQ_TIMER">Sequential Sleep Timer</label>:</td>
				<td>
					<input id="SEQ_TIMER" name="SEQ_TIMER" class="text" type="text" value="{$ftmux->SEQ_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Update Binaries</legend>
		<table class="input">
			<tr>
				<td><label for="BINARIES">Update Binaries</label>:</td>
				<td>
					{html_radios id="BINARIES" name='BINARIES' values=$truefalse_names output=$truefalse_names selected=$ftmux->BINARIES}
					<div class="hint">Choose to run update_binaries true/false. Update binaries gets from your last_record to now. </div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="BINS_TIMER">Update Binaries Sleep Timer</label>:</td>
				<td>
					<input id="BINS_TIMER" name="BINS_TIMER" class="text" type="text" value="{$ftmux->BINS_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Backfill</legend>
		<table class="input">
			<tr>
				<td><label for="BACKFILL">Backfill</label>:</td>
				<td>
					{html_radios id="BACKFILL" name='BACKFILL' values=$truefalse_names output=$truefalse_names selected=$ftmux->BACKFILL}
					<div class="hint">Choose to run backfill true/false. Backfill gets from your first_record back. It is not recommended to set "Backfill Quantity" > 20k as this will overwhelm the collections table. Small increments is faster for update_releases.</div>
				</td>
			</tr>

			<tr>
				<td><label for="BACKFILL_TYPE">Backfill Intervals</label>:</td>
				<td>
					{html_radios id="BACKFILL_TYPE" name='BACKFILL_TYPE' values=$truefalse_names output=$truefalse_names selected=$ftmux->BACKFILL_TYPE}
					<div class="hint">Choose to run Backfill Intervals true/false. True will download everything per group upto your backfill days set in admin/view group, one group per thread. False will download "Backfill Quantity" headers per group until backfill days is reached.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="BACKFILL_QTY">Backfill Quantity</label>:</td>
				<td>
					<input id="BACKFILL_QTY" name="BACKFILL_QTY" class="text" type="text" value="{$ftmux->BACKFILL_QTY}" />
					<div class="hint">When not running backfill intervals, you select the number of headers per group per thread to download.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="BACKFILL_GROUPS">Backfill Groups</label>:</td>
				<td>
					<input id="BACKFILL_GROUPS" name="BACKFILL_GROUPS" class="text" type="text" value="{$ftmux->BACKFILL_GROUPS}" />
					<div class="hint">When running backfill the groups are sorted so that the newest groups are backfilled first. Select the number of groups to backfill per loop.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="BACK_TIMER">Backfill Sleep Timer</label>:</td>
				<td>
					<input id="BACK_TIMER" name="BACK_TIMER" class="text" type="text" value="{$ftmux->BACK_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="BACKFILL_DELAY">Backfill Delay</label>:</td>
				<td>
					<input id="BACKFILL_DELAY" name="BACKFILL_DELAY" class="text" type="text" value="{$ftmux->BACKFILL_DELAY}" />
					<div class="hint">If backfill is run on a new group before update_binaries has ran, it will result in error. This is the time, in seconds, between the script starting and the first time backfill runs. this is not to be confused with a sleep timer between loops.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Import NZBS</legend>
		<table class="input">
			<tr>
				<td><label for="IMPORT">Import NZBS</label>:</td>
				<td>
					{html_radios id="IMPORT" name='IMPORT' values=$truefalse_names output=$truefalse_names selected=$ftmux->IMPORT}
					<div class="hint">Choose to run import nzb script true/false. This can point to a single folder with multiple subfolders on just the one folder. If you run this threaded, it will run 1 folder per thread.</div>
				</td>
			</tr>

			<tr>
				<td><label for="NZBS">Nzbs</label>:</td>
				<td>
					<input id="NZBS" class="long" name="NZBS" type="text" value="{$ftmux->NZBS}" />
					<div class="hint">Set the path to the nzb dump you downloaded from torrents, this is the path to bulk files folder of nzbs. This is by default, recursive and threaded. You set the threads in edit site, Advanced Settings.</div>
				</td>
			</tr>

			<tr>
				<td><label for="IMPORT_BULK">Use Bulk Importer</label>:</td>
				<td>
					{html_radios id="IMPORT_BULK" name='IMPORT_BULK' values=$truefalse_names output=$truefalse_names selected=$ftmux->IMPORT_BULK}
					<div class="hint">Choose to run the bulk import nzb script true/false. This uses /dev/shm and can interfere with apparmor. This runs about 10% faster than stock importer. true/false</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="IMPORT_TIMER">Import NZBS Sleep Timer</label>:</td>
				<td>
					<input id="IMPORT_TIMER" name="IMPORT_TIMER" class="text" type="text" value="{$ftmux->IMPORT_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Update Releases</legend>
		<table class="input">
			<tr>
				<td><label for="RELEASES">Update Releases</label>:</td>
				<td>
					{html_radios id="RELEASES" name='RELEASES' values=$truefalse_names output=$truefalse_names selected=$ftmux->RELEASES}
					<div class="hint">Create releases, this is really only necessary to turn off when you only want to post process. This runs "Categorizes releases in misc sections using the search name" on first loop and has 33% chance of running on any other loop. Then runs update_releases.php 1 false to create new releases.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="REL_TIMER">Update Releases Sleep Timer</label>:</td>
				<td>
					<input id="REL_TIMER" name="REL_TIMER" class="text" type="text" value="{$ftmux->REL_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Postprocessing</legend>
		<table class="input">
			<tr>
				<td><label for="POST">Postprocess All</label>:</td>
				<td>
					{html_radios id="POST" name='POST' values=$truefalse_names output=$truefalse_names selected=$ftmux->POST}
					<div class="hint">Choose to postprocess movies, music, etc true/false</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="POST_TIMER">Postprocess Sleep Timer</label>:</td>
				<td>
					<input id="POST_TIMER" name="POST_TIMER" class="text" type="text" value="{$ftmux->POST_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="POST_KILL_TIMER">Postprocess Kill Timer</label>:</td>
				<td>
					<input id="POST_KILL_TIMER" name="POST_KILL_TIMER" class="text" type="text" value="{$ftmux->POST_KILL_TIMER}" />
					<div class="hint">The time postprocess is allowed to run with no updates to the screen. Activity is detected when the history for the pane changes. The clock is restarted everytime activity is detected.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Fix Release Names</legend>
		<table class="input">
			<tr>
				<td><label for="FIX_NAMES">Fix Release Names</label>:</td>
				<td>
					{html_radios id="FIX_NAMES" name='FIX_NAMES' values=$truefalse_names output=$truefalse_names selected=$ftmux->FIX_NAMES}
					<div class="hint">Choose to try to fix Releases Names true/false</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="FIX_TIMER">Fix Release Names Sleep Timer</label>:</td>
				<td>
					<input id="FIX_TIMER" name="FIX_TIMER" class="text" type="text" value="{$ftmux->FIX_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Remove Crap Releases</legend>
		<table class="input">
			<tr>
				<td><label for="FIX_CRAP">Remove Crap Releases</label>:</td>
				<td>
					{html_radios id="FIX_CRAP" name='FIX_CRAP' values=$truefalse_names output=$truefalse_names selected=$ftmux->FIX_CRAP}
					<div class="hint">Choose to run Remove Crap Releases true/false</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="CRAP_TIMER">Remove Crap Releases Sleep Timer</label>:</td>
				<td>
					<input id="CRAP_TIMER" name="CRAP_TIMER" class="text" type="text" value="{$ftmux->CRAP_TIMER}" />
					<div class="hint">The time to sleep from the time the loop ends until it is restarted, in seconds.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Update TV/Theater</legend>
		<table class="input">
			<tr>
				<td><label for="UPDATE_TV">Update TV and Theater Schedules</label>:</td>
				<td>
					{html_radios id="UPDATE_TV" name='UPDATE_TV' values=$truefalse_names output=$truefalse_names selected=$ftmux->UPDATE_TV}
					<div class="hint">Choose to run Update TV and Theater Schedules true/false</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="TV_TIMER">Update TV and Theater Start Timer</label>:</td>
				<td>
					<input id="TV_TIMER" name="TV_TIMER" class="text" type="text" value="{$ftmux->TV_TIMER}" />
					<div class="hint">This is a start timer. The default is 12 hours. This means that if enabled, is will start/run every 12 hours, no matter how long it runs for.</div>
				</td>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Miscellaneous</legend>
		<table class="input">
			<tr>
				<td style="width:160px;"><label for="NICENESS">Niceness</label>:</td>
				<td>
					<input id="NICENESS" name="NICENESS" type="text" value="{$ftmux->NICENESS}" />
					<div class="hint">This sets the 'nice'ness of each script, default is 19, the lowest, the highest is -20 anything between -1 and -20 require root/sudo to run</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="DEFRAG_CACHE">Defrag Query Cache</label>:</td>
				<td>
					<input id="DEFRAG_CACHE" name="DEFRAG_CACHE" class="text" type="text" value="{$ftmux->DEFRAG_CACHE}" />
					<div class="hint">The mysql query cache gets frogmented over time. Enter the time, in seconds, to defrag the query cache. <br/ >cmd: FLUSH QUERY CACHE;</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="COLLECTIONS_KILL">Maximum Collections</label>:</td>
				<td>
					<input id="COLLECTIONS_KILL" name="COLLECTIONS_KILL" class="text" type="text" value="{$ftmux->COLLECTIONS_KILL}" />
					<div class="hint">Set this to any number above 0 and when it is exceeded, backfill and update binaries will be terminated. 0 disables.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="POSTPROCESS_KILL">Maximum Postprocess</label>:</td>
				<td>
					<input id="POSTPROCESS_KILL" name="POSTPROCESS_KILL" class="text" type="text" value="{$ftmux->POSTPROCESS_KILL}" />
					<div class="hint">Set this to any number above 0 and when it is exceeded, import, backfill and update binaries will be terminated. 0 disables.</div>
				</td>

			<tr>
				<td style="width:160px;"><label for="WRITE_LOGS">Logging</label>:</td>
				<td>
					{html_radios id="WRITE_LOGS" name='WRITE_LOGS' values=$truefalse_names output=$truefalse_names selected=$ftmux->WRITE_LOGS}
					<div class="hint">Set this to write each panes output to a per pane per day log file.</div>
				</td>
			</tr>
			</tr>
		</table>
</fieldset>

<fieldset>
	<legend>Server Monitors</legend>
		<table class="input">
			<tr>
				<td style="width:160px;"><label for="HTOP">htop</label>:</td>
				<td>
					{html_radios id="HTOP" name='HTOP' values=$truefalse_names output=$truefalse_names selected=$ftmux->HTOP}
					<div class="hint">htop - an interactive process viewer for Linux. The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="NMON">nmon</label>:</td>
				<td>
					{html_radios id="NMON" name='NMON' values=$truefalse_names output=$truefalse_names selected=$ftmux->NMON}
					<div class="hint">nmon is short for Nigel's performance Monitor for Linux. The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="VNSTAT">vnstat</label>:</td>
				<td>
					{html_radios id="VNSTAT" name='VNSTAT' values=$truefalse_names output=$truefalse_names selected=$ftmux->VNSTAT}
					<input id="VNSTAT_ARGS" name="VNSTAT_ARGS" class="text" type="text" value="{$ftmux->VNSTAT_ARGS}" />
					<div class="hint">vnStat is a console-based network traffic monitor for Linux and BSD that keeps a log of network traffic for the selected interface(s). Any additional arguments should be placed in the text box. The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="TCPTRACK">tcptrack</label>:</td>
				<td>
					{html_radios id="TCPTRACK" name='TCPTRACK' values=$truefalse_names output=$truefalse_names selected=$ftmux->TCPTRACK}
					<input id="TCPTRACK_ARGS" name="TCPTRACK_ARGS" class="text" type="text" value="{$ftmux->TCPTRACK_ARGS}" />
					<div class="hint">tcptrack displays the status of TCP connections that it sees on a given network interface. tcptrack monitors their state and displays information such as state, source/destination addresses and bandwidth usage in a sorted, updated list very much like the top(1) command. <br />Any additional arguments should be placed in the text box. <br />You may need to run "sudo setcap cap_net_raw+ep /usr/bin/tcptrack", to be able to run as user. The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="BWMNG">bwm-ng</label>:</td>
				<td>
					{html_radios id="BWMNG" name='BWMNG' values=$truefalse_names output=$truefalse_names selected=$ftmux->BWMNG}
					<div class="hint">bwm-ng can be used to monitor the current bandwidth of all or some specific network interfaces or disks (or partitions). The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="MYTOP">mytop</label>:</td>
				<td>
					{html_radios id="MYTOP" name='MYTOP' values=$truefalse_names output=$truefalse_names selected=$ftmux->MYTOP}
					<div class="hint">mytop - display MySQL server performance info like `top'. <br />You will need to create ~/.mytop, an example can be found in 'perldoc mytop'. The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

			<tr>
				<td style="width:160px;"><label for="CONSOLE">Console</label>:</td>
				<td>
					{html_radios id="CONSOLE" name='CONSOLE' values=$truefalse_names output=$truefalse_names selected=$ftmux->CONSOLE}
					<div class="hint">Open an empty bash shell. The pane for this can not be created after tmux starts.</div>
				</td>
			</tr>

		</table>
</fieldset>

<input type="submit" value="Save Tmux Settings" />

</form>

