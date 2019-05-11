<?php
$appDescription = 'nZEDb, the usenet indexer';
?>
<?= $this->Html->docType('html5') ?>

<html>
<head>
	<?= $this->Html->charset() ?>

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= h($title) ?></title>
	<?= $this->fetch('meta') ?>
	<?= $this->Html->css(['base', 'style', 'nzedb'], ['block' => true]) ?>
	<?= $this->fetch('css') ?>
	<?= $this->fetch('script') ?>

</head>
<body>
<header class="row">
	<?= $this->element('UserMenu') ?>

	<div id="logo">
		<?= $this->Html->image('logo.png',
			['alt' => 'nZEDb Logo', 'class' => 'logoimg']) . PHP_EOL ?>
		<em>A great usenet indexer</em>
	</div>
</header>

<!--[if lt IE 10]>
<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please
	<a href="http://browsehappy.com/">upgrade your browser</a> or
	<a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a>
					   to improve your experience.</p>
<![endif]-->

<div class="container clearfix"><?= $this->fetch('content') ?>
	<hr />
</div>

</body>
</html>
