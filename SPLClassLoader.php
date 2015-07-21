<?php

/**
 * SplClassLoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 *
 *     // Example which loads classes for the Doctrine Common package in the
 *     // Doctrine\Common namespace.
 *     $classLoader = new SplClassLoader('Doctrine\Common', '/path/to/doctrine');
 *     $classLoader->register();
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman S. Borschel <roman@code-factory.org>
 * @author Matthew Weier O'Phinney <matthew@zend.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class SplClassLoader
{
	private $_fileExtension = '.php';
	private $_namespace;
	private $_includePath;
	private $_namespaceSeparator = '\\';

	/**
	 * Creates a new <tt>SplClassLoader</tt> that loads classes of the
	 * specified namespace.
	 *
	 * @param string $ns The namespace to use.
	 */
	public function __construct($ns = null, array $includePath = [])
	{
		$this->_namespace = $ns;
		foreach ($includePath as &$path) {
			if (substr($path, -1) == '/') {
				$path = substr($path, 0, -1);
			}
		}
		$this->_includePath = $includePath;
	}

	/**
	 * Sets the namespace separator used by classes in the namespace of this class loader.
	 *
	 * @param string $sep The separator to use.
	 */
	public function setNamespaceSeparator($sep)
	{
		$this->_namespaceSeparator = $sep;
	}

	/**
	 * Gets the namespace seperator used by classes in the namespace of this class loader.
	 *
	 * @return string
	 */
	public function getNamespaceSeparator()
	{
		return $this->_namespaceSeparator;
	}

	/**
	 * Sets the base include path for all class files in the namespace of this class loader.
	 *
	 * @param string $includePath
	 */
	public function setIncludePath($includePath)
	{
		$this->_includePath = $includePath;
	}

	/**
	 * Gets the base include path for all class files in the namespace of this class loader.
	 *
	 * @return string $includePath
	 */
	public function getIncludePath()
	{
		return $this->_includePath;
	}

	/**
	 * Sets the file extension of class files in the namespace of this class loader.
	 *
	 * @param string $fileExtension
	 */
	public function setFileExtension($fileExtension)
	{
		$this->_fileExtension = $fileExtension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this class loader.
	 *
	 * @return string $fileExtension
	 */
	public function getFileExtension()
	{
		return $this->_fileExtension;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register()
	{
		spl_autoload_register([$this, 'loadClass']);
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister()
	{
		spl_autoload_unregister([$this, 'loadClass']);
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $className The name of the class to load.
	 * @return void
	 */
	public function loadClass($className)
	{
		if (preg_match('#\\\#', $className)) {
			// Looks like it's a namespaced class. just clean pathsepearator for now.
			$className = str_replace('#\\#', DIRECTORY_SEPARATOR, $className);
		}
		if ($className == 'Smarty') {
			require_once SMARTY_DIR . 'Smarty.class.php';
			return;
		}
		if ($this->_namespace === null || $this->_namespace . $this->_namespaceSeparator === substr($className, 0, strlen($this->_namespace . $this->_namespaceSeparator))) {
			$fileName = '';
			$namespace = '';
			if (strtolower(substr($className, 0, 6)) !== 'smarty') {
				if (false !== ($lastNsPos = strripos($className, $this->_namespaceSeparator))) {
					$namespace = substr($className, 0, $lastNsPos);
					$className = substr($className, $lastNsPos + 1);
					$fileName = str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
				}
				$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;
			} else {
				$fileName = strtolower($className) . '.php';
			}

			if (!empty($this->_includePath)) {
				foreach ($this->_includePath as $path) {
					$spec = $path . DIRECTORY_SEPARATOR . $fileName;
					if (file_exists($spec)) {
						require_once $spec;
						return;
					}
				}
			}
			require_once $fileName;
		}
	}
}
