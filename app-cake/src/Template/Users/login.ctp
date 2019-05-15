<?php
$this->layout = 'login';
?>
<?= $this->Form->create() ?>

<form action="<?= $this->Url->build(['controller' => 'users', 'action' => 'login']) ?>" method="post">
	<div class="form-group has-feedback">
		<input type="text" class="form-control" placeholder="User name" name="username">
		<span class="glyphicon glyphicon-user form-control-feedback"></span>
	</div>
	<div class="form-group has-feedback">
		<input type="password" class="form-control" placeholder="Password" name="password">
		<span class="glyphicon glyphicon-lock form-control-feedback"></span>
	</div>
	<div class="row">
		<div class="col-xs-8">
			<div class="checkbox icheck">
				<label>
					<input type="checkbox"> Remember Me
				</label>
			</div>
		</div>
		<!-- /.col -->
		<div class="col-xs-4">
			<button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
		</div>
		<!-- /.col -->
	</div>
<?= $this->Form->end() ?>
