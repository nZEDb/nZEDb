<?php

require_once dirname(__FILE__) . '/archivereader.php';
require_once dirname(__FILE__) . '/pipereader.php';

/**
 * RarInfo class.
 *
 * A simple class for inspecting RAR file data and listing information about
 * the archive contents in pure PHP (no external dependencies). Data can be
 * streamed from a file or loaded directly from memory.
 *
 * Example usage:
 *
 * <code>
 *
 *   // Load the RAR file or data
 *   $rar = new RarInfo;
 *   $rar->open('./foo.rar'); // or $rar->setData($data);
 *   if ($rar->error) {
 *     echo "Error: {$rar->error}\n";
 *     exit;
 *   }
 *
 *   // Check encryption
 *   if ($rar->isEncrypted) {
 *     echo "Archive is password encrypted\n";
 *     exit;
 *   }
 *
 *   // Process the file list
 *   $files = $rar->getFileList();
 *   foreach ($files as $file) {
 *     if ($file['pass'] == true) {
 *       echo "File is passworded: {$file['name']}\n";
 *     }
 *     if ($file['compressed'] == false) {
 *       echo "Extracting uncompressed file: {$file['name']}\n";
 *       $rar->saveFileData($file['name'], "./destination/{$file['name']}");
 *       // or $data = $rar->getFileData($file['name']);
 *     }
 *   }
 *
 * </code>
 *
 * For RAR file fragments - i.e. that may not contain a valid Marker Block - add
 * TRUE as the second parameter for the open() or setData() methods to skip the
 * error messages and allow a forced search for valid File Header blocks.
 *
 * @author     Hecks
 * @copyright  (c) 2010-2014 Hecks
 * @license    Modified BSD
 * @version    5.6
 */
class RarInfo extends ArchiveReader
{
	// ------ Class constants -----------------------------------------------------

	/**#@+
	 * RAR 1.5 - 4.x archive format values (thanks to Marko Kreen)
	 */

	// Block types
	const BLOCK_MARK          = 0x72;
	const BLOCK_MAIN          = 0x73;
	const BLOCK_FILE          = 0x74;
	const BLOCK_OLD_COMMENT   = 0x75;
	const BLOCK_OLD_EXTRA     = 0x76;
	const BLOCK_OLD_SUB       = 0x77;
	const BLOCK_OLD_RECOVERY  = 0x78;
	const BLOCK_OLD_AUTH      = 0x79;
	const BLOCK_SUB           = 0x7a;
	const BLOCK_ENDARC        = 0x7b;
	const BLOCK_NULL          = 0x00;

	// Flags for BLOCK_MAIN
	const MAIN_VOLUME         = 0x0001;
	const MAIN_COMMENT        = 0x0002;
	const MAIN_LOCK           = 0x0004;
	const MAIN_SOLID          = 0x0008;
	const MAIN_NEWNUMBERING   = 0x0010;
	const MAIN_AUTH           = 0x0020;
	const MAIN_RECOVERY       = 0x0040;
	const MAIN_PASSWORD       = 0x0080;
	const MAIN_FIRSTVOLUME    = 0x0100;
	const MAIN_ENCRYPTVER     = 0x0200;

	// Flags for BLOCK_FILE
	const FILE_SPLIT_BEFORE   = 0x0001;
	const FILE_SPLIT_AFTER    = 0x0002;
	const FILE_PASSWORD       = 0x0004;
	const FILE_COMMENT        = 0x0008;
	const FILE_SOLID          = 0x0010;
	const FILE_DICTMASK       = 0x00e0;
	const FILE_DICT64         = 0x0000;
	const FILE_DICT128        = 0x0020;
	const FILE_DICT256        = 0x0040;
	const FILE_DICT512        = 0x0060;
	const FILE_DICT1024       = 0x0080;
	const FILE_DICT2048       = 0x00a0;
	const FILE_DICT4096       = 0x00c0;
	const FILE_DIRECTORY      = 0x00e0;
	const FILE_LARGE          = 0x0100;
	const FILE_UNICODE        = 0x0200;
	const FILE_SALT           = 0x0400;
	const FILE_VERSION        = 0x0800;
	const FILE_EXTTIME        = 0x1000;
	const FILE_EXTFLAGS       = 0x2000;

	// Flags for BLOCK_ENDARC
	const ENDARC_NEXT_VOLUME  = 0x0001;
	const ENDARC_DATACRC      = 0x0002;
	const ENDARC_REVSPACE     = 0x0004;
	const ENDARC_VOLNR        = 0x0008;

	// Flags for all blocks
	const SKIP_IF_UNKNOWN     = 0x4000;
	const LONG_BLOCK          = 0x8000;

	// Subtypes for BLOCK_SUB
	const SUBTYPE_COMMENT     = 'CMT';
	const SUBTYPE_ACL         = 'ACL';
	const SUBTYPE_STREAM      = 'STM';
	const SUBTYPE_UOWNER      = 'UOW';
	const SUBTYPE_AUTHVER     = 'AV';
	const SUBTYPE_RECOVERY    = 'RR';
	const SUBTYPE_OS2EA       = 'EA2';
	const SUBTYPE_BEOSEA      = 'EABE';

	// Compression methods
	const METHOD_STORE        = 0x30;
	const METHOD_FASTEST      = 0x31;
	const METHOD_FAST         = 0x32;
	const METHOD_NORMAL       = 0x33;
	const METHOD_GOOD         = 0x34;
	const METHOD_BEST         = 0x35;

	// OS types
	const OS_MSDOS = 0;
	const OS_OS2   = 1;
	const OS_WIN32 = 2;
	const OS_UNIX  = 3;
	const OS_MACOS = 4;
	const OS_BEOS  = 5;

	/**#@-*/

	/**
	 * Size in bytes of the main part of each block header.
	 */
	const HEADER_SIZE = 7;

	/**
	 * Format for unpacking the main part of each block header.
	 */
	const FORMAT_BLOCK_HEADER = 'vhead_crc/Chead_type/vhead_flags/vhead_size';

	/**
	 * Format for unpacking the remainder of a File block header.
	 */
	const FORMAT_FILE_HEADER = 'Vpack_size/Vunp_size/Chost_os/Vfile_crc/Vftime/Cunp_ver/Cmethod/vname_size/Vattr';

	/**
	 * RAR archive format version.
	 */
	const FMT_RAR14 = '1.4';
	const FMT_RAR15 = '1.5';
	const FMT_RAR50 = '5.0';

