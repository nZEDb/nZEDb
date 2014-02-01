<?php
if (isset($_POST["useremail"])) {
	// Send the contact info and report back to user.
	$email = $_POST["useremail"];
	$mailto = $page->site->email;
	$mailsubj = "Contact Form Submitted";
	$mailhead = "From: $email\n";
	$mailbody = "Values submitted from contact form:\n";

	while (list ($key, $val) = each($_POST)) {
		$mailbody .= "$key : $val\n";
	}

	if (!preg_match("/\n/i", $_POST["useremail"])) {
		@mail($mailto, $mailsubj, $mailbody, $mailhead);
	}

	$page->smarty->assign("msg", "<h2 style='padding-top:25px;'>Thanks for getting in touch with " . $page->site->title . ".</h2>");
}

$page->title = "Contact " . $page->site->title;
$page->meta_title = "Contact " . $page->site->title;
$page->meta_keywords = "contact us,contact,get in touch,email";
$page->meta_description = "Contact us at " . $page->site->title . " and submit your feedback";

$page->content = $page->smarty->fetch('contact.tpl');

$page->render();
