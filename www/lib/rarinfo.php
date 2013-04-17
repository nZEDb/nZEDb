<?php
/**
 * RarInfo class.
 * 
 * A simple class for inspecting RAR file data and listing information about 
 * the archive contents in pure PHP (no external dependencies). Data can be 
 * loaded directly from a file or from a variable passed by reference.
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
 *   }
 *
 * </code>
 *
 * @todo Plenty of parsing still possible, most format values have been added ;)
 * @link http://www.win-rar.com/index.php?id=24&kb_article_id=162
 *
 * @author     Hecks
 * @copyright  (c) 2010 Hecks
 * @license    Modified BSD
 * @version    1.6
 *
 * CHANGELOG:
 * ----------
 * 1.6 Added extra error checking to read method
 * 1.5 Improved getSummary method output
 * 1.4 Added filename sanity checks & maxFilenameLength variable
 * 1.3 Fixed issues with some file headers lacking LONG_BLOCK flag
 * 1.2 Tweaked seeking method
 * 1.1 Fixed issues with PHP not handling unsigned longs properly (pfft)
 * 1.0 Initial release
 *
 */
class RarInfo
{
	// ------ Class constants -----------------------------------------------------	

	/**#@+
	 * RAR file format values
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

	// OS types
	const OS_MSDOS = 0;
	const OS_OS2   = 1;
	const OS_WIN32 = 2;
	const OS_UNIX  = 3;
	const OS_MACOS = 4;
	const OS_BEOS  = 5;
	
	/**#@-*/
	
	/**
	 * Format for unpacking the main part of each block header.
	 */
	const FORMAT_BLOCK_HEADER = 'vhead_crc/Chead_type/vhead_flags/vhead_size';

	/**
	 * Format for unpacking the remainder of a File block header.
	 */
	const FORMAT_FILE_HEADER = 'Vunp_size/Chost_os/Vfile_crc/Vftime/Cunp_ver/Cmethod/vname_size/Vattr';
	
	/**
	 * Signature for the Marker block.
	 */	
	const MARKER_BLOCK = '526172211a0700';
	
	
	// ------ Class variables and methods -----------------------------------------

	/**
	 * List of block names corresponding to block types.
	 * @var array
	 */	
	static $blockNames = array(
		0x72 => 'Marker',
		0x73 => 'Archive',
		0x74 => 'File',
		0x75 => 'Old Style Comment',
		0x76 => 'Old Style Extra Info',
		0x77 => 'Old Style Subblock',
		0x78 => 'Old Style Recovery Record',
		0x79 => 'Old Style Archive Authenticity',
		0x7a => 'Subblock',
		0x7b => 'Archive End',
	);
	
	// ------ Instance variables and methods ---------------------------------------
	
	/**
	 * Is the volume attribute set for the archive?
	 * @var bool
	 */
	public $isVolume;
	
	/**
	 * Is authenticity information present?
	 * @var bool
	 */
	public $hasAuth;
	
	/**
	 * Is a recovery record present?
	 * @var bool
	 */
	public $hasRecovery;

	/**
	 * Is the archive encrypted with a password?
	 * @var bool
	 */
	public $isEncrypted;
	
	/**
	 * The last error message.
	 * @var string
	 */
	public $error;
	
	/**
	 * Loads data from the specified file (up to maxReadBytes) and analyses
	 * the archive contents.
	 *
	 * @param   string  path to the file
	 * @return  bool    false if archive analysis fails
	 */
	public function open($file)
	{
		if ($this->isAnalyzed) {$this->reset();}
		if (!($rarFile = realpath($file))) {
			trigger_error("File does not exist ($file)", E_USER_WARNING);
			$this->error = 'File does not exist';
			return false;
		}
		$this->data = file_get_contents($rarFile, NULL, NULL, 0, $this->maxReadBytes);
		$this->dataSize = strlen($this->data);
		$this->rarFile = $rarFile;
		
		return $this->analyze();
	}

	/**
	 * Loads data passed by reference (up to maxReadBytes) and analyses the 
	 * archive contents.
	 *
	 * @param   string  archive data stored in a variable
	 * @return  bool    false if archive analysis fails
	 */	
	public function setData(&$data)
	{
		if ($this->isAnalyzed) {$this->reset();}
		$this->data = substr($data, 0, $this->maxReadBytes);
		$this->dataSize = strlen($data);
		
		return $this->analyze();
	}

	/**
	 * Sets the maximum number of data bytes to be stored.
	 *
	 * @param   integer maximum bytes
	 * @return  void
	 */
	public function setMaxBytes($bytes)
	{
		if (is_int($bytes)) {$this->maxReadBytes = $bytes;}
	}
	
