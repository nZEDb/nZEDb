<?php

use Cake\Core\Configure;

?>
<?= $this->fetch('scriptBottom') ?>
<?php
$javaScripts =
	[
		'AdminLTE./bower_components/jquery/dist/jquery', // jQuery 3
		'AdminLTE./bower_components/bootstrap/dist/js/bootstrap', // Bootstrap 3.3.7
		'AdminLTE./bower_components/jquery-slimscroll/jquery.slimscroll', // Slimscroll
		'AdminLTE./plugins/iCheck/icheck', // iCheck
		'AdminLTE.adminlte', // AdminLTE App
	];

$debug = Configure::read('debug');
?>

<?php foreach ($javaScripts as $script) : ?>
<?= $this->Html->script($script . ($debug === true ? '' : '.min')) ?>

<?php endforeach; ?>
