<?php
use Cake\Core\Configure;

$appDescription = 'nZEDb, the usenet indexer';

//$this->Html->css(['base', 'style', 'nzedb'], ['block' => true])

//Bootstrap 3.3.7
$this->Html->css('AdminLTE./bower_components/bootstrap/dist/css/bootstrap.min', ['block' => true]);

// Font Awesome
$this->Html->css('AdminLTE./bower_components/font-awesome/css/font-awesome.min', ['block' => true]);

// Ionicons
$this->Html->css('AdminLTE./bower_components/Ionicons/css/ionicons.min', ['block' => true]);

// Theme style
$this->Html->css(['AdminLTE.AdminLTE.css'], ['block' => true]);

// AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load.
$this->Html->css('AdminLTE.skins/skin-' . Configure::read('Theme.skin') . '.min', ['block' => true]);

// jQuery 3
$this->Html->script('AdminLTE./bower_components/jquery/dist/jquery.min', ['block' => true]);

// Bootstrap 3.3.7
$this->Html->script('AdminLTE./bower_components/bootstrap/dist/js/bootstrap.min', ['block' => true]);

// AdminLTE App
$this->Html->script('AdminLTE.adminlte.min', ['block' => true]);

// Slimscroll
$this->Html->script('AdminLTE./bower_components/jquery-slimscroll/jquery.slimscroll.min',
	['block' => true]);

// FastClick
$this->Html->script('AdminLTE./bower_components/fastclick/lib/fastclick', ['block' => true]);

?>

<?= $this->fetch('content') ?>
