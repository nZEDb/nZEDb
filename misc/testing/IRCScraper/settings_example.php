<?php
// Random username will be generated for you, you can change the "$username" bellow with "YourActualUserName" if you want to.
$username = getRandomUsername();

// EFNET server details.
define('SCRAPE_IRC_EFNET_SERVER', 'irc.Prison.NET'); // Efnet server address, change if you have issues connecting.
define('SCRAPE_IRC_EFNET_PORT', '6667');             // Port for efnet server.
define('SCRAPE_IRC_EFNET_NICKNAME', "$username");    // Nick name (this is the nickname everyone sees in the channel)
define('SCRAPE_IRC_EFNET_REALNAME', "$username");    // This is a name that people see in /whois, you can set this to your nickname.
define('SCRAPE_IRC_EFNET_USERNAME', "$username");    // This is part of your hostname, you can set this the same as nickname. This is also used to log in to ZNC.
define('SCRAPE_IRC_EFNET_PASSWORD', false);          // This is used for bouncers like ZNC, set this false or '' if you don't have a bouncer.

define('SCRAPE_IRC_C_Z_BOOL', true); // True uses Corrupt, False uses Zenet. (they both PRE the same stuff). If you have trouble with one, use the other.

// Corrupt-Net server details.
define('SCRAPE_IRC_CORRUPT_SERVER', 'irc.corrupt-net.org'); // This should not be changed, since this is the only address to corrupt.
define('SCRAPE_IRC_CORRUPT_PORT', '6667');
define('SCRAPE_IRC_CORRUPT_NICKNAME', "$username");
define('SCRAPE_IRC_CORRUPT_REALNAME', "$username");
define('SCRAPE_IRC_CORRUPT_USERNAME', "$username");
define('SCRAPE_IRC_CORRUPT_PASSWORD', false);

// Zenet server details.
define('SCRAPE_IRC_ZENET_SERVER', 'irc.zenet.org');
define('SCRAPE_IRC_ZENET_PORT', '6667');
define('SCRAPE_IRC_ZENET_NICKNAME', "$username");
define('SCRAPE_IRC_ZENET_REALNAME', "$username");
define('SCRAPE_IRC_ZENET_USERNAME', "$username");
define('SCRAPE_IRC_ZENET_PASSWORD', false);

########################################################################################################################
########################################################################################################################
########################################################################################################################

// Try to generate a random username for you (up to 9 chars long).
function getRandomUsername() {
	$username = array(
		'John', 'Jeff', 'Mike', 'Micheal', 'Simon', 'Eric', 'Jennifer',
		'Robert', 'Natasha', 'James', 'Ozzy', 'Dana', 'Patricia', 'Patrick',
		'Bill', 'Anita', 'Bart', 'Billy', 'Aaron', 'Chris', 'Chipper', 'Edge',
		'Zhed', 'Scott', 'David', 'Willie', 'Stewart',
		'Sophia', 'Emma', 'Olivia', 'Isabella', 'Lily', 'Chloe',
		'Madison', 'Emily', 'Ella', 'Madelyn', 'Abigail', 'Aubrey',
		'Addison', 'Avery', 'Layla', 'Hailey', 'Amelia', 'Hannah', 'Charlotte',
		'Kaitlyn', 'Harper', 'Kaylee', 'Sophie', 'Mackenzie', 'Peyton', 'Riley',
		'Grace', 'Brooklyn', 'Sarah', 'Aaliyah', 'Anna', 'Arianna', 'Ellie',
		'Natalie', 'Isabelle', 'Lillian', 'Evelyn', 'Elizabeth', 'Lyla', 'Lucy',
		'Claire', 'Makayla', 'Kylie', 'Audrey', 'Maya', 'Aiden', 'Jackson',
		'Ethan', 'Liam', 'Mason', 'Noah', 'Lucas', 'Jacob', 'Jayden', 'Jack',
		'Logan', 'Ryan', 'Caleb', 'Benjamin', 'William', 'Michael', 'Alexander',
		'Elijah', 'Matthew', 'Dylan', 'James', 'Owen', 'Connor', 'Brayden',
		'Carter', 'Landon', 'Joshua', 'Luke', 'Daniel', 'Gabriel', 'Nicholas',
		'Nathan', 'Oliver', 'Henry', 'Andrew', 'Gavin', 'Cameron',
		'Isaac', 'Evan', 'Samuel', 'Grayson', 'Tyler', 'Zachary', 'Wyatt',
		'Joseph', 'Charlie', 'Hunter', 'David', 'Gabriella', 'Annabelle'
	);
	$username = $username[mt_rand(0, count($username) - 1)];
	$current = strlen($username);
	if ($current >= 7) {
		$username = substr($username, 0, 6);
		$current = 6;
	}
	for ($i = 0; $i < (9 - $current); $i++) {
		$username .= rand(0,9);
	}
	return $username;
}