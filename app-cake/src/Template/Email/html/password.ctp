<?php

$this->assign('title', 'Password Reset Request');

?>
<h1>Password Reset Request</h1>
<div>
	<p>Someone requested a password reset for this email address.</p>
	<p>To reset the password use <?= $this->Html->link('this link', ['href' => $url]) ?>. The link
	   will be valid for 72 hours.</p>
	<p>If you did not request this reset, the request will be removed when you next login.</p>
</div>
