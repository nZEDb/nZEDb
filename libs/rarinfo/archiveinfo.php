<?php

require_once dirname(__FILE__).'/archivereader.php';
require_once dirname(__FILE__).'/rarinfo.php';
require_once dirname(__FILE__).'/zipinfo.php';
require_once dirname(__FILE__).'/srrinfo.php';
require_once dirname(__FILE__).'/par2info.php';
require_once dirname(__FILE__).'/sfvinfo.php';
require_once dirname(__FILE__).'/szipinfo.php';

/**
 * ArchiveInfo class.
 *
 * This is an example implementation of a class that provides a facade for all of
 * the archive readers in the library. It will automatically detect any valid file
 * or data type, and parse its contents via delegation to the correct reader. The
 * API should then be identical to the reader's API for any given archive type.
 *
 * It also supports recursively inspecting the contents of any archives packed within
 * other archives (only if uncompressed) via extra methods that either allow chained
 * calls to the embedded archive objects or return a flat list of contents with the
 * source path info included as an extra field. If external clients are configured
 * to extract compressed files, these will be used recursively too.
 *
 * Note that since the class exposes the interfaces of different readers directly,
 * any application using it should add extra checks for expected properties, methods
 * and returned values, depending on the source type and reader interface. In other
 * words, apply duck typing.
 *
 * Example usage:
 *
 * <code>
 *
 *   // Load the archive file or data
 *   $archive = new ArchiveInfo;
 *   $archive->open('./foo.rar'); // or $archive->setData($data);
 *   if ($archive->error) {
 *     echo "Error: {$archive->error}\n";
 *     exit;
 *   }
 *
 *   // Check the archive type
 *   if ($archive->type != ArchiveInfo::TYPE_RAR) {
 *     echo "Source is not a RAR archive\n";
 *     // exit here or continue with duck typing
 *   }
 *
 *   // Check encryption
 *   if (!empty($archive->isEncrypted)) {
 *     echo "Archive is password encrypted\n";
 *     exit;
 *   }
 *
 *   // List the contents of all archives recursively
 *   foreach ($archive->getArchiveFileList() as $file) {
 *     if (isset($file['error'])) {
 *       echo "Error: {$file['error']} (in: {$file['source']})\n";
 *       continue; // skip recursion errors
 *     }
 *     if (!empty($file['pass'])) {
 *       echo "File is passworded: {$file['name']} (in: {$file['source']})\n";
 *       continue; // skip encrypted files
 *     }
 *     if (empty($file['compressed'])) {
 *       echo "Extracting uncompressed file: {$file['name']} from: {$file['source']}\n";
 *       $archive->saveFileData($file['name'], "./dir/{$file['name']}", $file['source']);
 *       // or $data = $archive->getFileData($file['name'], $file['source']);
 *     }
 *   }
 *
 * </code>
 *
 * @author     Hecks
 * @copyright  (c) 2010-2013 Hecks
 * @license    Modified BSD
 * @version    2.3
 * @link       https://github.com/zeebinz/rarinfo/
 */
class ArchiveInfo extends ArchiveReader
{
	/**#@+
	 * Supported archive types.
	 */
	const TYPE_NONE    = 0x0000;
	const TYPE_RAR     = 0x0002;
	const TYPE_ZIP     = 0x0004;
	const TYPE_SRR     = 0x0008;
	const TYPE_SFV     = 0x0010;
	const TYPE_PAR2    = 0x0020;
	const TYPE_SZIP    = 0x0040;

	/**#@-*/

	/**
	 * Source path label of the main archive.
	 */
	const MAIN_SOURCE  = 'main';

	/**
	 * The default list of the supported archive reader classes.
	 * @var array
	 */
	protected $readers = array(
		self::TYPE_RAR  => 'RarInfo',
		self::TYPE_SRR  => 'SrrInfo',
		self::TYPE_PAR2 => 'Par2Info',
		self::TYPE_ZIP  => 'ZipInfo',
		self::TYPE_SFV  => 'SfvInfo',
		self::TYPE_SZIP => 'SzipInfo',
	);

	/**
	 * The current archive file/data type.
	 * @var integer
	 */
	public $type = self::TYPE_NONE;