	/**
	 * Convenience method that outputs a summary list of the archive information,
	 * useful for pretty-printing.
	 *
	 * @param   bool   add file list to output?
	 * @return  array  archive summary
	 */	
	public function getSummary($full=false)
	{
		$summary = array(
			'rar_file' => $this->rarFile,
			'data_size' => $this->dataSize,
			'is_volume' => (int) $this->isVolume,
			'has_auth' => (int) $this->hasAuth,
			'has_recovery' => (int) $this->hasRecovery,
			'is_encrypted' => (int) $this->isEncrypted,
		);
		$fileList = $this->getFileList();
		$summary['file_count'] = count($fileList);
		if ($full) {
			$summary['file_list'] = $fileList;
		}
		
		return $summary;
	}

	/**
	 * Returns a list of the blocks found in the archive in human-readable format
	 * (for debugging purposes only).
	 *
	 * @param   bool   should numeric values be displayed as hexadecimal?
	 * @return  array  list of blocks
	 */	
	public function getBlocks($asHex=false)
	{
		// Check that blocks are stored
		if (!$this->blocks) {return false;}
		
		// Build the block list
		$ret = array();
		foreach ($this->blocks AS $block) {
			$b = array();
			$b['type'] = isset(self::$blockNames[$block['head_type']]) ? self::$blockNames[$block['head_type']] : 'Unknown';
			if ($asHex) foreach ($block AS $key=>$val) {
				$b[$key] = is_numeric($val) ? dechex($val) : $val;
			} else {
				$b += $block;
			}
			
			// Sanity check filename length
			if (isset($b['file_name'])) {$b['file_name'] = substr($b['file_name'], 0, $this->maxFilenameLength);}
			$ret[] = $b;
		}
		
		return $ret;
	}

	/**
	 * Parses the stored blocks and returns a list of records for each of the 
	 * files in the archive.
	 *
	 * @return  mixed  false if no file blocks available, or array of file records
	 */
	public function getFileList()
	{
		// Check that blocks are stored
		if (!$this->blocks) {return false;}

		// Build the file list
		$ret = array();
		foreach ($this->blocks AS $block) {
			if ($block['head_type'] == self::BLOCK_FILE) {
				$ret[] = array(
					'name' => !empty($block['file_name']) ? substr($block['file_name'], 0, $this->maxFilenameLength) : 'Unknown',
					'size' => isset($block['unp_size']) ? $block['unp_size'] : 0,
					'date' => !empty($block['ftime']) ? $this->dos2unixtime($block['ftime']) : 0,
					'pass'  => (int) $block['has_password'],
				);
			}
		}
		
		return $ret;
	}
	
	/**
	 * Path to the RAR file (if any).
	 * @var string
	 */
	protected $rarFile;
	
	/**
	 * The maximum number of bytes to analyze.
	 * @var integer
	 */
	protected $maxReadBytes = 1048576;

	/**
	 * The maximum length of filenames (for sanity checking).
	 * @var integer
	 */
	protected $maxFilenameLength = 500;
	
	/**
	 * Have the archive contents been analyzed?
	 * @var bool
	 */
	protected $isAnalyzed = false;

	/**
	 * The stored RAR file data.
	 * @var string
	 */	
	protected $data;

	/**
	 * The size in bytes of the currently stored data.
	 * @var integer
	 */	
	protected $dataSize;

	/**
	 * A pointer to the current position in the data.
	 * @var integer
	 */	
	protected $offset = 0;
	
	/**
	 * List of blocks found in the archive.
	 * @var array
	 */	
	protected $blocks;	
	