	/**#@+
	 * RAR 5.0 archive format values
	 */

	// Block types
	const R50_BLOCK_MAIN         = 0x01;
	const R50_BLOCK_FILE         = 0x02;
	const R50_BLOCK_SERVICE      = 0x03;
	const R50_BLOCK_CRYPT        = 0x04;
	const R50_BLOCK_ENDARC       = 0x05;

	// Flags for all block types
	const R50_HAS_EXTRA          = 0x0001;
	const R50_HAS_DATA           = 0x0002;
	const R50_SKIP_IF_UNKNOWN    = 0x0004;
	const R50_SPLIT_BEFORE       = 0x0008;
	const R50_SPLIT_AFTER        = 0x0010;
	const R50_IS_CHILD           = 0x0020;
	const R50_INHERITED          = 0x0040;

	// Service block types
	const R50_SERVICE_COMMENT    = 'CMT';
	const R50_SERVICE_QUICKOPEN  = 'QO';
	const R50_SERVICE_ACL        = 'ACL';
	const R50_SERVICE_STREAM     = 'STM';
	const R50_SERVICE_RECOVERY   = 'RR';

	// Flags for R50_BLOCK_MAIN
	const R50_MAIN_VOLUME        = 0x0001;
	const R50_MAIN_VOLNUMBER     = 0x0002;
	const R50_MAIN_SOLID         = 0x0004;
	const R50_MAIN_RECOVERY      = 0x0008;
	const R50_MAIN_LOCK          = 0x0010;

	// Flags for R50_BLOCK_FILE
	const R50_FILE_DIRECTORY     = 0x0001;
	const R50_FILE_UTIME         = 0x0002;
	const R50_FILE_CRC32         = 0x0004;
	const R50_FILE_UNPUNKNOWN    = 0x0008;

	// Flags for R50_BLOCK_ENDARC
	const R50_ENDARC_NEXT_VOLUME = 0x0001;

	// Extra record types for R50_BLOCK_MAIN
	const R50_MEXTRA_LOCATOR     = 0x01;

	// Flags for R50_MEXTRA_LOCATOR
	const R50_MEXTRA_LOC_QLIST   = 0x0001;
	const R50_MEXTRA_LOC_RR      = 0x0002;

	// Extra record types for R50_BLOCK_FILE
	const R50_FEXTRA_CRYPT       = 0x01;
	const R50_FEXTRA_HASH        = 0x02;
	const R50_FEXTRA_HTIME       = 0x03;
	const R50_FEXTRA_VERSION     = 0x04;
	const R50_FEXTRA_REDIR       = 0x05;
	const R50_FEXTRA_UOWNER      = 0x06;
	const R50_FEXTRA_SUBDATA     = 0x07;

	// Flags for R50_FEXTRA_HTIME
	const R50_FEXTRA_HT_UNIX     = 0x0001;
	const R50_FEXTRA_HT_MTIME    = 0x0002;
	const R50_FEXTRA_HT_CTIME    = 0x0004;
	const R50_FEXTRA_HT_ATIME    = 0x0008;

	// Compression methods
	const R50_METHOD_STORE       = 0;
	const R50_METHOD_FASTEST     = 1;
	const R50_METHOD_FAST        = 2;
	const R50_METHOD_NORMAL      = 3;
	const R50_METHOD_GOOD        = 4;
	const R50_METHOD_BEST        = 5;

	// OS types
	const R50_OS_WIN32 = 0;
	const R50_OS_UNIX  = 1;

	/**#@-*/

	// ------ Instance variables and methods ---------------------------------------

	/**
	 * Signature for the RAR Marker block.
	 * @var string
	 */
	protected $markerBlock = "\x52\x61\x72\x21\x1a\x07\x00";

	/**
	 * Signature for RAR 5.0 format archives.
	 * @var string
	 */
	protected $markerRar50 = "\x52\x61\x72\x21\x1a\x07\x01\x00";

	/**
	 * List of block names corresponding to block types.
	 * @var array
	 */
	protected $blockNames = array(
		// RAR 1.5 - 4.x
		self::BLOCK_MARK          => 'Marker',
		self::BLOCK_MAIN          => 'Archive',
		self::BLOCK_FILE          => 'File',
		self::BLOCK_OLD_COMMENT   => 'Old Style Comment',
		self::BLOCK_OLD_EXTRA     => 'Old Style Extra Info',
		self::BLOCK_OLD_SUB       => 'Old Style Subblock',
		self::BLOCK_OLD_RECOVERY  => 'Old Style Recovery Record',
		self::BLOCK_OLD_AUTH      => 'Old Style Archive Authenticity',
		self::BLOCK_SUB           => 'Subblock',
		self::BLOCK_ENDARC        => 'Archive End',
		self::BLOCK_NULL          => 'Null Block',
		// RAR 5.0
		self::R50_BLOCK_MAIN      => 'Archive',
		self::R50_BLOCK_FILE      => 'File',
		self::R50_BLOCK_SERVICE   => 'Service',
		self::R50_BLOCK_CRYPT     => 'Archive Encryption',
		self::R50_BLOCK_ENDARC    => 'Archive End',
	);

	/**
	 * List of the names corresponding to Subblock types (0x7a).
	 * @var array
	 */
	protected $subblockNames = array(
		self::SUBTYPE_COMMENT   => 'Comment',
		self::SUBTYPE_ACL       => 'Access Control List',
		self::SUBTYPE_STREAM    => 'Stream',
		self::SUBTYPE_UOWNER    => 'Owner/Group Information',
		self::SUBTYPE_AUTHVER   => 'Authenticity Verification',
		self::SUBTYPE_RECOVERY  => 'Recovery Record',
		self::SUBTYPE_OS2EA     => 'OS2EA',
		self::SUBTYPE_BEOSEA    => 'BEOSEA',
	);

	/**
	 * List of the names corresponding to RAR 5.0 Service types.
	 * @var array
	 */
	protected $serviceNames = array(
		self::R50_SERVICE_COMMENT    => 'Archive Comment',
		self::R50_SERVICE_QUICKOPEN  => 'Archive Quick Open',
		self::R50_SERVICE_ACL        => 'Access Control List',
		self::R50_SERVICE_STREAM     => 'NTFS Stream',
		self::R50_SERVICE_RECOVERY   => 'Recovery Record',
	);

	/**
	 * Is the volume attribute set for the archive?
	 * @var boolean
	 */
	public $isVolume = false;

