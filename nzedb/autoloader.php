<?php
if (file_exists(realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php')) {
	require_once realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';
}

/**
 * An example of a project-specific implementation.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
 * from /path/to/project/src/Baz/Qux.php:
 *
 *      new \Foo\Bar\Baz\Qux;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function($class) {

	// project-specific namespace prefix
	$prefix = 'nzedb\\';

	// base directory for the namespace prefix
	$base_dir = __DIR__ . DIRECTORY_SEPARATOR;

	// If no namespace, add default;
	$class = preg_match('#\\\#', $class) ? $class : $prefix . $class;

	// does the class use the namespace prefix?
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}

	// get the relative class name
	$relative_class = substr($class, $len);

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

	// if the file exists, require it
	if (file_exists($file)) {
		require_once $file;
	} elseif (nZEDb_LOGAUTOLOADER) {
		var_dump($file);
	}
});

?>
