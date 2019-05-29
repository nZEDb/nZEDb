<?php use Cake\Core\Configure; ?>
<?= $this->Html->docType('html5') ?>
<?= $this->element('default-head') ?>

<body class="hold-transition skin-<?= Configure::read('Theme.skin') ?> sidebar-mini">
<div class="wrapper">

	<header class="main-header">
		<!-- Logo -->
		<a href="<?= $this->Url->build('/') ?>" class="logo">
			<!-- logo for regular state and mobile devices -->
			<span class="logo-lg"><?= Configure::read('Theme.logo.large') ?></span>
		</a>
		<!-- Header Navbar: style can be found in header.less -->
		<?= $this->element('nav-top') ?>
	</header>

	<?php if ($this->Identity->isLoggedIn()) :
		echo $this->element('aside-main-sidebar');
	endif; ?>

	<!-- Content Wrapper. Contains page content -->
	<div class="content-wrapper">

		<?= $this->Flash->render() ?>

		<?= $this->Flash->render('auth') ?>

		<!--[if lt IE 11]>
		<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please
			<a href="https://brave.com/nze714">upgrade your browser</a> to improve your experience.</p>
		<![endif]-->

		<?= $this->fetch('content') ?>

	</div>
	<!-- /.content-wrapper -->

	<?= $this->element('footer') ?>

	<!-- Control Sidebar -->
	<?= $this->element('aside-control-sidebar') ?>
	<!-- /.control-sidebar -->

	<!-- Add the sidebar's background. This div must be placed
			 immediately after the control sidebar -->
	<div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->

<!-- jQuery 3 -->
<?= $this->Html->script('AdminLTE./bower_components/jquery/dist/jquery.min') ?>
<!-- Bootstrap 3.3.7 -->
<?= $this->Html->script('AdminLTE./bower_components/bootstrap/dist/js/bootstrap.min') ?>
<!-- AdminLTE App -->
<?= $this->Html->script('AdminLTE.adminlte.min') ?>
<!-- Slimscroll -->
<?= $this->Html->script('AdminLTE./bower_components/jquery-slimscroll/jquery.slimscroll.min') ?>
<!-- FastClick -->
<?= $this->Html->script('AdminLTE./bower_components/fastclick/lib/fastclick') ?>

<?= $this->fetch('scriptBottom') ?>

<script type="text/javascript">
		$(document).ready(function(){
				$(".navbar .menu").slimscroll({
						height: "200px",
						alwaysVisible: false,
						size: "3px"
				}).css("width", "100%");

		let a = $('a[href="<?= $this->Url->build() ?>"]');
		if (!a.parent().hasClass('treeview') && !a.parent().parent().hasClass('pagination')) {
						a.parent().addClass('active').parents('.treeview').addClass('active');
				}
				
		});
</script>

</body>
</html>