	/**
	 * Sets the list of supported archive reader classes for the current instance,
	 * overriding the defaults. The keys should be valid archive types:
	 *
	 *    $archive->setReaders(array(ArchiveInfo::TYPE_RAR => 'RarInfo'));
	 *
	 * If $recursive is set to true, this list will also be used for all embedded
	 * archives as well. This can be a bit unpredictable, so use with caution, but
	 * it's a convenient way to swap in custom readers or change their order.
	 *
	 * @param   array    $readers    list of reader classes keyed by archive type
	 * @param   boolean  $recursive  apply list to all embedded archives?
	 * @return  void
	 */
	public function setReaders(array $readers, $recursive=false)
	{
		$this->readers = $readers;
		$this->inheritReaders = $recursive;
		if ($recursive) {
			$this->archives = array();
		}
	}

	/**
	 * Sets the list of external clients configured for each reader type to allow
	 * extraction of compressed files. The keys should be valid archive types:
	 *
	 *    $archive->setExternalClients(array(
	 *        ArchiveInfo::TYPE_RAR => 'path_to_unrar_client',
	 *        ArchiveInfo::TYPE_ZIP => 'path_to_unzip_client',
	 *    ));
	 *
	 * Note that extracting embedded encrypted files is not currently supported
	 * due to recursion issues.
	 *
	 * @param   array  $clients  list of external clients
	 * @return  void
	 */
	public function setExternalClients(array $clients)
	{
		if ($this->reader && isset($clients[$this->type])) {
			$this->reader->setExternalClient($clients[$this->type]);
		}

		$this->externalClients = $clients;
		$this->archives = array();
		if ($this->reader) {
			unset($this->error);
		}
	}

	/**
	 * Sets the regex string (minus delimiters and brackets) for filtering valid
	 * archive extensions when inspecting archive contents recursively. This check
	 * can be disabled completely by setting the value to NULL.
	 *
	 * @param   string  $extensions  the regex of archive file extensions
	 * @return  void
	 */
	public function setArchiveExtensions($extensions)
	{
		$this->extensions = $extensions;
		$this->archives = array();
	}

	/**
	 * Convenience method that outputs a summary list of the archive information,
	 * useful for pretty-printing.
	 *
	 * When called with $full set to true, this method will also return a nested
	 * summary of all the embedded archive contents in the 'archives' field, keyed
	 * to the archive filenames.
	 *
	 * @param   boolean  $full  return a full summary?
	 * @return  array    archive summary
	 */
	public function getSummary($full=false)
	{
		$summary = array(
			'main_info' => isset($this->readers[$this->type]) ? $this->readers[$this->type] : 'Unknown',
			'main_type' => $this->type,
			'file_name' => $this->file,
			'file_size' => $this->fileSize,
			'data_size' => $this->dataSize,
			'use_range' => "{$this->start}-{$this->end}",
		);
		if ($this->reader) {
			$args = func_get_args();
			$summary += $this->__call('getSummary', $args);
		}
		if ($full && $this->containsArchive()) {
			$summary['archives'] = $this->getArchiveList(true); // recursive
		}
		if ($this->tempFiles) {
			$summary['temp_files'] = array_keys($this->tempFiles);
		}
		if ($this->error) {
			$summary['error'] = $this->error;
		}
		return $summary;
	}

	/**
	 * Returns a list of records for each of the files in the archive by delegation
	 * to the stored reader.
	 *
	 * @return  array|boolean  list of file records, or false if none are available
	 */
	public function getFileList()
	{
		if ($this->reader) {
			$args = func_get_args();
			return $this->__call('getFileList', $args);
		}

		return false;
	}

	/**
	 * Returns a list of the parsed data found in the source in human-readable
	 * format (for debugging purposes only).
	 *
	 * @return  array|boolean  parsed data, or false if none available
	 */
	public function getParsedData()
	{
		switch ($this->type) {
			case self::TYPE_RAR:
			case self::TYPE_SRR:
				return $this->reader->getBlocks();
			case self::TYPE_PAR2:
				return $this->reader->getPackets();
			case self::TYPE_ZIP:
				return $this->reader->getRecords();
			case self::TYPE_SZIP:
				return $this->reader->getHeaders();
			case self::TYPE_SFV:
				return $this->reader->getFileList();
			default:
				return false;
		}
	}

	/**
	 * Returns the stored archive reader instance for the file/data type.
	 *
	 * @return  ArchiveReader
	 */
	public function getReader()
	{
		return $this->reader;
	}

	/**
	 * Determines whether the current archive type can contain other archives
	 * through which it can search recursively.
	 *
	 * @return  boolean
	 */
	public function allowsRecursion()
	{
		return (bool) ($this->type & (self::TYPE_RAR | self::TYPE_ZIP | self::TYPE_SZIP));
	}

