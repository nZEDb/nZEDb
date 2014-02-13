<?php

spl_autoload_register(
	function ($className)
	{
		if ($className == 'Smarty') {
			require_once SMARTY_DIR . 'Smarty.class.php';
			return;
		}

		$paths = array(nZEDb_WWW . 'pages', SMARTY_DIR, SMARTY_DIR . 'plugins', SMARTY_DIR . 'sysplugins');
		foreach ($paths as $path)
		{
			$spec = $path . DIRECTORY_SEPARATOR  . $className . '.php';
			if (file_exists($spec)) {
				require_once $spec;
				return;
			}
		}
	}
);

?>
