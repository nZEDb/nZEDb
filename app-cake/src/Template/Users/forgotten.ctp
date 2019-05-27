<?php
use Cake\Core\Configure;

$this->layout = 'plain';

$this->set('message', 'Forgotten Password');
$this->set('no_social', true);
$this->set('page_type', 'login');

?>
<p>Enter your user name or e-mail address.<br />A reset e-mail will be sent.</p>
<?= $this->Form->create() ?>

<form action="<?= $this->Url->build('/forgot') ?>" method="post">
	<div class="form-group has-feedback">
		<input type="text" class="form-control" required placeholder="User name or e-mail address." name="email">
		<span class="glyphicon glyphicon-user form-control-feedback"></span>
	</div>
	<div class="row">
		<!-- /.col -->
		<div class="col-xs-4 pull-right">
			<button type="submit" class="btn btn-primary btn-block btn-flat">Send</button>
		</div>
		<!-- /.col -->
	</div>
	<?php if (Configure::read('App.use_recaptcha')):
			echo $this->element('recaptcha');
		endif; ?>

<?= $this->Form->end() ?>
