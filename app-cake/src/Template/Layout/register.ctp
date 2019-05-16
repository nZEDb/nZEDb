<?php use Cake\Core\Configure; ?>
<?= $this->Html->docType('html5') ?>
<?= $this->element('default-head') ?>

<body class="hold-transition skin-<?= Configure::read('Theme.skin') ?> register-page">
<div class="register-box">
	<div class="register-logo">
		<a href="<?= $this->Url->build() ?>"><?= Configure::read('Theme.logo.large') ?></a>
	</div>

	<div class="register-box-body">
		<p class="login-box-msg">Register a new membership</p>

		<?= $this->Flash->render() ?>

		<?= $this->fetch('content') ?>

		<?php if (Configure::read('Theme.login.show_social')): ?>
			<div class="social-auth-links text-center">
				<p>- OR -</p>
				<a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i>Sign in using Facebook</a>
				<a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i>Sign in using Google+</a>
			</div>
			<!-- /.social-auth-links -->
		<?php endif ?>

		<?= $this->Html->link('I am already a member',
			[
				'controller' => 'Users',
				'action'		 => 'login',
			],
			['class' => 'btn']
		) ?>
	</div>
	<!-- /.form-box -->
</div>
<!-- /.register-box -->

<?= $this->element('default-tail') ?>

<script>
	$(function () {
		$('input').iCheck({
			checkboxClass: 'icheckbox_square-blue',
			radioClass: 'iradio_square-blue',
			increaseArea: '20%' /* optional */
		});
	});
</script>
</body>
</html>
