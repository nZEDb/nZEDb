 
<h1>{$page->title}</h1>

<div id="donate">
	<h3>Support development</h3>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="hidden" name="hosted_button_id" value="K36CTY8X2XMX8">
	<input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
	<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	</form>
</div>

<p>
	Welcome to newznab. In this area you will be able to configure many aspects of your site.<br>
	If this is your first time here, you need to start the scripts which will fill newznab.
</p>

	<ol style="list-style-type:decimal; line-height: 180%;">
	<li style="margin-bottom: 15px;">Configure your <a href="{$smarty.const.WWW_TOP}/site-edit.php">site options</a>. The defaults will probably work fine.</li>
	<li style="margin-bottom: 15px;">There a default list of usenet groups provided. To get started, you will need to <a href="{$smarty.const.WWW_TOP}/group-list.php">activate some groups</a>. <u>Do not</u> activate every group if its your first time setting this up. Try one or two first.
	You can also <a href="{$smarty.const.WWW_TOP}/group-edit.php">add your own groups</a> manually.</li>
	<li style="margin-bottom: 15px;">Next you will want to get the latest headers. <b>This should be done from the command line</b>, using the linux or windows shell scripts found in /misc/update_scripts/nix_scripts (or win_scripts for windows users), as it can take some time. If this is your first time dont bother with the init scripts just open a command prompt...
	<div style="padding-left:20px; font-family:courier;">cd newznab/misc/update_scripts<br/>php update_binaries.php</div>
	</li>
	<li style="margin-bottom: 15px;">After obtaining headers, the next step is to create releases. <b>This is best done from the command line</b> using the linux or windows shell scripts found in /misc/update_scripts/nix_scripts (or win_scripts for windows users). If this is your first time dont bother with the init scripts just open a command prompt...
	<div style="padding-left:20px; font-family:courier;">cd newznab/misc/update_scripts<br/>php update_releases.php</div>
	</li>
	<li style="margin-bottom: 15px;">If you intend to keep using newznab, it is best to sign up for your own api keys from <a href="http://www.themoviedb.org/account/signup">tmdb</a>, <a href="http://http://developer.rottentomatoes.com/">rotten tomatoes</a> and <a href="http://aws.amazon.com/">amazon</a>.</li>
	<li>If you like newznab and intend to continue using it, please consider <a href="http://www.newznab.com/">sending a donation</a> to the team who write and maintain it.</li>
	</ol>
