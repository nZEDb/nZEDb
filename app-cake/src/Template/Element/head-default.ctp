<?php use Cake\Core\Configure; ?>
<?= $this->Html->docType('html5') ?>

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?= Configure::read('Theme.title') ?> | <?= $this->fetch('title') ?></title>
	<!-- Tell the browser to be responsive to screen width -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Bootstrap 3.3.7 -->
	<?= $this->Html->css('AdminLTE./bower_components/bootstrap/dist/css/bootstrap') ?>

	<!-- Font Awesome -->
	<?= $this->Html->css('AdminLTE./bower_components/font-awesome/css/font-awesome') ?>

	<!-- Ionicons -->
	<?= $this->Html->css('AdminLTE./bower_components/Ionicons/css/ionicons') ?>

	<!-- Theme style -->
	<?= $this->Html->css('AdminLTE.AdminLTE') ?>

	<!-- Google Font -->
	<link rel="stylesheet"
		href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
	<!-- AdminLTE Skins. Choose a skin from the css/skins folder,
			 instead of downloading all of them, to reduce the load. -->
	<?= $this->Html->css(Configure::read('Theme.skin')) ?>
	<?= $this->fetch('css') ?>

<?php if (Configure::read('recaptcha.enabled') !== false): ?>
	<script type="text/javascript" src="https://www.google.com/recaptcha/api.js"></script>
<?php endif; ?>
</head>