	/**
	 * Parses the RAR data and stores a list of found blocks.
	 *
	 * @return  bool  false if parsing fails
	 */
	protected function analyze()
	{
		// Find the MARKER block
		$startPos = strpos($this->data, pack('H*', self::MARKER_BLOCK));
		if ($startPos === false) {
//			trigger_error('Not a valid RAR file', E_USER_WARNING);
			$this->error = 'Could not find Marker Block, not a valid RAR file';
			return false;
		}
		$this->offset = $startPos;
		$block = array('offset' => $startPos);
		$block += unpack(self::FORMAT_BLOCK_HEADER, $this->read(7));
		$this->blocks[] = $block;

		// Analyze all remaining blocks
		while ($this->offset < $this->dataSize) try {
		
			// Get the current block header
			$block = array('offset' => $this->offset);
			$block += unpack(self::FORMAT_BLOCK_HEADER, $this->read(7));
			if (($block['head_flags'] & self::LONG_BLOCK)
				|| ($block['head_type'] == self::BLOCK_FILE)
				) {
				$addsize = unpack('V', $this->read(4));
				$block['add_size'] = sprintf('%u', $addsize[1]);
			} else {
				$block['add_size'] = 0;
			}

			// Block type: ARCHIVE
			if ($block['head_type'] == self::BLOCK_MAIN) {
			
				// Unpack the remainder of the Archive block header
				$block += unpack('vreserved1/Vreserved2', $this->read(6));
				
				// Parse Archive flags
				if ($block['head_flags'] & self::MAIN_VOLUME) {
					$this->isVolume = true;
				}
				if ($block['head_flags'] & self::MAIN_AUTH) {
					$this->hasAuth = true;
				}						
				if ($block['head_flags'] & self::MAIN_RECOVERY) {
					$this->hasRecovery = true;
				}			
				if ($block['head_flags'] & self::MAIN_PASSWORD) {
					$this->isEncrypted = true;
				}
			}
		
			// Block type: FILE
			elseif ($block['head_type'] == self::BLOCK_FILE) {
			
				// Unpack the remainder of the File block header
				$block += unpack(self::FORMAT_FILE_HEADER, $this->read(21));
				
				// Fix PHP issue with unsigned longs
				$block['unp_size'] = sprintf('%u', $block['unp_size']);
				$block['file_crc'] = sprintf('%u', $block['file_crc']);
				$block['ftime'] = sprintf('%u', $block['ftime']);
				$block['attr'] = sprintf('%u', $block['attr']);
				
				// Large file sizes
				if ($block['head_flags'] & self::FILE_LARGE) {
					$block += unpack('Vhigh_pack_size/Vhigh_unp_size', $this->read(8));
					$block['high_pack_size'] = sprintf('%u', $block['high_pack_size']);
					$block['high_unp_size'] = sprintf('%u', $block['high_unp_size']);
					$block['add_size'] += ($block['high_pack_size'] * 0x100000000);
					$block['unp_size'] += ($block['high_unp_size'] * 0x100000000);
				}
				
				// Filename
				$block['file_name'] = $this->read($block['name_size']);
				
				// Salt (optional)
				if ($block['head_flags'] & self::FILE_SALT) {
					$block += unpack('C8salt', $this->read(8));
				}
				
				// Extended time fields (optional)
				if ($block['head_flags'] & self::FILE_EXTTIME) {
					$block['ext_time'] = true;
				}
				
				// Encrypted with password?
				if ($block['head_flags'] & self::FILE_PASSWORD) {
					$block['has_password'] = true;
				} else {
					$block['has_password'] = false;
				}
			}

			// Add block to the list
			$this->blocks[] = $block;
			
			// Skip to the next block
			$this->seek($block['offset'] + $block['head_size'] + $block['add_size']);
		
			// Sanity check
			if ($block['offset'] == $this->offset) {
				trigger_error('Parsing failed', E_USER_WARNING);
				$this->error = 'Parsing seems to be stuck';
				return false;
			}
			
		// No more readable data, or read error
		} catch (Exception $e) {
			if ($this->error) {return false;}
			break;
		}

		// End	
		$this->isAnalyzed = true;
		return true;
	}
	
	/**
	 * Reads the given number of bytes from the stored data and moves the 
	 * pointer forward.
	 *
	 * @param   integer  number of bytes to read
	 * @return  string   byte string
	 */
	protected function read($num)
	{
		// Check that enough data is available
		$newPos = $this->offset + $num;
		if ($newPos > ($this->dataSize - 1)) {
			throw new Exception('End of readable data');
		}
		
		// Read the requested bytes
		$read = substr($this->data, $this->offset, $num);
		
		// Confirm read length
		$rlen = strlen($read);
		if ($rlen < $num) {
			$this->error = "Not enough data ({$num} requested, {$rlen} available)";
			//trigger_error($this->error, E_USER_WARNING);
			throw new Exception('Read error');
		}
		
		// Move the data pointer
		$this->offset = $newPos;
		
		return $read;
	}
	
	/**
	 * Moves the stored data pointer to the given position.
	 *
	 * @param   integer  new pointer position
	 * @return  void
	 */
	protected function seek($pos)
	{
		if ($pos > ($this->dataSize - 1) || $pos < 0) {
			$this->offset = ($this->dataSize - 1);
		}
		$this->offset = $pos;
	}
	
	/**
	 * Converts DOS standard timestamps to UNIX timestamps.
	 *
	 * @param   integer  DOS timestamp
	 * @return  integer  UNIX timestamp
	 */
	protected function dos2unixtime($dostime)
	{
		$sec  = 2 * ($dostime & 0x1f);
		$min  = ($dostime >> 5) & 0x3f;
		$hrs  = ($dostime >> 11) & 0x1f;
		$day  = ($dostime >> 16) & 0x1f;
		$mon  = ($dostime >> 21) & 0x0f;
		$year = (($dostime >> 25) & 0x7f) + 1980;
		
		return mktime($hrs, $min, $sec, $mon, $day, $year);
	}

	/**
	 * Resets the instance variables before parsing new data.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		$this->rarFile = null;
		$this->data = null;
		$this->dataSize = null;
		$this->offset = 0;
		$this->isAnalyzed = false;
		$this->error = null;
		$this->isVolume = null;
		$this->hasAuth = null;
		$this->hasRecovery = null;
		$this->isEncrypted = null;
		$this->blocks = null;
	}
	
} // End RarInfo class
