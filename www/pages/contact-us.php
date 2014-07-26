<?php
if (isset($_POST["useremail"])) {
	// Send the contact info and report back to user.
	$email = $_POST["useremail"];
	$mailto = $page->settings->getSetting('email');

	$mailsubj = "Contact Form Submitted";
	$mailhead = "From: $email\n";
	$mailbody = "Values submitted from contact form:\n";

	while (list ($key, $val) = each($_POST)) {
		$mailbody .= "$key : $val\n";
	}

	if (!preg_match("/\n/i", $_POST["useremail"])) {
		@mail($mailto, $mailsubj, $mailbody, $mailhead);
	}

	$page->smarty->assign("msg", "<h2 style='text-align:center;'>Thank you for getting in touch with " . $page->settings->getSetting('title') . ".</h2>");
}

$page->title = "Contact " . $page->settings->getSetting('title');
$page->meta_title = "Contact " . $page->settings->getSetting('title');
$page->meta_keywords = "contact us,contact,get in touch,email";
$page->meta_description = "Contact us at " . $page->settings->getSetting('title') . " and submit your feedback";

$page->content = $page->smarty->fetch('contact.tpl');

$page->render();