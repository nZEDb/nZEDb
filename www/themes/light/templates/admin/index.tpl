
<h1>{$page->title}</h1>

<p>
	Welcome to nZEDb. In this area you will be able to configure many aspects of your site.<br>
	If this is your first time here, you need to start the scripts which will fill nZEDb.
</p>

<ol style="list-style-type:decimal; line-height: 180%;">
	<li style="margin-bottom: 15px;">Configure your <a href="{$smarty.const.WWW_TOP}/site-edit.php">site options</a> and
		<a href="{$smarty.const.WWW_TOP}/tmux-edit.php">tmux options</a>, if you plan to run the tmux scripts.
		The defaults will probably work fine.</li>
	<li style="margin-bottom: 15px;">There a default list of usenet groups provided. To get started, you will need to
		<a href="{$smarty.const.WWW_TOP}/group-list.php">activate some groups</a>.
	<br /><u><b><i>Do not</i></b></u> activate every group if its your first time setting this up. Try one or two first.
	You can also <a href="{$smarty.const.WWW_TOP}/group-edit.php">add your own groups</a> manually.</li>
<li style="margin-bottom: 15px;">You should populate the TVRage table:
	<div class="code">
		cd {$smarty.const.nZEDb_ROOT}misc/testing/DB
		<br />php populate_tvrage.php true
	</div>
</li>
<li style="margin-bottom: 15px;">Next you will want to get the latest headers. <b>This should be done from the
		command line</b>, using the linux or windows shell scripts found in:</b>
	<div class="code">
		{$smarty.const.nZEDb_ROOT}misc/update/nix/tmux
		<br />{$smarty.const.nZEDb_ROOT}misc/update/nix/screen
		<br />{$smarty.const.nZEDb_ROOT}misc/update/win
	</div>
	as it can take some time. If this is your first time don't bother with the init scripts just open a command prompt...
	<div class="code">
		cd {$smarty.const.nZEDb_ROOT}misc/update
		<br/>php update_binaries.php</div>
</li>
<li style="margin-bottom: 15px;">After obtaining headers, the next step is to create releases. <b>This is best done
		from the command line</b> using the linux or windows shell scripts.
		<br />If this is your first time don't bother with the init scripts just open a command prompt...
	<div class="code">
		cd {$smarty.const.nZEDb_ROOT}misc/update
		<br />php update_releases.php 1 true</div>
</li>
<li style="margin-bottom: 15px;">If you intend to keep using nZEDb, it is best to sign up for your own api keys from
	<a href="http://www.themoviedb.org/account/signup">tmdb</a>, <a href="http://trakt.tv">trakt</a>,
	<a href="http://developer.rottentomatoes.com/">rotten tomatoes</a>, <a href="http://aws.amazon.com/">amazon
	</a>, <a href="http://anidb.net/">anidb</a> and <a href="http://fanart.tv/">fanart</a>.</li>
</ol>
