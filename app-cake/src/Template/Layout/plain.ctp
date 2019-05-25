<?php use Cake\Core\Configure; ?>
<?= $this->Html->docType('html5') ?>

<?= $this->element('default-head') ?>

<body class="hold-transition skin-<?= Configure::read('Theme.skin') ?> <?= $page_type ?>-page">
<div class="<?= $page_type ?>-box">
	<div class="<?= $page_type ?>-logo">
		<a href="<?= $this->Url->build() ?>"><?= Configure::read('Theme.logo.large') ?></a>
	</div>
	<!-- /.login-logo -->
	<div class="<?= $page_type ?>-box-body">
		<p class="<?= $page_type ?>-box-msg"><?= $message ?></p>

		<?= $this->Flash->render() ?>

		<?= $this->fetch('content') ?>

		<?php if ((!isset($no_social) || $no_social === false) &&
			Configure::read('Theme.login.show_social')): ?>
			<div class="social-auth-links text-center">
				<p>- OR -</p>
				<a href="#"
					class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i>
					Sign in using Facebook</a>
				<a href="#"
					class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i>
					Sign in using Google+</a>
			</div>
			<!-- /.social-auth-links -->
		<?php endif ?>

	</div>
	<!-- /.login-box-body -->
</div>
<!-- /.login-box -->

<?= $this->element('default-tail') ?>
</body>
</html>
