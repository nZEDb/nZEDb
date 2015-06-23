<?php

use nzedb\utility\Utility;
use nzedb\Captcha;

$captcha = new Captcha($page);
$msg = '';
if (isset($_POST["useremail"])) {

	if ($captcha->getError() === false) {

		// Send the contact info and report back to user.
		$email = $_POST["useremail"];
		$mailto = $page->settings->getSetting('email');

		$mailsubj = "Contact Form Submitted";
		$mailbody = "Values submitted from contact form:<br/>";

		//@TODO take this loop out, it's not safe.
		while (list ($key, $val) = each($_POST)) {
			if ($key != 'submit') {
				$mailbody .= "$key : $val<br/>";
			}
		}

		if (!preg_match("/\n/i", $_POST["useremail"])) {
			Utility::sendEmail($mailto, $mailsubj, $mailbody, $email);
		}
		$msg = "<h2 style='text-align:center;'>Thank you for getting in touch with " . $page->settings->getSetting('title') . ".</h2>";
	}
}
$page->smarty->assign("msg", $msg);
$page->title = "Contact " . $page->settings->getSetting('title');
$page->meta_title = "Contact " . $page->settings->getSetting('title');
$page->meta_keywords = "contact us,contact,get in touch,email";
$page->meta_description = "Contact us at " . $page->settings->getSetting('title') . " and submit your feedback";

$page->content = $page->smarty->fetch('contact.tpl');

$page->render();
