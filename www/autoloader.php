<?php
require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . 'automated.config.php');
require_once realpath(dirname(__DIR__) . DS . 'autoloaders.php');

spl_autoload_register(
	function ($className) {
		$paths = [
			nZEDb_WWW . 'pages' . DS,
			nZEDb_WWW . 'pages' . DS . 'admin' . DS,
			nZEDb_WWW . 'pages' . DS . 'install' . DS,
		];

		foreach ($paths as $path) {
			$spec = str_replace('\\', DS, $path . $className . '.php');

			if (file_exists($spec)) {
				require_once $spec;
				break;
			} else if (nZEDb_LOGAUTOLOADER) {
				var_dump($spec);
			}
		}
	},
	true
);

?>