	/**
	 * Determines whether the current archive contains another archive.
	 *
	 * @return  boolean
	 */
	public function containsArchive()
	{
		$this->getArchiveList();
		return !empty($this->archives);
	}

	/**
	 * Determines whether the current reader can extract files using an external
	 * client.
	 *
	 * @return  boolean
	 */
	public function canExtract()
	{
		return $this->reader && (!empty($this->externalClients[$this->type])
		    || !empty($this->reader->externalClient));
	}

	/**
	 * Lists any embedded archives, either as raw ArchiveInfo objects or as file
	 * summaries, and caches the object list locally. The optional filtering of
	 * valid archive extensions can be disabled by first calling:
	 *
	 *    $archive->setArchiveExtensions(null);
	 *
	 * This will mean that all files in the archive will be inspected, regardless
	 * of their extensions - less efficient, more paranoid & probably buggier ;)
	 *
	 * @param   boolean  $summary  return file summaries?
	 * @return  array|boolean  list of stored objects/summaries, or false on error
	 */
	public function getArchiveList($summary=false)
	{
		if (!$this->reader || !$this->allowsRecursion())
			return false;

		if (empty($this->archives)) {
			$extensions = !empty($this->extensions) ? "/^({$this->extensions})$/" : false;
			foreach ($this->reader->getFileList() as $file) {
				if ($extensions && !preg_match($extensions, pathinfo($file['name'], PATHINFO_EXTENSION)))
					continue;
				if (($archive = $this->getArchive($file['name']))
				 && ($archive->type != self::TYPE_NONE || empty($archive->readers))
				) {
					$this->archives[$file['name']] = $archive;
				}
			}
			if (!empty($this->externalClients)) {
				$this->extractArchives();
			}
		}
		if ($summary) {
			$ret = array();
			foreach ($this->archives as $name => $archive) {
				$ret[$name] = $archive->getSummary(true); // recursive
			}
			return $ret;
		}

		return $this->archives;
	}

	/**
	 * Returns an ArchiveInfo object for an embedded archive file with the contents
	 * analyzed (initially without recursion). Calls to this method can also be
	 * chained together to navigate the tree, e.g.:
	 *
	 *    $rar->getArchive('parent.rar')->getArchive('child.zip')->getFileList();
	 *
	 * @param   string   $filename   the embedded archive filename
	 * @return  ArchiveInfo|boolean  false if an object can't be returned
	 */
	public function getArchive($filename)
	{
		if (!$this->reader || !$this->allowsRecursion())
			return false;

		// Check the cache first
		if (isset($this->archives[$filename]))
			return $this->archives[$filename];

		foreach ($this->reader->getFileList(true) as $file) {
			if ($file['name'] == $filename && isset($file['range'])) {

				// Create the new archive object
				$archive = new self;
				$archive->externalClients = $this->externalClients;
				$archive->extensions = $this->extensions;
				if ($this->inheritReaders) {
					$archive->setReaders($this->readers, true);
				}

				// Extract any compressed data to a temporary file if supported
				if ($this->canExtract() && !empty($file['compressed']) && empty($file['pass'])) {
					list($hash, $temp) = $this->getTempFileName("{$file['name']}:{$file['range']}");
					if (!isset($this->tempFiles[$hash])) {
						$this->reader->extractFile($file['name'], $temp);
						@chmod($temp, 0777);
						$this->tempFiles[$hash] = $temp;
					}
					if ($this->reader->error) {
						$archive->error = $this->reader->error;
						$archive->readers = array();
					} else {
						$archive->open($temp, $this->isFragment);
						$archive->isTemporary = true;
					}
					return $archive;
				}

				// Otherwise we shouldn't process any files that are unreadable
				if (!empty($file['compressed']) || !empty($file['pass'])) {
					$archive->readers = array();
				}

				// Try to parse the raw source file/data
				$range = explode('-', $file['range']);
				if ($this->file) {
					$archive->open($this->file, $this->isFragment, $range);
				} else {
					$archive->setData($this->data, $this->isFragment, $range);
				}

				// Make error messages more specific
				if (!empty($file['compressed'])) {
					$archive->error = 'The archive is compressed and cannot be read';
				}
				if (!empty($file['pass']) || !empty($archive->isEncrypted)) {
					$archive->error = 'The archive is encrypted and cannot be read';
				}

				return $archive;
			}
		}

		// Something went wrong
		return false;
	}

