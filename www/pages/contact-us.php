<?php

use app\models\Settings;
use nzedb\utility\Misc;
use nzedb\Captcha;

$captcha = new Captcha($page);
$msg = '';
if (isset($_POST['useremail'])) {

	if ($captcha->getError() === false) {

		// Send the contact info and report back to user.
		$email = $_POST['useremail'];
		$mailto = Settings::value('site.main.email');

		$mailsubj = 'Contact Form Submitted';
		$mailbody = 'Values submitted from contact form:<br/>';

		//@TODO take this loop out, it's not safe.
		foreach($_POST as $key => $value) {
			if ($key != 'submit') {
				$mailbody .= "$key : $value<br/>";
			}
		}

		if (!preg_match("/\n/i", $_POST['useremail'])) {
			Misc::sendEmail($mailto, $mailsubj, $mailbody, $email);
		}
		$msg = "<h2 style='text-align:center;'>Thank you for getting in touch with " . Settings::value('site.main.title') . '.</h2>';
	}
}
$page->smarty->assign('msg', $msg);
$page->title = 'Contact ' . Settings::value('site.main.title');
$page->meta_title = 'Contact ' . Settings::value('site.main.title');
$page->meta_keywords = 'contact us,contact,get in touch,email';
$page->meta_description = 'Contact us at ' . Settings::value('site.main.title') . ' and submit your feedback';

$page->content = $page->smarty->fetch('contact.tpl');

$page->render();
