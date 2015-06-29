<?php

spl_autoload_register(
	function ($className) {
		$paths = [
			nZEDb_WWW . 'pages' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR,
			nZEDb_WWW . 'pages' . DIRECTORY_SEPARATOR,
		];

		foreach ($paths as $path) {
			$spec = str_replace('\\', DIRECTORY_SEPARATOR, $path . $className . '.php');

			if (file_exists($spec)) {
				require_once $spec;
				break;
			} else if (nZEDb_LOGAUTOLOADER) {
				var_dump($spec);
			}
		}
	}
);

?>
