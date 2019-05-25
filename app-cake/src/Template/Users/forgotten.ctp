<?php
$this->layout = 'plain';

$this->set('message', 'Enter your user name or e-mail address.<br />A reset e-mail will be sent.');
$this->set('no_social', true);
$this->set('page_type', 'login');

?>
<?= $this->Form->create() ?>

<form action="<?= $this->Url->build('/forgot') ?>" method="post">
	<div class="form-group has-feedback">
		<input type="text" class="form-control" placeholder="User name or e-mail address."
			name="username">
		<span class="glyphicon glyphicon-user form-control-feedback"></span>
	</div>
	<div class="row">
		<!-- /.col -->
		<div class="col-xs-4 pull-right">
			<button type="submit" class="btn btn-primary btn-block btn-flat">Send</button>
		</div>
		<!-- /.col -->
	</div>
<?= $this->Form->end() ?>
