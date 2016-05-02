<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * This layout is used to render error pages in both development and production. It is recommended
 * that you maintain a separate, simplified layout for rendering errors that does not involve any
 * complex logic or dynamic data, which could potentially trigger recursive errors.
 */
use lithium\core\Libraries;
$path = Libraries::get(true, 'path');
?>
<!doctype html>
<html>
<head>
	<?php echo $this->html->charset(); ?>
	<title>Unhandled exception</title>
	<?php echo $this->html->style(array('bootstrap.min', 'lithified', 'debug')); ?>
	<?php echo $this->scripts(); ?>
	<?php echo $this->styles(); ?>
	<?php echo $this->html->link('Icon', null, array('type' => 'icon')); ?>
</head>
<body class="lithified">
	<div class="container">
		<div class="masthead">
			<ul class="nav nav-pills pull-right">
				<li>
					<a href="http://li3.me/docs/manual/quickstart">Quickstart</a>
				</li>
				<li>
					<a href="http://li3.me/docs/manual">Manual</a>
				</li>
				<li>
					<a href="http://li3.me/docs/lithium">API</a>
				</li>
				<li>
					<a href="http://li3.me/">More</a>
				</li>
			</ul>
			<a href="http://li3.me/"><h3>&#10177;</h3></a>
		</div>

		<hr>

		<div class="row-fluid">
			<h1>An unhandled exception was thrown</h1>
			<h3>Configuration</h3>
			<p>
				This layout can be changed by modifying
				<code><?="{$path}/views/layouts/error.html.php";?></code>
			</p>
			<p>
				To modify your error-handling configuration, see
				<code><?="{$path}/config/bootstrap/errors.php";?></code>
			</p>
		</div>

		<div class="content">
			<?php echo $this->content(); ?>
		</div>

		<hr>

		<div class="footer">
			<p>&copy; Union Of RAD <?php echo date('Y') ?></p>
		</div>
	</div>
</body>
</html>