	/**
	 * Provides the contents of the current archive in a flat list, optionally
	 * recursing through all embedded archives as well, with a 'source' field
	 * added to each item that includes the archive source path.
	 *
	 * If $all is set to true, the file lists of all the supported archive types
	 * will be merged in the flat list, not just those that allow recursion. This
	 * should be used with caution, as the output varies between readers and the
	 * only guaranteed results are:
	 *
	 *     array('name'  => '...', 'source' => '...') or:
	 *     array('error' => '...', 'source' => '...')
	 *
	 * It's really just handy for inspecting all known file names in the laziest
	 * way possible, and not much more than that.
	 *
	 * @param   boolean  $recurse   list all archive contents recursively?
	 * @param   string   $all       include all supported archive file lists?
	 * @param   string   $source    [ignore, for internal use only]
	 * @return  array|boolean  the flat archive file list, or false on error
	 */
	public function getArchiveFileList($recurse=true, $all=false, $source=null)
	{
		if (!$this->reader) {return false;}
		$ret = array();

		// Start with the main parent
		if ($source == null) {
			$source = self::MAIN_SOURCE;
			if ($ret = $this->reader->getFileList()) {
				$ret = $this->flattenFileList($ret, $source, $all);
			}
		}

		// Merge each archive file list
		if ($recurse && $this->containsArchive()) {
			foreach ($this->getArchiveList() as $name => $archive) {

				// Only include the file lists of types that allow recursion?
				if (empty($archive->error) && !$all && !$archive->allowsRecursion())
					continue;
				$branch = $source.' > '.$name;

				// We should append any errors
				if ($archive->error || !($files = $archive->reader->getFileList())) {
					$error = $archive->error ? $archive->error : 'No files found';
					$ret[] = array('error' => $error, 'source' => $branch);
					continue;
				}

				// Otherwise merge recursively
				$ret = array_merge($ret, $this->flattenFileList($files, $branch, $all));
				if ($archive->containsArchive()) {
					$ret = array_merge($ret, $archive->getArchiveFileList(true, $all, $branch));
				}
			}
		}

		return $ret;
	}

	/**
	 * Retrieves the archive reader object described by the given source path string.
	 *
	 * @param   string  $source  archive source path of the file
	 * @return  ArchiveReader|boolean  archive reader object, or false on error
	 */
	public function getArchiveFromSource($source)
	{
		$this->getArchiveFileList(true);
		$source = explode(' > ', $source);
		foreach ($source as $file) {
			$archive = ($file == self::MAIN_SOURCE) ? $this : $archive->getArchive($file);
			if (!$archive) {break;}
		}

		return isset($archive) ? $archive : false;
	}

	/**
	 * Retrieves the raw data for the given filename and optionally the archive
	 * source (e.g. 'main' or 'main > child.rar', etc.).
	 *
	 * @param   string  $filename  name of the file to extract
	 * @param   string  $source    archive source path of the file
	 * @return  string|boolean  file data, or false on error
	 */
	public function getFileData($filename, $source=self::MAIN_SOURCE)
	{
		// Check that a valid data source is available
		if (!$this->reader || ($this->reader->data == '' && $this->reader->handle == null))
			return false;

		// Get the absolute start/end positions
		if (!($info = $this->getFileInfo($filename, $source)) || empty($info['range'])) {
			$in_source = $source ? " in: ({$source})" : '';
			$this->error = "Could not find file info for: ({$filename}){$in_source}";
			return false;
		}
		unset($this->error);

		return $this->reader->getRange(explode('-', $info['range']));
	}

	/**
	 * Saves the raw data for the given filename and optionally archive source path
	 * to the given destination (e.g. 'main' or 'main > child.rar', etc.).
	 *
	 * @param   string  $filename     name of the file to extract
	 * @param   string  $destination  full path of the file to create
	 * @param   string  $source       archive source path of the file
	 * @return  integer|boolean  number of bytes saved or false on error
	 */
	public function saveFileData($filename, $destination, $source=self::MAIN_SOURCE)
	{
		// Check that a valid data source is available
		if (!$this->reader || ($this->reader->data == '' && $this->reader->handle == null))
			return false;

		// Get the absolute start/end positions
		if (!($info = $this->getFileInfo($filename, $source)) || empty($info['range'])) {
			$in_source = $source ? " in: ({$source})" : '';
			$this->error = "Could not find file info for: ({$filename}){$in_source}";
			return false;
		}
		unset($this->error);

		return $this->reader->saveRange(explode('-', $info['range']), $destination);
	}

