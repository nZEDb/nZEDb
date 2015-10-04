<?php
/**
 *
 * @param string $class The fully-qualified class name.
 *
 * @return void
 */
spl_autoload_register(
	function($class) {
		// base directory for the namespace prefix
		$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

		// if the file exists, require it
		if (file_exists($file) && is_readable($file)) {
			require_once $file;
		} else {
			// Let's try the old style format with 'class.' prefixed to the lower-cased filename.
			// This bypasses the concept of a Vendor namespace as older libs don't use it.

			$filename    = 'class.' . strtolower($class) . '.php';
			$DirIterator = new \DirectoryIterator($base_dir);
			foreach ($DirIterator as $fileInfo) {
				if ($fileInfo->isDir() && !$fileInfo->isDot()) {
					$file = $fileInfo->getPathname() . DIRECTORY_SEPARATOR . $filename;
					if (file_exists($file) && is_readable($file)) {
						require_once $file;
					}
				}
			}
		}
	}
);

spl_autoload_register(
	function ($class) {
		// Only continue if the class is in our namespace.
		if (strpos($class, 'libs\\') === 0) {
			// Replace namespace separators with directory separators in the class name, append
			// with .php
			$file = nZEDb_ROOT . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

			// if the file exists, require it
			if (file_exists($file)) {
				require_once $file;
			}
		}
	}
);

?>