	/**
	 * Is authenticity information present?
	 * @var boolean
	 */
	public $hasAuth = false;

	/**
	 * Is a recovery record present?
	 * @var boolean
	 */
	public $hasRecovery = false;

	/**
	 * Is the archive encrypted with a password?
	 * @var boolean
	 */
	public $isEncrypted = false;

	/**
	 * The RAR format version for the current archive.
	 * @var string
	 */
	public $format = '';

	/**
	 * Any RAR 5.0 archive comments.
	 * @var string
	 */
	public $comments = '';

	/**
	 * Convenience method that outputs a summary list of the archive information,
	 * useful for pretty-printing.
	 *
	 * @param   boolean   $full      add file list to output?
	 * @param   boolean   $skipDirs  should directory entries be skipped?
	 * @return  array     archive summary
	 */
	public function getSummary($full=false, $skipDirs=false)
	{
		$summary = array(
			'file_name'    => $this->file,
			'file_size'    => $this->fileSize,
			'data_size'    => $this->dataSize,
			'use_range'    => "{$this->start}-{$this->end}",
			'is_volume'    => (int) $this->isVolume,
			'has_auth'     => (int) $this->hasAuth,
			'has_recovery' => (int) $this->hasRecovery,
			'is_encrypted' => (int) $this->isEncrypted,
			'rar_format'   => $this->format,
		);
		if ($this->comments) {
			$summary['comments'] = $this->comments;
		}
		$fileList = $this->getFileList($skipDirs);
		$summary['file_count'] = count($fileList);
		if ($full) {
			$summary['file_list'] = $fileList;
		}
		if ($this->error) {
			$summary['error'] = $this->error;
		}

		return $summary;
	}

	/**
	 * Returns a list of the blocks found in the archive, optionally in human-readable
	 * format (for debugging purposes only).
	 *
	 * @param   boolean  $format  should the block data be formatted?
	 * @param   boolean  $asHex   should numeric values be displayed as hexadecimal?
	 * @return  array|boolean  list of blocks, or false if none available
	 */
	public function getBlocks($format=true, $asHex=false)
	{
		// Check that blocks are stored
		if (empty($this->blocks)) {return false;}
		if (!$format) {return $this->blocks;}

		// Build a formatted block list
		$ret = array();
		foreach ($this->blocks as $block) {
			$b = $this->formatBlock($block, $asHex);
			if ($b['head_type'] == self::R50_BLOCK_SERVICE && !empty($block['file_name'])
			 && $b['file_name'] == self::R50_SERVICE_QUICKOPEN
			) {
				// Format any cached blocks
				foreach ($b['cache_data'] as &$cache) {
					$cache['data'] = $this->formatBlock($cache['data'], $asHex);
				}
			}
			$ret[] = $b;
		}

		return $ret;
	}

	/**
	 * Parses the stored blocks and returns a list of records for each of the
	 * files in the archive.
	 *
	 * @param   boolean  $skipDirs  should directory entries be skipped?
	 * @return  array  list of file records, empty if none are available
	 */
	public function getFileList($skipDirs=false)
	{
		$ret = array();
		foreach ($this->blocks as $block) {
			if (($block['head_type'] == self::BLOCK_FILE || $block['head_type'] == self::R50_BLOCK_FILE)
			 && !empty($block['file_name'])
			) {
				if ($skipDirs && !empty($block['is_dir'])) {continue;}
				$ret[] = $this->getFileBlockSummary($block);
			}
		}

		return $ret;
	}

