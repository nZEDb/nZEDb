<h1>{$page->title}</h1>
<p>
	Welcome to nZEDb. In this area you will be able to configure many aspects of your site.<br>
	If this is your first time here, you need to start the scripts which will fill nZEDb.
</p>
<ol style="list-style-type:decimal; line-height: 180%;">
	<li style="margin-bottom: 15px;">Configure your <a href="{$smarty.const.WWW_TOP}/site-edit.php">site options</a>. The defaults will probably work fine.</li>
	<li style="margin-bottom: 15px;">There is a default list of usenet groups provided. To get started, you will need to <a href="{$smarty.const.WWW_TOP}/group-list.php">activate some groups</a>. <u>Do not</u> activate every group if its your first time setting this up. Try one or two first.
	You can also <a href="{$smarty.const.WWW_TOP}/group-edit.php">add your own groups</a> manually.</li>
	<li style="margin-bottom: 15px;">Next you will want to get the latest headers. <b>This should be done from the command line</b>, using the linux or windows shell scripts found in {$smarty.const.nZEDb_ROOT}misc/update/nix (or win for windows users), as it can take some time. If this is your first time don't bother with the init scripts just open a command prompt...
		<div style="padding-left:20px; font-family:courier;">cd {$smarty.const.nZEDb_ROOT}misc/update<br/>php update_binaries.php</div>
	</li>
	<li style="margin-bottom: 15px;">After obtaining headers, the next step is to create releases. <b>This is best done from the command line</b> using the linux or windows shell scripts found in {$smarty.const.nZEDb_ROOT}misc/update/nix (or win for windows users). If this is your first time don't bother with the init scripts just open a command prompt...
		<div style="padding-left:20px; font-family:courier;">cd {$smarty.const.nZEDb_ROOT}misc/update<br/>php update_releases.php</div>
	</li>
	<li style="margin-bottom: 15px;">If you intend to keep using nZEDb, it is best to sign up for your own api keys from <a href="http://www.themoviedb.org/account/signup">tmdb</a>, <a href="http://trakt.tv">trakt</a>, <a href="http://developer.rottentomatoes.com/">rotten tomatoes</a>, <a href="http://aws.amazon.com/">amazon</a> and <a href="http://fanart.tv/">fanart</a>.</li>
</ol>