	/**
	 * Extracts a compressed or encrypted file using one of the configured external
	 * clients, optionally returning the data or saving it to file.
	 *
	 * Note that this method will fail with uncompressed or unencrypted files in
	 * embedded archives - use getFileData() or saveFileData() instead.
	 *
	 * @param   string  $filename     name of the file to extract
	 * @param   string  $destination  full path of the file to create
	 * @param   string  $password     password to use for decryption
	 * @param   string  $source       archive source path of the file to extract
	 * @return  mixed   extracted data, number of bytes saved or false on error
	 */
	public function extractFile($filename, $destination=null, $password=null, $source=self::MAIN_SOURCE)
	{
		// Check that a valid reader is available
		if (!($archive = $this->getArchiveFromSource($source)) || !($reader = $archive->reader)) {
			$this->error = "Not a valid archive source: {$source}";
			return false;
		}
		if (!method_exists($reader, 'extractFile')) {
			$this->error = get_class($reader).' does not support the extractFile() method';
			return false;
		}

		// Get the result of the extraction
		$result = $reader->extractFile($filename, $destination, $password);
		if ($reader->error) {
			$this->error = $reader->error;
			return false;
		}
		unset($this->error);

		return $result;
	}

	/**
	 * Class destructor, cleanly removes any archive reader references.
	 *
	 * @return  void
	 */
	public function __destruct()
	{
		$this->reset();
		parent::__destruct();
	}

	/**
	 * Returns the position of the first archive marker/signature in the stored
	 * data or file by delegation to the stored reader.
	 *
	 * @return  mixed  Marker position, or false if marker is missing
	 */
	public function findMarker()
	{
		if ($this->reader)
			return $this->reader->findMarker();

		return false;
	}

	/**
	 * Magic method for accessing the properties of the stored reader.
	 *
	 * @param   string  $name  the property name
	 * @return  mixed   the property value
	 */
	public function __get($name)
	{
		if ($this->reader && isset($this->reader->$name))
			return $this->reader->$name;

		return parent::__get($name);
	}

	/**
	 * Magic method for testing whether properties of the stored reader are set.
	 * Note that if called via empty(), if the method returns TRUE a second call
	 * is made to __get() to test if the actual value is false.
	 *
	 * @param   string  $name  the property name
	 * @return  boolean
	 */
	public function __isset($name)
	{
		if ($this->reader)
			return isset($this->reader->$name);

		return false;
	}

	/**
	 * Magic method for delegating method calls to the stored reader.
	 *
	 * @param   string  $method  the method name
	 * @param   array   $args    the method arguments
	 * @return  mixed   result of the delegated method call
	 * @throws  BadMethodCallException
	 */
	public function __call($method, $args)
	{
		if (!$this->reader)
			throw new BadMethodCallException(get_class($this)."::$method() is not defined");

		switch (count($args)) {
			case 0:
				return $this->reader->$method();
			case 1:
				return $this->reader->$method($args[0]);
			case 2:
				return $this->reader->$method($args[0], $args[1]);
			case 3:
				return $this->reader->$method($args[0], $args[1], $args[2]);
			default:
				return call_user_func_array(array($this->reader, $method), $args);
		}
	}

	/**
	 * The stored archive reader instance.
	 * @var ArchiveReader
	 */
	protected $reader;

	/**
	 * Cached list of any embedded archive objects.
	 * @var array
	 */
	protected $archives = array();

	/**
	 * Should any embedded archives inherit the readers list from this instance?
	 * @var boolean
	 */
	protected $inheritReaders = false;

	/**
	 * List of any external clients to use for extraction.
	 * @var array
	 */
	protected $externalClients = array();

	/**
	 * Is the current archive being processed from a temporary file?
	 * @var boolean
	 */
	protected $isTemporary = false;

	/**
	 * The regex for filtering any valid archive extensions.
	 * @var string
	 */
	protected $extensions = 'rar|r[0-9]+|zip|srr|par2|sfv|7z|[0-9]+';

	/**
	 * Parses the source file/data by delegation to one of the configured readers,
	 * or returns false if the source is not a supported type.
	 *
	 * @return  boolean  false if parsing fails
	 */
	protected function analyze()
	{
		// Create a reader to handle the file/data
		if ($this->createReader() === false || $this->findMarker() === false) {
			$this->error = 'Source is not a supported archive type';
			$this->close();
			return false;
		}

		// Delegate some properties to the reader
		unset($this->markerPosition);
		unset($this->fileCount);
		unset($this->error);

		// Let the reader handle any files
		if ($this->file) {
			$this->close();
		}

		return true;
	}

