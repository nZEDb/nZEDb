<?php
$this->set('statusbar', '');
?>

<div class="row">
	<div class="columns large-12 text-center">
<h1>Login</h1>
	<?= $this->Form->create() ?>
		<?= $this->Form->control('username') ?>
		<?= $this->Form->control('password') ?>
	<?= $this->Form->button('Login') ?>
	<?= $this->Form->end() ?>
</div>
	<?php debug($user) ?>
</div>