	/**
	 * Returns a summary list of any RAR 5.0 Quick Open cached file headers.
	 *
	 * @param   boolean  $skipDirs  should directory entries be skipped?
	 * @return  array|boolean  list of file record, or false if none available
	 */
	public function getQuickOpenFileList($skipDirs=false)
	{
		// Check that blocks are stored
		if (empty($this->blocks)) {return false;}

		$ret = array();
		foreach ($this->blocks as $block) {
			if ($block['head_type'] == self::R50_BLOCK_SERVICE && !empty($block['file_name'])
			 && $block['file_name'] == self::R50_SERVICE_QUICKOPEN
			) {
				// Build the cached file header list
				foreach ($block['cache_data'] as $cache) {
					if ($cache['data']['head_type'] == self::R50_BLOCK_FILE) {
						if ($skipDirs && !empty($cache['data']['is_dir'])) {continue;}
						$ret[] = $this->getFileBlockSummary($cache['data'], true);
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * Retrieves the raw data for the given filename. Note that this is only useful
	 * if the file isn't compressed.
	 *
	 * @param   string  $filename  name of the file to extract
	 * @return  string|boolean  file data, or false on error
	 */
	public function getFileData($filename)
	{
		// Check that blocks are stored and data source is available
		if (empty($this->blocks) || ($this->data == '' && $this->handle == null))
			return false;

		// Get the absolute start/end positions
		if (!($info = $this->getFileInfo($filename)) || empty($info['range'])) {
			$this->error = "Could not find file info for: ({$filename})";
			return false;
		}
		$this->error = '';

		return $this->getRange(explode('-', $info['range']));
	}

	/**
	 * Saves the raw data for the given filename to the given destination. This
	 * is only useful if the file isn't compressed.
	 *
	 * @param   string   $filename     name of the file to extract
	 * @param   string   $destination  full path of the file to create
	 * @return  integer|boolean  number of bytes saved or false on error
	 */
	public function saveFileData($filename, $destination)
	{
		// Check that blocks are stored and data source is available
		if (empty($this->blocks) || ($this->data == '' && $this->handle == null))
			return false;

		// Get the absolute start/end positions
		if (!($info = $this->getFileInfo($filename)) || empty($info['range'])) {
			$this->error = "Could not find file info for: ({$filename})";
			return false;
		}
		$this->error = '';

		return $this->saveRange(explode('-', $info['range']), $destination);
	}

	/**
	 * Sets the absolute path to the external Unrar client.
	 *
	 * @param   string   $client  path to the client
	 * @return  void
	 * @throws  InvalidArgumentException
	 */
	public function setExternalClient($client)
	{
		if ($client && (!is_file($client) || !is_executable($client)))
			throw new InvalidArgumentException("Not a valid client: {$client}");

		$this->externalClient = $client;
	}

	/**
	 * Extracts a compressed or encrypted file using the configured external Unrar
	 * client, optionally returning the data or saving it to file.
	 *
	 * @param   string  $filename     name of the file to extract
	 * @param   string  $destination  full path of the file to create
	 * @param   string  $password     password to use for decryption
	 * @return  mixed   extracted data, number of bytes saved or false on error
	 */
	public function extractFile($filename, $destination=null, $password=null)
	{
		if (!$this->externalClient || (!$this->file && !$this->data)) {
			$this->error = 'An external client and valid data source are needed';
			return false;
		}

		// Check that the file is extractable
		if (!($info = $this->getFileInfo($filename))) {
			$this->error = "Could not find file info for: ({$filename})";
			return false;
		}
		if (!empty($info['pass']) && $password == null) {
			$this->error = "The file is passworded: ({$filename})";
			return false;
		}

		// Set the data file source
		$source = $this->file ? $this->file : $this->createTempDataFile();

		// Ensure that internal file paths are valid for Mac/*nix
		if (DIRECTORY_SEPARATOR !== '\\') {
			$filename = str_replace('\\', '/', $filename);
		}

		// Set the external command
		$pass = $password ? '-p'.escapeshellarg($password) : '-p-';
		$command = '"'.$this->externalClient.'"'
			." p -kb -y -c- -ierr -ip {$pass} -- "
			.escapeshellarg($source).' '.escapeshellarg($filename);

		// Set STDERR to write to a temporary file
		list($hash, $errorFile) = $this->getTempFileName($source.'errors');
		$this->tempFiles[$hash] = $errorFile;
		$command .= ' 2> '.escapeshellarg($errorFile);

		// Start the new pipe reader
		$pipe = new PipeReader;
		if (!$pipe->open($command)) {
			$this->error = $pipe->error;
			return false;
		}
		$this->error = '';

		// Open destination file or start buffer
		if ($destination) {
			$handle = fopen($destination, 'wb');
			$written = 0;
		} else {
			$data = '';
		}

		// Buffer the piped data or save it to file
		while ($read = $pipe->read(1024, false)) {
			if ($destination) {
				$written += fwrite($handle, $read);
			} else {
				$data .= $read;
			}
		}
		if ($destination) {fclose($handle);}
		$pipe->close();

		// Check for errors (only after the pipe is closed)
		if (($error = @file_get_contents($errorFile)) && strpos($error, 'All OK') === false) {
			if ($destination) {@unlink($destination);}
			$this->error = $error;
			return false;
		}

		return $destination ? $written : $data;
	}

	/**
	 * List of block types and Subblock subtypes without bodies.
	 * @var array
	 */
	protected $headersOnly = array(
		'type'    => array(),
		'subtype' => array()
	);

	/**
	 * List of blocks found in the archive.
	 * @var array
	 */
	protected $blocks = array();

	/**
	 * Full path to the external Unrar client.
	 * @var string
	 */
	protected $externalClient = '';

	/**
	 * Returns block data in human-readable format (for debugging purposes only).
	 *
	 * @param   array    $block  the block to format
	 * @param   boolean  $asHex  should numeric values be displayed as hexadecimal?
	 * @return  array    the formatted block
	 */
	protected function formatBlock($block, $asHex=false)
	{
		$b = array();

		// Add block descriptors
		$b['type'] = isset($this->blockNames[$block['head_type']]) ? $this->blockNames[$block['head_type']] : 'Unknown';
		if ($block['head_type'] == self::BLOCK_SUB && !empty($block['file_name'])
		 && isset($this->subblockNames[$block['file_name']])
		) {
			$b['sub_type'] = $this->subblockNames[$block['file_name']];
		}
		if ($block['head_type'] == self::R50_BLOCK_SERVICE && !empty($block['file_name'])
		 && isset($this->serviceNames[$block['file_name']])
		) {
			$b['name'] = $this->serviceNames[$block['file_name']];
		}
		$b += $block;

		// Use hexadecimal values?
		if ($asHex) {
			array_walk_recursive($b, array('self', 'convert2hex'));
		}

		// Sanity check filename length
		if (isset($b['file_name'])) {
			$b['file_name'] = substr($b['file_name'], 0, $this->maxFilenameLength);
		}

		return $b;
	}

	/**
	 * Returns a processed summary of a RAR File block.
	 *
	 * @param   array  $block      a valid File block
	 * @param   array  $quickOpen  is this a Quick Open cached block?
	 * @return  array  summary information
	 */
	protected function getFileBlockSummary($block, $quickOpen=false)
	{
		$ret = array(
			'name' => !empty($block['file_name']) ? substr($block['file_name'], 0, $this->maxFilenameLength) : 'Unknown',
			'size' => isset($block['unp_size']) ? $block['unp_size'] : 0,
			'date' => !empty($block['utime']) ? $block['utime'] : (!empty($block['ftime']) ? self::dos2unixtime($block['ftime']) : 0),
			'pass' => isset($block['has_password']) ? ((int) $block['has_password']) : 0,
			'compressed' => (int) ($block['method'] != self::METHOD_STORE && $block['method'] != self::R50_METHOD_STORE),
			'next_offset' => $block['next_offset'],
		);
		if (!empty($block['is_dir'])) {
			$ret['is_dir'] = 1;
		} elseif (!$quickOpen && !in_array(self::BLOCK_FILE, $this->headersOnly['type'])) {
			$start = $this->start + $block['offset'] + $block['head_size'];
			$end   = min($this->end, $start + $block['pack_size'] - 1);
			$ret['range'] = "{$start}-{$end}";
		}
		if (!empty($block['file_crc'])) {
			$ret['crc32'] = dechex($block['file_crc']);
		}
		if (!empty($block['split_after']) || !empty($block['split_before'])) {
			$ret['split'] = 1;
		}
		if (!empty($block['split_after'])) {
			$ret['split_after'] = 1;
		}

		return $ret;
	}

	/**
	 * Returns information for the given filename in the current file/data.
	 *
	 * @param   string  $filename  the filename to search
	 * @return  array|boolean  the file info or false on error
	 */
	protected function getFileInfo($filename)
	{
		foreach ($this->getFileList(true) as $file) {
			if (isset($file['name']) && $file['name'] == $filename) {
				return $file;
			}
		}
		return false;
	}

	/**
	 * Searches for the position of a valid File header in the file/data up to
	 * maxReadBytes, and sets it as the start of the data to analyze.
	 *
	 * This quite slow hack is only useful when handling RAR file fragments, and
	 * only with RAR format 1.5 - 4.x.
	 *
	 * @return  integer|boolean  the header offset, or false if none is found
	 */
	protected function findFileHeader()
	{
		// Buffer the data to search
		$start = $this->offset;
		try {
			$buffer = $this->read(min($this->length, $this->maxReadBytes));
			$this->rewind();
		} catch (Exception $e) {return false;}

		// Get all the offsets to test
		if (!($positions = self::strposall($buffer, pack('C', self::BLOCK_FILE))))
			return false;

		foreach ($positions as $offset) try {
			$offset += $start;
			$this->seek($offset - 2);

			// Run a File header CRC & sanity check
			$block = $this->getNextBlock();
			if ($this->checkFileHeaderCRC($block)) {
				$this->seek($block['offset'] + self::HEADER_SIZE);
				$this->processBlock($block);
				if ($this->sanityCheckFileHeader($block)) {

					// A valid File header was found
					$this->format = self::FMT_RAR15;
					return $this->markerPosition = $block['offset'];
				}
			}

		// No more readable data, or read error
		} catch (Exception $e) {continue;}

		return false;
	}

	/**
	 * Runs a File Header CRC check on a valid File block.
	 *
	 * @param   array    $block  a valid File block
	 * @return  boolean  false if CRC check fails
	 */
	protected function checkFileHeaderCRC($block)
	{
		// Not supported for RAR 5.0
		if ($this->format == self::FMT_RAR50) {return false;}

		try {
			$this->seek($block['offset'] + 2);
			$data = $this->read($block['head_size'] - 2);
			$crc = crc32($data) & 0xffff;
			return ($crc === $block['head_crc']);
		} catch (Exception $e){
			return false;
		}
	}

	/**
	 * File header CRC checks can produce false positives, so this is a
	 * last-ditch attempt to verify that this is actually a valid header.
	 *
	 * @param   array    $block  the block to sanity check
	 * @param   integer  $limit  the minimum failure threshold
	 * @return  boolean  false if the sanity check fails
	 */
	protected function sanityCheckFileHeader($block, $limit=3)
	{
		// Not supported for RAR 5.0
		if ($this->format == self::FMT_RAR50) {return false;}

		$fail = ($block['host_os'] > 5)
		      + ($block['method'] > 0x35)
		      + ($block['unp_ver'] > 50)
		      + ($block['name_size'] > $this->maxFilenameLength)
		      + ($block['pack_size'] > PHP_INT_MAX)
		      + (isset($block['salt']) && !isset($block['has_password']));

		return $fail < $limit;
	}

	/**
	 * Returns the position of the archive marker/signature in the stored data or file.
	 *
	 * @return  mixed  Marker position, or false if marker is missing
	 */
	public function findMarker()
	{
		if ($this->markerPosition !== null)
			return $this->markerPosition;

		try {
			$buff = $this->read(min($this->length, $this->maxReadBytes));
			$this->rewind();
			if (($pos = strpos($buff, $this->markerBlock)) !== false) {
				$this->format = self::FMT_RAR15;
			} elseif (($pos = strpos($buff, $this->markerRar50)) !== false) {
				$this->format = self::FMT_RAR50;
			}
			return $this->markerPosition = $pos;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Parses the RAR data and stores a list of valid blocks locally.
	 *
	 * @return  boolean  false if parsing fails
	 */
	protected function analyze()
	{
		// Find the RAR marker, if there is one
		$startPos = $this->findMarker();
		if ($this->format == self::FMT_RAR50)
		{
			// Start after the RAR 5.0 marker signature
			$this->seek($startPos + strlen($this->markerRar50));

		} elseif ($startPos === false && !$this->isFragment) {

			// Not a RAR fragment or valid file, so abort here
			$this->error = 'Could not find Marker block, not a valid RAR file';
			return false;

		} elseif ($startPos !== false) {

			// Start at the MARKER block
			$this->seek($startPos);

		} elseif ($this->isFragment) {

			// Search for a valid file header and continue unpacking from there
			if (($startPos = $this->findFileHeader()) === false) {
				$this->error = 'Could not find a valid File header';
				return false;
			}
			$this->seek($startPos);
		}

		// Analyze all valid blocks
		while ($this->offset < $this->length) try {

			// Get the next block header
			$block = $this->getNextBlock();

			// Process the current block by type
			$this->processBlock($block);

			// Add the current block to the list
			$this->blocks[] = $block;

			// Bail if this is an encrypted archive
			if ($this->isEncrypted)
				break;

			// Skip to the next block, if any
			if ($this->offset != $block['next_offset']) {
				$this->seek($block['next_offset']);
			}

			// Sanity check
			if ($block['offset'] == $this->offset) {
				$this->error = 'Parsing seems to be stuck';
				$this->close();
				return false;
			}

		// No more readable data, or read error
		} catch (Exception $e) {
			if ($this->error) {$this->close(); return false;}
			break;
		}

		// Check for valid blocks
		if (empty($this->blocks)) {
			$this->error = 'No valid RAR blocks were found';
			return false;
		}

		// Analysis was successful
		return true;
	}

	/**
	 * Reads the start of the next block header and returns the common block
	 * info before further processing by block type.
	 *
	 * @return  array  the next block header info
	 */
	protected function getNextBlock()
	{
		if ($this->format == self::FMT_RAR50)
			return $this->getNextBlockR50();

		// Start the block info
		$block = array('offset' => $this->offset);

		// Unpack the block header
		$block += self::unpack(self::FORMAT_BLOCK_HEADER, $this->read(self::HEADER_SIZE), false);

		// Check for add_size field
		if (($block['head_flags'] & self::LONG_BLOCK)
		 && ($block['head_type'] != self::BLOCK_FILE)
		 && ($block['head_type'] != self::BLOCK_SUB)
		) {
			$block += self::unpack('Vadd_size', $this->read(4));
		} else {
			$block['add_size'] = 0;
		}

		// Sanity check header size
		$block['head_size'] = max(self::HEADER_SIZE, $block['head_size']);

		// Add offset info for next block (if any)
		$block['next_offset'] = $block['offset'] + $block['head_size'] + $block['add_size'];

		// Return the block info
		return $block;
	}

	/**
	 * Processes a block passed by reference based on its type.
	 *
	 * @param   array  $block  the block to process
	 * @return  void
	 */
	protected function processBlock(&$block)
	{
		if ($this->format == self::FMT_RAR50)
			return $this->processBlockR50($block);

		// Block type: ARCHIVE
		if ($block['head_type'] == self::BLOCK_MAIN) {

			// Unpack the remainder of the Archive block header
			$block += self::unpack('vreserved1/Vreserved2', $this->read(6));

			// Parse Archive flags
			if ($block['head_flags'] & self::MAIN_VOLUME) {
				$block['is_volume'] = true;
				$this->isVolume = true;
			}
			if ($block['head_flags'] & self::MAIN_AUTH) {
				$block['has_auth'] = true;
				$this->hasAuth = true;
			}
			if ($block['head_flags'] & self::MAIN_RECOVERY) {
				$block['has_recovery'] = true;
				$this->hasRecovery = true;
			}
			if ($block['head_flags'] & self::MAIN_PASSWORD) {
				$block['is_encrypted'] = 1;
				$this->isEncrypted = true;
			}
		}

		// Block type: ARCHIVE END
		elseif ($block['head_type'] == self::BLOCK_ENDARC) {
			$block['more_volumes'] = (bool) ($block['head_flags'] & self::ENDARC_NEXT_VOLUME);
		}

		// Block type: NULL BLOCK (zero-padded)
		elseif ($block['head_type'] == self::BLOCK_NULL) {
			$remainder = $this->length - $block['offset'] - $block['head_size'];
			if ($remainder < self::HEADER_SIZE) {
				$block['next_offset'] = $this->length;
				$block['add_size'] = $remainder;
			}
		}

		// Block type: FILE or SUBBLOCK (new style)
		elseif ($block['head_type'] == self::BLOCK_FILE || $block['head_type'] == self::BLOCK_SUB) {

			// Unpack the remainder of the block header
			$block += self::unpack(self::FORMAT_FILE_HEADER, $this->read(25));

			// Large file sizes
			if ($block['head_flags'] & self::FILE_LARGE) {
				$block += self::unpack('Vhigh_pack_size/Vhigh_unp_size', $this->read(8));
				$block['pack_size'] = self::int64($block['pack_size'], $block['high_pack_size']);
				$block['unp_size'] = self::int64($block['unp_size'], $block['high_unp_size']);
			}

			// Is this a directory entry?
			if (($block['head_flags'] & self::FILE_DICTMASK) == self::FILE_DIRECTORY) {
				$block['is_dir'] = true;
			}

			// Filename: unicode
			if ($block['head_flags'] & self::FILE_UNICODE) {

				// Split the standard filename and unicode data from the file_name field
				$fn = explode("\x00", $this->read($block['name_size']));

				// Decompress the unicode filename, encode the result as UTF-8
				$uc = new RarUnicodeFilename($fn[0], $fn[1]);
				if ($ucname = $uc->decode()) {
					$block['file_name'] = @iconv('UTF-16LE', 'UTF-8//IGNORE//TRANSLIT', $ucname);

				// Fallback to the standard filename
				} else {
					$block['file_name'] = $fn[0];
				}

			// Filename: non-unicode
			} else {
				$block['file_name'] = $this->read($block['name_size']);
			}

			// Salt (optional)
			if ($block['head_flags'] & self::FILE_SALT) {
				$block['salt'] = $this->read(8);
			}

			// Extended time fields (optional)
			if ($block['head_flags'] & self::FILE_EXTTIME) {
				$block['ext_time'] = true;
			}

			// Encrypted with password?
			if ($block['head_flags'] & self::FILE_PASSWORD) {
				$block['has_password'] = true;
			}

			// Continued from previous volume?
			if ($block['head_flags'] & self::FILE_SPLIT_BEFORE) {
				$block['split_before'] = true;
			}

			// Continued in next volume?
			if ($block['head_flags'] & self::FILE_SPLIT_AFTER) {
				$block['split_after'] = true;
			}

			// Increment the file count
			if ($block['head_type'] == self::BLOCK_FILE) {
				$this->fileCount++;
			}

			// Update next header block offset
			if (($block['head_type'] == self::BLOCK_FILE && !in_array(self::BLOCK_FILE, $this->headersOnly['type']))
			 || ($block['head_type'] == self::BLOCK_SUB  && !in_array($block['file_name'], $this->headersOnly['subtype']))
			) {
				$block['next_offset'] += $block['pack_size'];
			}
		}

		// Parse any useful Subblock info
		if ($block['head_type'] == self::BLOCK_SUB) {

			// Authenticity verification
			if ($block['file_name'] == self::SUBTYPE_AUTHVER) {
				$block += self::unpack('vav_name_size', $this->read(2));
				$block['av_file_name'] = $this->read($block['av_name_size']);
				$block += self::unpack('vav_unknown/vav_cname_size', $this->read(4)); // guesswork
				$block['av_created_by'] = $this->read($block['av_cname_size']);
			}
		}
	}

	/**
	 * Reads the start of the next Rar 5.0 block header and returns the common
	 * block info before further processing by block type.
	 *
	 * @return  array  the next block header info
	 */
	protected function getNextBlockR50()
	{
		// Start the block info
		$block = array('offset' => $this->offset);

		// Unpack the main part of the header
		$block += self::unpack('Vhead_crc', $this->read(4));
		$block['head_size_r'] = $this->getVarInt();
		$block['head_size']   = $this->offset - $block['offset'] + $block['head_size_r'];
		$block['head_type']   = $this->getVarInt();
		$block['head_flags']  = $this->getVarInt();

		// Optional extra area and data area sizes
		if ($block['head_flags'] & self::R50_HAS_EXTRA) {
			$block['extra_size'] = $this->getVarInt();
		}
		if ($block['head_flags'] & self::R50_HAS_DATA) {
			$block['data_size'] = $this->getVarInt();
		}

		// Sanity check header size
		$block['head_size'] = max(self::HEADER_SIZE, $block['head_size']);

		// Add offset info for next block (if any)
		$block['next_offset'] = $block['offset'] + $block['head_size'];
		if (isset($block['data_size'])) {
			$block['next_offset'] += $block['data_size'];
		}

		// Return the block info
		return $block;
	}

	/**
	 * Processes a RAR 5.0 block passed by reference based on its type.
	 *
	 * @param   array  $block      the block to process
	 * @param   array  $quickOpen  is this a Quick Open cached block?
	 * @return  void
	 * @throws  RuntimeException
	 */
	protected function processBlockR50(&$block, $quickOpen=false)
	{
		// Block type: ARCHIVE
		if ($block['head_type'] == self::R50_BLOCK_MAIN) {
			$block['flags'] = $this->getVarInt();

			if ($block['flags'] & self::R50_MAIN_VOLUME) {
				$block['is_volume'] = true;
				$this->isVolume = true;
			}
			if ($block['flags'] & self::R50_MAIN_VOLNUMBER) {
				$block['vol_number'] = $this->getVarInt();
			}
			if ($block['flags'] & self::R50_MAIN_RECOVERY) {
				$block['has_recovery'] = true;
				$this->hasRecovery = true;
			}
		}

		// Block type: ARCHIVE END
		elseif ($block['head_type'] == self::R50_BLOCK_ENDARC) {
			$block['flags'] = $this->getVarInt();
			$block['more_volumes'] = (bool) ($block['flags'] & self::R50_ENDARC_NEXT_VOLUME);
		}

		// Block type: ARCHIVE ENCRYPTION
		elseif ($block['head_type'] == self::R50_BLOCK_CRYPT) {
			$this->isEncrypted = true;
		}

		// Block type: FILE or SERVICE
		elseif ($block['head_type'] == self::R50_BLOCK_FILE
		     || $block['head_type'] == self::R50_BLOCK_SERVICE
		) {
			if (!isset($block['data_size']))
				throw new RuntimeException('Required block data size is missing');

			$block['flags']     = $this->getVarInt();
			$block['pack_size'] = $block['data_size'];
			$block['unp_size']  = $this->getVarInt();
			$block['attr']      = $this->getVarInt();
			$block['utime']     = 0;

			if ($block['flags'] & self::R50_FILE_UTIME) {
				$block += self::unpack('Vutime', $this->read(4));
			}
			if ($block['flags'] & self::R50_FILE_CRC32) {
				$block += self::unpack('Vfile_crc', $this->read(4));
			}
			if ($block['flags'] & self::R50_FILE_DIRECTORY) {
				$block['is_dir'] = true;
			}

			$block['comp_info'] = $this->getVarInt();
			$block['unp_ver']   = $block['comp_info'] & 0x3f;
			$block['method']    = ($block['comp_info'] >> 7) & 7;
			$block['host_os']   = $this->getVarInt();
			$block['name_size'] = $this->getVarInt();
			$block['file_name'] = $this->read($block['name_size']);

			// Increment the file count
			if ($block['head_type'] == self::R50_BLOCK_FILE && !$quickOpen) {
				$this->fileCount++;
			}

			if ($block['head_type'] == self::R50_BLOCK_SERVICE && !$quickOpen) {

				// Add any archive comments
				if ($block['file_name'] == self::R50_SERVICE_COMMENT) {
					$this->comments = $this->read($block['pack_size']);

				// Add the quick open data
				} elseif ($block['file_name'] == self::R50_SERVICE_QUICKOPEN) {
					$this->processQuickOpenRecords($block);
				}
			}
		}

		// Continued from previous volume?
		if ($block['head_flags'] & self::R50_SPLIT_BEFORE) {
			$block['split_before'] = true;
		}

		// Continued in next volume?
		if ($block['head_flags'] & self::R50_SPLIT_AFTER) {
			$block['split_after'] = true;
		}

		// Process any extra records
		if ($block['head_flags'] & self::R50_HAS_EXTRA) {
			$this->processExtraRecords($block);
		}
	}

	/**
	 * Processes the RAR 5.0 Quick Open block data and stores any cached headers.
	 *
	 * @param   array  $block  the block to process
	 * @return  void
	 */
	protected function processQuickOpenRecords(&$block)
	{
		$end = $this->offset + $block['data_size'];
		while ($this->offset < $end) {

			// Start the cache record
			$cache = array('offset' => $this->offset);
			$cache += self::unpack('Vcrc32', $this->read(4));
			$cache['size']         = $this->getVarInt();
			$cache['flags']        = $this->getVarInt();
			$cache['quick_offset'] = $this->getVarInt();
			$cache['data_size']    = $this->getVarInt();
			$cache['next']         = $this->offset + $cache['data_size'];

			// Process the cached header data
			$data = $this->getNextBlockR50();
			$this->processBlockR50($data, true);
			$cache['data'] = $data;

			// Store the cached data
			$block['cache_data'][] = $cache;
			if ($this->offset != $cache['next']) {
				$this->seek($cache['next']);
			}
		}
	}

	/**
	 * Processes the extra records of a RAR 5.0 block passed by reference.
	 *
	 * @param   array  $block  the block to process
	 * @return  void
	 */
	protected function processExtraRecords(&$block)
	{
		$end = $this->offset + $block['extra_size'];
		while ($this->offset < $end) {

			// Start with the record size and type
			$rec = array(
				'name'   => 'Unknown',
				'offset' => $this->offset,
				'size'   => $size = $this->getVarInt(),
				'next'   => $this->offset + $size,
				'type'   => $this->getVarInt(),
			);

			// Block type: ARCHIVE
			if ($block['head_type'] == self::R50_BLOCK_MAIN) {
				if ($rec['type'] == self::R50_MEXTRA_LOCATOR) {
					$rec['name']  = 'Locator';
					$rec['flags'] = $this->getVarInt();

					if ($rec['flags'] & self::R50_MEXTRA_LOC_QLIST) {
						$rec['quick'] = $this->getVarInt();
					}
					if ($rec['flags'] & self::R50_MEXTRA_LOC_RR) {
						$rec['rr'] = $this->getVarInt();
					}
				}
			}

			// Block type: FILE
			elseif ($block['head_type'] == self::R50_BLOCK_FILE) {
				if ($rec['type'] == self::R50_FEXTRA_CRYPT) {
					$rec['name'] = 'File encryption';
					$block['has_password'] = true;
				}
				elseif ($rec['type'] == self::R50_FEXTRA_HASH) {
					$rec['name'] = 'File hash';
				}
				elseif ($rec['type'] == self::R50_FEXTRA_HTIME) {
					$rec['name']  = 'File time';
					$rec['flags'] = $this->getVarInt();
					$rec['unix']  = (bool) ($rec['flags'] & self::R50_FEXTRA_HT_UNIX);

					if ($rec['flags'] & self::R50_FEXTRA_HT_MTIME) {
						if ($rec['unix']) {
							$rec += self::unpack('Vmtime', $this->read(4));
							$block['utime'] = $rec['mtime'];
						} else {
							$rec += self::unpack('Vmtime/Vmtime_high', $this->read(8));
							$block['utime'] = self::win2unixtime($rec['mtime'], $rec['mtime_high']);
						}
					}
					if ($rec['flags'] & self::R50_FEXTRA_HT_CTIME) {
						if ($rec['unix']) {
							$rec += self::unpack('Vctime', $this->read(4));
						} else {
							$rec += self::unpack('Vctime/Vctime_high', $this->read(8));
						}
					}
					if ($rec['flags'] & self::R50_FEXTRA_HT_ATIME) {
						if ($rec['unix']) {
							$rec += self::unpack('Vatime', $this->read(4));
						} else {
							$rec += self::unpack('Vatime/Vatime_high', $this->read(8));
						}
					}
				}
				elseif ($rec['type'] == self::R50_FEXTRA_VERSION) {
					$rec['name'] = 'File version';
				}
				elseif ($rec['type'] == self::R50_FEXTRA_REDIR) {
					$rec['name'] = 'Redirection';
				}
				elseif ($rec['type'] == self::R50_FEXTRA_UOWNER) {
					$rec['name'] = 'Unix owner';
				}
				elseif ($rec['type'] == self::R50_FEXTRA_SUBDATA) {
					$rec['name'] = 'Service data';
				}
			}

			// Add the extra record
			$block['extra'][] = $rec;
			if ($this->offset != $rec['next']) {
				$this->seek($rec['next']);
			}
		}
	}

	/**
	 * Reads a RAR 5.0 variable length integer value from the current offset,
	 * which may be an unsigned integer or float depending on the size and system.
	 *
	 * The high bit of each byte in the little-endian sequence is a continuation
	 * flag (where 0 = end of the sequence), the remaining 7 bits are the value.
	 * The maximum value is an unsigned 64-bit integer in a 10-byte sequence.
	 *
	 * @return  integer|float  the variable length value, or zero on under/overflow
	 */
	protected function getVarInt()
	{
		$shift = $low = $high = 0;
		$count = 1;

		while ($this->offset < $this->length && $count <= 10) {
			$byte = ord($this->read(1));
			if ($count < 5) {
				$low  += ($byte & 0x7f) << $shift;
			} elseif ($count == 5) {
				$low  += ($byte & 0x0f) << $shift; // 4 bits
				$high += ($byte >> 4) & 0x07;      // 3 bits
				$shift = -4;
			} elseif ($count > 5) {
				$high += ($byte & 0x7f) << $shift;
			}
			if (($byte & 0x80) === 0) {
				if ($low < 0) {$low += 0x100000000;}
				if ($high < 0) {$high += 0x100000000;}
				return ($high ? self::int64($low, $high) : $low);
			}
			$shift += 7;
			$count += 1;
		}

		return 0;
	}

	/**
	 * Resets the instance variables before parsing new data.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		parent::reset();
		$this->isVolume = false;
		$this->hasAuth = false;
		$this->hasRecovery = false;
		$this->isEncrypted = false;
		$this->blocks = array();
		$this->format = '';
		$this->comments = '';
	}

} // End RarInfo class

/**
 * RarUnicodeFilename class.
 *
 * This utility class handles the unicode filename decompression for RAR files. It is
 * adapted directly from Marko Kreen's python script rarfile.py.
 *
 * @link https://github.com/markokr/rarfile
 *
 * @version 1.2
 */
class RarUnicodeFilename
{
	/**
	 * Initializes the class instance.
	 *
	 * @param   string  $stdName  the standard filename
	 * @param   string  $encData  the unicode data
	 * @return  void
	 */
	public function __construct($stdName, $encData)
	{
		$this->stdName = $stdName;
		$this->encData = $encData;
	}

	/**
	 * Decompresses the unicode filename by combining the standard filename with
	 * the additional unicode data, return value is encoded as UTF-16LE.
	 *
	 * @return  mixed  the unicode filename, or false on failure
	 */
	public function decode()
	{
		$highByte = $this->encByte();
		$encDataLen = strlen($this->encData);
		$flagBits = 0;

		while ($this->encPos < $encDataLen) {
			if ($flagBits == 0) {
				$flags = $this->encByte();
				$flagBits = 8;
			}
			$flagBits -= 2;

			switch (($flags >> $flagBits) & 3) {
				case 0:
					$this->put($this->encByte(), 0);
					break;
				case 1:
					$this->put($this->encByte(), $highByte);
					break;
				case 2:
					$this->put($this->encByte(), $this->encByte());
					break;
				default:
					$n = $this->encByte();
					if ($n & 0x80) {
						$c = $this->encByte();
						for ($i = 0; $i < (($n & 0x7f) + 2); $i++) {
							$lowByte = ($this->stdByte() + $c) & 0xFF;
							$this->put($lowByte, $highByte);
						}
					} else {
						for ($i = 0; $i < ($n + 2); $i++) {
							$this->put($this->stdByte(), 0);
						}
					}
			}
		}

		// Return the unicode string
		if ($this->failed) {return false;}
		return $this->output;
	}

	/**
	 * The standard filename data.
	 * @var string
	 */
	protected $stdName;

	/**
	 * The unicode data used for processing.
	 * @var string
	 */
	protected $encData;

	/**
	 * Pointer for the standard filename data.
	 * @var integer
	 */
	protected $pos = 0;

	/**
	 * Pointer for the unicode data.
	 * @var integer
	 */
	protected $encPos = 0;

	/**
	 * Did the decompression fail?
	 * @var boolean
	 */
	protected $failed = false;

	/**
	 * Decompressed unicode filename string.
	 * @var string
	 */
	protected $output;

	/**
	 * Gets the current byte value from the unicode data and increments the
	 * pointer if successful.
	 *
	 * @return  integer  encoded byte value, or 0 on fail
	 */
	protected function encByte()
	{
		if (isset($this->encData[$this->encPos])) {
			$ret = ord($this->encData[$this->encPos]);
		} else {
			$this->failed = true;
			$ret = 0;
		}
		$this->encPos++;
		return $ret;
	}

	/**
	 * Gets the current byte value from the standard filename data.
	 *
	 * @return  integer  standard byte value, or placeholder on fail
	 */
	protected function stdByte()
	{
		if (isset($this->stdName[$this->pos])) {
			return ord($this->stdName[$this->pos]);
		}
		$this->failed = true;
		return ord('?');
	}

	/**
	 * Builds the output for the unicode filename string in 16-bit blocks (UTF-16LE).
	 *
	 * @param   integer  $low   low byte value
	 * @param   integer  $high  high byte value
	 * @return  void
	 */
	protected function put($low, $high)
	{
		$this->output .= chr($low);
		$this->output .= chr($high);
		$this->pos++;
	}

} // End RarUnicodeFilename class