	/**
	 * Creates a reader instance for parsing the source file/data and stores it
	 * locally for any later delegation.
	 *
	 * Each reader in the configured order tries to parse the source file/data and
	 * find a valid marker/signature for its type. Where more than one marker is
	 * present - such as with embedded archives - the reader that finds the earliest
	 * marker will be used as the delegate.
	 *
	 * @return  boolean  false if no reader could parse the source
	 */
	protected function createReader()
	{
		$range = array($this->start, $this->end);

		foreach ($this->readers as $type => $class) {

			// Analyze the source with a new reader
			$reader = new $class;
			if ($this->file) {
				$reader->open($this->file, $this->isFragment, $range);
			} else {
				$reader->setData($this->data, $this->isFragment, $range);
			}
			if ($reader->error) {continue;}

			// Store the reader with the earliest marker
			if (($marker = $reader->findMarker()) !== false) {
				$start = !isset($start) ? $marker : $start;
				if ($marker <= $start) {
					$start = $marker;
					if (!empty($this->externalClients[$type])) {
						$reader->setExternalClient($this->externalClients[$type]);
					}
					$this->reader = $reader;
					$this->type = $type;
				}
				if ($start === 0) {break;}
			}
		}

		return isset($this->reader);
	}

	/**
	 * Returns information for the given filename and optionally archive source
	 * in the current file/data.
	 *
	 * @param   string  $filename  the filename to search
	 * @param   string  $source    archive source path of the file
	 * @return  array|boolean  the file info or false on error
	 */
	protected function getFileInfo($filename, $source=self::MAIN_SOURCE)
	{
		if (strpos($source, self::MAIN_SOURCE) !== 0) {
			$source = self::MAIN_SOURCE.' > '.$source;
		}
		foreach ($this->getArchiveFileList(true) as $file) {
			if (!empty($file['name']) && empty($file['is_dir'])
			 && $file['name'] == $filename && $file['source'] == $source
			) {
				return $file;
			}
		}

		return false;
	}

	/**
	 * Helper method that flattens a file list that may have children, removes keys
	 * and re-indexes, then adds a source path field to each item.
	 *
	 * @param   array    $files   the file list to flatten
	 * @param   string   $source  the current source path info
	 * @param   boolean  $all     should any child lists be included?
	 * @return  array  the flat file list
	 */
	protected function flattenFileList(array $files, $source, $all=false)
	{
		$files = array_values($files);
		$children = array();
		foreach ($files as &$file) {
			$file['source'] = $source;
			if ($source != self::MAIN_SOURCE) {
				unset($file['next_offset']);
			}
			if ($all && !empty($file['files'])) foreach ($file['files'] as $child) {
				$child['source'] = $source.' > '.$file['name'];
				unset($child['next_offset']);
				$children[] = $child;
			}
		}
		if (!empty($children)) {
			$files = array_merge($files, $children);
		}

		return $files;
	}

	/**
	 * Extracts any embedded archives that contain compressed files using the
	 * configured external clients to allow recursive inspection and extraction.
	 *
	 * @return  boolean  false on error
	 */
	protected function extractArchives()
	{
		if (!$this->reader || !$this->canExtract())
			return false;

		foreach ($this->archives as $name => $archive) {
			if ($archive->isTemporary || !$archive->canExtract())
				continue;

			if ($files = $archive->reader->getFileList()) foreach ($files as $file) {
				if (!empty($file['compressed']) && empty($file['pass'])) {
					list($hash, $temp) = $this->getTempFileName("{$name}:{$archive->start}-{$archive->end}");
					if (!isset($this->tempFiles[$hash])) {
						$archive->reader->saveRange(array($archive->start, $archive->end), $temp);
						@chmod($temp, 0777);
						$this->tempFiles[$hash] = $temp;
					}
					$archive->open($temp, $this->isFragment);
					$archive->isTemporary = true;
					continue 2;
				}
			}
		}

		return true;
	}

	/**
	 * Resets the instance variables before parsing new data.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		$this->reader = null;
		foreach ($this->archives as $archive) {
			$archive->reset();
		}
		parent::reset();
		$this->archives = array();
		$this->type = self::TYPE_NONE;
		$this->isTemporary = false;
	}

} // End ArchiveInfo class
