<?php use Cake\Core\Configure; ?>
<?= $this->Html->docType('html5') ?>
<?= $this->element('default-head') ?>

<body class="hold-transition skin-<?= Configure::read('Theme.skin') ?> login-page">
<div class="login-box">
	<div class="login-logo">
		<a href="<?= $this->Url->build('/') ?>"><?= Configure::read('Theme.logo.large') ?></a>
	</div>
	<!-- /.login-logo -->
	<div class="login-box-body">
		<p class="login-box-msg">Sign in to start your session</p>

		<?= $this->Flash->render() ?>

		<?= $this->fetch('content') ?>

		<?php if (Configure::read('Theme.login.show_social')): ?>
			<div class="social-auth-links text-center">
				<p>- OR -</p>
				<a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i> Sign in using Facebook</a>
				<a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i> Sign in using Google+</a>
			</div>
			<!-- /.social-auth-links -->
		<?php endif ?>

		<div class="pull-left">
			<a href="<?= $this->Url->build('/forgot') ?>" class="">I forgot my password</a>
		</div>
		<?php if (Configure::read('Theme.login.show_register')): ?>

		<div class="pull-right">
			<a href = "<?= $this->Url->build('/register') ?>" class="" >Create a new account</a>
		</div>
		<?php endif ?>

	</div>
	<!-- /.login-box-body -->
</div>
<!-- /.login-box -->

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
