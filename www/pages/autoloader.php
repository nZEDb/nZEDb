<?php

spl_autoload_register(
	function ($className) {
		$spec = str_replace('\\', DS, nZEDb_WWW . 'pages' . DS . $className . '.php');
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
