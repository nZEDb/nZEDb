<?php

spl_autoload_register(
	function ($className) {
		$spec = str_replace('\\',
							DIRECTORY_SEPARATOR,
							nZEDb_WWW . 'pages' . $className . '.php');

		if (file_exists($spec)) {
			require_once $spec;
		} else {
			if (nZEDb_LOGAUTOLOADER) {
				var_dump($spec);
			}
		}
	}
);

?>
