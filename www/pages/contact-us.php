<?php

use app\models\Settings;
use nzedb\Captcha;
use nzedb\utility\Misc;

$captcha = new Captcha($page);
$msg = '';
$title = Settings::value('site.main.title');
if (isset($_POST['useremail']) && $captcha->getError() === false) {
	// Send the contact info and report back to user.
	$email = $_POST['useremail'];
	$mailto = Settings::value('site.main.email');

	$mailsubj = 'Contact Form Submitted';
	$mailbody = 'Values submitted from contact form:<br/>';

	// @TODO take this loop out, it's not safe.
	foreach ($_POST as $key => $value) {
		if ($key !== 'submit') {
			$mailbody .= "$key : $value<br/>\n";
		}
	}

	if (! \preg_match("/\n/i", $_POST['useremail'])) {
		Misc::sendEmail($mailto, $mailsubj, $mailbody, $email);
	}
	$msg = "<h2 style='text-align:center;'>Thank you for getting in touch with " . $title . '.</h2>';
}

$page->smarty->assign('msg', $msg);
$page->title = 'Contact ' . $title;
$page->meta_title = 'Contact ' . $title;
$page->meta_keywords = 'contact us,contact,get in touch,email';
$page->meta_description = 'Contact us at ' . $title . ' and submit your feedback';

$page->content = $page->smarty->fetch('contact.tpl');

$page->render();
