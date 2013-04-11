<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/releaseregex.php");

$page = new AdminPage();
$page->title = "Submit your regex expressions to newznab";

$regex = new ReleaseRegex();
$regexList = $regex->get(false, -1, true, true);

if (count($regexList))
{
	$regexSerialize = serialize($regexList);
	$regexFilename  = 'releaseregex-' . time() . '.regex';
	
	// User wants to submit their regex's
	if (isset($_POST['regex_submit_please']))
	{
		// Submit
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (newznab / compatible;)");
		curl_setopt($ch, CURLOPT_URL,"http://newznab.com/regex/uploadregex.php");
		curl_setopt($ch, CURLOPT_POST, true);
		$post = array(
			"regex" => $regexSerialize
		);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
		$response = curl_exec($ch);
		
		curl_close($ch);
				
		if ($response == 'OK')
		{
			$page->smarty->assign('upload_status', 'OK');
		}
		else
		{
			$page->smarty->assign('upload_status', 'BAD');
		}
	}
}
else
{
	$regexFilename = 'No user regexs found. Please add some.';
	$regexList = array('Empty');
	$page->smarty->assign('regex_error', 1);
}

$page->smarty->assign('regex_filename', $regexFilename);
$page->smarty->assign('regex_contents', $regexList);

$page->content  = $page->smarty->fetch('regex-submit.tpl');
$page->render();

?>