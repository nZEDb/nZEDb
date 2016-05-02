<?php

require_once dirname(__FILE__) . '/archivereader.php';
require_once dirname(__FILE__) . '/pipereader.php';

/**
 * ZipInfo class.
 *
 * A simple class for inspecting ZIP file data and listing information about the
 * contents in pure PHP. Data can be streamed from a file or loaded directly.
 *
 * Example usage:
 *
 * <code>
 *
 *   // Load the ZIP file or data
 *   $zip = new ZipInfo;
 *   $zip->open('./foo.zip'); // or $zip->setData($data);
 *   if ($zip->error) {
 *     echo "Error: {$zip->error}\n";
 *     exit;
 *   }
 *
 *   // Check encryption
 *   if ($zip->isEncrypted) {
 *     echo "Archive is encrypted\n";
 *     exit;
 *   }
 *
 *   // Process the file list
 *   $files = $zip->getFileList();
 *   foreach ($files as $file) {
 *     if ($file['pass'] == true) {
 *       echo "File is passworded: {$file['name']}\n";
 *     }
 *     if ($file['compressed'] == false) {
 *       echo "Extracting uncompressed file: {$file['name']}\n";
 *       $zip->saveFileData($file['name'], "./destination/{$file['name']}");
 *       // or $data = $zip->getFileData($file['name']);
 *     }
 *   }
 *
 * </code>
 *
 * The ZIP specification is quite bloated (particularly when it comes to extra
 * fields and OS-specific info) and only a small part of it is implemented here,
 * but there's lots still to explore:
 *
 * @link http://www.pkware.com/documents/casestudies/APPNOTE.TXT
 *
 * @author     Hecks
 * @copyright  (c) 2010-2013 Hecks
 * @license    Modified BSD
 * @version    2.1
 */
class ZipInfo extends ArchiveReader
{
	// ------ Class constants -----------------------------------------------------

	/**#@+
	 * ZIP file format values
	 */

	// Record type signatures
	const RECORD_CENTRAL_FILE         = 0x02014b50;
	const RECORD_LOCAL_FILE           = 0x04034b50;
	const RECORD_SIGNATURE            = 0x05054b50;
	const RECORD_ENDCENTRAL           = 0x06054b50;
	const RECORD_Z64_ENDCENTRAL       = 0x06064b50;
	const RECORD_Z64_ENDCENTRAL_LOC   = 0x07064b50;
	const RECORD_ARCHIVE_EXTRA        = 0x08064b50;
	const RECORD_DATA_DESCR           = 0x08074b50;

	// General purpose flags
	const FILE_ENCRYPTED              = 0x0001;
	const FILE_DESCRIPTOR_USED        = 0x0008;
	const FILE_STRONG_ENCRYPTED       = 0x0040;
	const FILE_EFS_UTF8               = 0x0800;
	const FILE_CDR_ENCRYPTED          = 0x2000;

	// Extra Field IDs
	const EXTRA_ZIP64                 = 0x0001;
	const EXTRA_NTFS                  = 0x000a;
	const EXTRA_UNIX                  = 0x000d;
	const EXTRA_STRONG_ENCR           = 0x0017;
	const EXTRA_POSZIP                = 0x4690;
	const EXTRA_UNIXTIME              = 0x5455;
	const EXTRA_IZUNIX                = 0x5855;
	const EXTRA_IZUNIX2               = 0x7855;
	const EXTRA_IZUNIX3               = 0x7875;
	const EXTRA_WZ_AES                = 0x9901;

	// OS Types
	const OS_FAT      = 0;
	const OS_AMIGA    = 1;
	const OS_VMS      = 2;
	const OS_UNIX     = 3;
	const OS_VM_CMS   = 4;
	const OS_ATARI    = 5;
	const OS_HPFS     = 6;
	const OS_MAC      = 7;
	const OS_Z_SYSTEM = 8;
	const OS_CPM      = 9;
	const OS_NTFS     = 10;
	const OS_MVS      = 11;
	const OS_VSE      = 12;
	const OS_ACORN    = 13;
	const OS_VFAT     = 14;
	const OS_ALT_MVS  = 15;
	const OS_BEOS     = 16;
	const OS_TANDEM   = 17;
	const OS_OS400    = 18;
	const OS_OSX      = 19;

	/**#@-*/

	/**
	 * Format for unpacking Local File records.
	 */
	const FORMAT_LOCAL_FILE = 'Cversion_need_num/Cversion_need_os/vflags/vmethod/vlast_mod_time/vlast_mod_date/Vcrc32/Vcompressed_size/Vuncompressed_size/vfile_name_length/vextra_length';

	/**
	 * Format for unpacking Central File records.
	 */
	const FORMAT_CENTRAL_FILE = 'Cversion_made_num/Cversion_made_os/Cversion_need_num/Cversion_need_os/vflags/vmethod/vlast_mod_time/vlast_mod_date/Vcrc32/Vcompressed_size/Vuncompressed_size/vfile_name_length/vextra_length/vcomment_length/vdisk_start/vattr_int/Vattr_ext/Vrel_offset';

	/**
	 * Format for unpacking End of Central Directory records.
	 */
	const FORMAT_ENDCENTRAL = 'vdisk_num/vstart_disk/ventries_disk/ventries_total/Vcentral_size/Vcentral_offset/vcomment_length';

	/**
	 * Format for unpacking ZIP64 format End of Central Directory records.
	 */
	const FORMAT_Z64_ENDCENTRAL = 'Vcentral_size/Vcentral_size_high/Cversion_made_num/Cversion_made_os/Cversion_need_num/Cversion_need_os/Vdisk_num/Vstart_disk/Ventries_disk/Ventries_disk_high/Ventries_total/Ventries_total_high/Vcentral_offset/Vcentral_offset_high';

	/**
	 * Format for unpacking ZIP64 format End of Central Directory Locator records.
	 */
	const FORMAT_Z64_ENDCENTRAL_LOC = 'Vstart_disk/Vcentral_offset/Vcentral_offset_high/Vtotal_disks';

	/**
	 * Format for unpacking Data Descriptor blocks.
	 */
	const FORMAT_DATA_DESCR = 'Vsignature/Vcrc32/Vcompressed_size/Vuncompressed_size';

	/**
	 * Format for unpacking Extra Field blocks.
	 */
	const FORMAT_EXTRA_FIELD = 'vheaderID/vdata_size';


	// ------ Instance variables and methods ---------------------------------------

	/**
	 * List of record names corresponding to record types.
	 * @var array
	 */
	protected $recordNames = array(
		self::RECORD_CENTRAL_FILE        => 'Central File',
		self::RECORD_LOCAL_FILE          => 'Local File',
		self::RECORD_SIGNATURE           => 'Digital Signature',
		self::RECORD_ENDCENTRAL          => 'End of Central Directory',
		self::RECORD_Z64_ENDCENTRAL      => 'ZIP64 End of Central Directory',
		self::RECORD_Z64_ENDCENTRAL_LOC  => 'ZIP64 End of Central Directory Locator',
		self::RECORD_ARCHIVE_EXTRA       => 'Archive Extra Data',
		self::RECORD_DATA_DESCR          => 'Data Descriptor',
	);

	/**
	 * List of Extra Field names corresponding to header IDs.
	 * @var array
	 */
	protected $extraFieldNames = array(
		self::EXTRA_ZIP64        => 'Zip64',
		self::EXTRA_NTFS         => 'NTFS',
		self::EXTRA_UNIX         => 'Unix',
		self::EXTRA_STRONG_ENCR  => 'Strong Encryption',
		self::EXTRA_POSZIP       => 'POSZIP',
		self::EXTRA_UNIXTIME     => 'Unix Time',
		self::EXTRA_IZUNIX       => 'Info-ZIP (UX)',
		self::EXTRA_IZUNIX2      => 'Info-ZIP (Ux)',
		self::EXTRA_IZUNIX3      => 'Info-ZIP (ux)',
		self::EXTRA_WZ_AES       => 'AES-256 Password Encryption',
	);

	/**
	 * List of Host OS names by type.
	 * @var array
	 */
	protected $hostOSNames = array(
		self::OS_FAT      => 'MS-DOS and OS/2 (FAT)',
		self::OS_AMIGA    => 'Amiga',
		self::OS_VMS      => 'OpenVMS',
		self::OS_UNIX     => 'Unix',
		self::OS_VM_CMS   => 'VM/CMS',
		self::OS_ATARI    => 'Atari',
		self::OS_HPFS     => 'OS/2 HPFS',
		self::OS_MAC      => 'Macintosh',
		self::OS_Z_SYSTEM => 'Z-System',
		self::OS_CPM      => 'CP/M',
		self::OS_NTFS     => 'Windows NTFS',
		self::OS_MVS      => 'MVS (OS/390 - Z/OS)',
		self::OS_VSE      => 'VSE',
		self::OS_ACORN    => 'Acorn Risc',
		self::OS_VFAT     => 'VFAT',
		self::OS_ALT_MVS  => 'Alternative MVS',
		self::OS_BEOS     => 'BEOS',
		self::OS_TANDEM   => 'Tandem',
		self::OS_OS400    => 'OS/400',
		self::OS_OSX      => 'OS X (Darwin)',
	);

	/**
	 * Is the archive Central Directory encrypted?
	 * @var boolean
	 */
	public $isEncrypted = false;

	/**
	 * Convenience method that outputs a summary list of the file/data information,
	 * useful for pretty-printing.
	 *
	 * @param   boolean   $full      add file list to output?
	 * @param   boolean   $skipDirs  should directory entries be skipped?
	 * @param   boolean   $central   should Central File records be used?
	 * @return  array     file/data summary
	 */
	public function getSummary($full=false, $skipDirs=false, $central=false)
	{
		$summary = array(
			'file_name'  => $this->file,
			'file_size'  => $this->fileSize,
			'data_size'  => $this->dataSize,
			'use_range'  => "{$this->start}-{$this->end}",
			'file_count' => $this->fileCount,
		);
		if ($full) {
			$summary['file_list'] = $this->getFileList($skipDirs, $central);
		}
		if ($this->error) {
			$summary['error'] = $this->error;
		}

		return $summary;
	}

	/**
	 * Returns a list of the ZIP records found in the file/data in human-readable
	 * format (for debugging purposes only).
	 *
	 * @return  array|boolean  list of stored records, or false if none available
	 */
	public function getRecords()
	{
		// Check that records are stored
		if (empty($this->records)) {return false;}

		// Build the record list
		$ret = array();

		foreach ($this->records AS $record) {

			$r = array();
			$r['type_name'] = $this->recordNames[$record['type']];
			$r += $record;

			// Sanity check filename length
			if (isset($r['file_name'])) {$r['file_name'] = substr($r['file_name'], 0, $this->maxFilenameLength);}
			$ret[] = $r;
		}

		return $ret;
	}

	/**
	 * Parses the stored records and returns a list of each of the file entries,
	 * optionally using the Central Directory File record instead of the (more
	 * limited) Local File record data. Valid file records include directory entries,
	 * but these can be skipped.
	 *
	 * @return  array  list of file records, empty if none are available
	 */
	public function getFileList($skipDirs=false, $central=false)
	{
		$ret = array();
		foreach ($this->records as $record) {
			if (($central && $record['type'] == self::RECORD_CENTRAL_FILE)
			|| (!$central && $record['type'] == self::RECORD_LOCAL_FILE)
			) {
				if ($skipDirs && !empty($record['is_dir'])) {continue;}
				$ret[] = $this->getFileRecordSummary($record);
			}
		}

		return $ret;
	}

	/**
	 * Retrieves the raw data for the given filename. Note that this is only useful
	 * if the file hasn't been compressed or encrypted.
	 *
	 * @param   string  $filename  name of the file to retrieve
	 * @return  mixed   file data, or false if no file records available
	 */
	public function getFileData($filename)
	{
		// Check that records are stored and data source is available
		if (empty($this->records) || ($this->data == '' && $this->handle == null)) {
			return false;
		}

		// Get the absolute start/end positions
		if (!($info = $this->getFileInfo($filename)) || empty($info['range'])) {
			$this->error = "Could not find file info for: ({$filename})";
			return false;
		}
		$this->error = '';

		return $this->getRange(explode('-', $info['range']));
	}

	/**
	 * Saves the raw data for the given filename to the given destination. Note that
	 * this is only useful if the file isn't compressed or encrypted.
	 *
	 * @param   string   $filename     name of the file to extract
	 * @param   string   $destination  full path of the file to create
	 * @return  integer|boolean  number of bytes saved or false on error
	 */
	public function saveFileData($filename, $destination)
	{
		// Check that records are stored and data source is available
		if (empty($this->records) || ($this->data == '' && $this->handle == null)) {
			return false;
		}

		// Get the absolute start/end positions
		if (!($info = $this->getFileInfo($filename)) || empty($info['range'])) {
			$this->error = "Could not find file info for: ({$filename})";
			return false;
		}
		$this->error = '';

		return $this->saveRange(explode('-', $info['range']), $destination);
	}

	/**
	 * Sets the absolute path to the external 7za client.
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
	 * Extracts a compressed or encrypted file using the configured external 7za
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

		// Set the external command
		$pass = $password ? '-p'.escapeshellarg($password) : '';
		$command = '"'.$this->externalClient.'"'
			." e -so -bd -y -tzip {$pass} -- "
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
		if (($error = @file_get_contents($errorFile)) && strpos($error, 'Everything is Ok') === false) {
			if ($destination) {@unlink($destination);}
			$this->error = $error;
			return false;
		}

		return $destination ? $written : $data;
	}

	/**
	 * List of records found in the file/data.
	 * @var array
	 */
	protected $records = array();

	/**
	 * Full path to the external 7za client.
	 * @var string
	 */
	protected $externalClient = '';

	/**
	 * Returns a processed summary of a Local or Central File record.
	 *
	 * @param   array  $record  a valid file record
	 * @return  array  summary information
	 */
	protected function getFileRecordSummary($record)
	{
		$ret = array(
			'name' => substr($record['file_name'], 0, $this->maxFilenameLength),
			'size' => $record['uncompressed_size'],
			'date' => self::dos2unixtime(($record['last_mod_date'] << 16) | $record['last_mod_time']),
			'pass' => isset($record['is_encrypted']) ? ((int) $record['is_encrypted']) : 0,
			'compressed' => (int) ($record['method'] > 0),
			'next_offset' => $record['next_offset'],
		);
		if (!empty($record['is_dir'])) {
			$ret['is_dir'] = 1;
		} elseif ($record['type'] == self::RECORD_LOCAL_FILE) {
			$start = $this->start + $record['offset'] + 30 + $record['file_name_length'] + $record['extra_length'];
			$end   = min($this->end, $start + $record['uncompressed_size'] - 1);
			$ret['range'] = "{$start}-{$end}";
		}
		if (!empty($record['crc32'])) {
			$ret['crc32'] = dechex($record['crc32']);
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
	 * Returns the position of the starting record signature in the file/data.
	 * An 'empty' ZIP file consists only of an End of Central Directory record.
	 *
	 * @return  mixed  start position, or false if no valid signature found
	 */
	public function findMarker()
	{
		if ($this->markerPosition !== null)
			return $this->markerPosition;

		// Buffer the data to search
		try {
			$buff = $this->read(min($this->length, $this->maxReadBytes));
			$this->rewind();
		} catch (Exception $e) {
			return false;
		}

		// Try to find the first Local File or Central File record
		if (($pos = strpos($buff, pack('V', self::RECORD_LOCAL_FILE))) !== false
		 || ($pos = strpos($buff, pack('V', self::RECORD_CENTRAL_FILE))) !== false
		) {
			return $this->markerPosition = $pos;
		}

		// Otherwise this could be an empty ZIP file
		return $this->markerPosition = strpos($buff, pack('V', self::RECORD_ENDCENTRAL));
	}

	/**
	 * Parses the ZIP data and stores a list of valid records locally.
	 *
	 * @return  boolean  false if parsing fails
	 */
	protected function analyze()
	{
		// Find the first record signature, if there is one
		if (($startPos = $this->findMarker()) === false) {
			$this->error = 'Could not find any records, not a valid ZIP file';
			return false;
		}
		$this->seek($startPos);

		// Analyze all records
		while ($this->offset < $this->length) try {

			// Get the next record header
			if (($record = $this->getNextRecord()) === false)
				continue;

			// Process the current record by type
			$this->processRecord($record);

			// Add the current record to the list
			$this->records[] = $record;

			// Skip to the next record, if any
			if ($this->offset != $record['next_offset']) {
				$this->seek($record['next_offset']);
			}

			// Sanity check
			if ($record['offset'] == $this->offset) {
				$this->error = 'Parsing seems to be stuck';
				$this->close();
				return false;
			}

		// No more readable data, or read error
		} catch (Exception $e) {
			if ($this->error) {$this->close(); return false;}
			break;
		}

		// Check for valid records
		if (empty($this->records)) {
			$this->error = 'No valid ZIP records were found';
			return false;
		}

		// Analysis was successful
		return true;
	}

	/**
	 * Reads the start of the next record header and checks the header signature
	 * before further processing by record type.
	 *
	 * @return  mixed  the next record info, or false on invalid signature
	 */
	protected function getNextRecord()
	{
		// Start the record info
		$record = array('offset' => $this->offset);

		// Unpack the record signature
		$record += self::unpack('Vtype', $this->read(4));

		// Check that the record signature is valid
		if (!isset($this->recordNames[$record['type']])) {
			$this->seek($this->offset - 3);
			return false;
		}

		// Return the record info
		return $record;
	}

	/**
	 * Processes a record passed by reference based on its type. We start with just
	 * the header signature, and unpack the rest of each header/body from there.
	 *
	 * @param   array  $record  the record to process
	 * @return  void
	 */
	protected function processRecord(&$record)
	{
		// Record type: LOCAL FILE
		if ($record['type'] == self::RECORD_LOCAL_FILE) {
			$record += self::unpack(self::FORMAT_LOCAL_FILE, $this->read(26));
			$record['file_name'] = $this->read($record['file_name_length']);
			if ($record['extra_length'] > 0) {
				$this->processExtraFields($record);
			}
			$record['next_offset'] = $this->offset + $record['compressed_size'];
			$this->fileCount++;

			// Data Descriptor follows file data?
			if ($record['flags'] & self::FILE_DESCRIPTOR_USED) {
				$this->seek($record['next_offset']);
				$descr = self::unpack(self::FORMAT_DATA_DESCR, $this->read(16));
				$record['has_descriptor'] = true;
				$record['crc32'] = $descr['crc32'];
				$record['compressed_size'] = $descr['compressed_size'];
				$record['uncompressed_size'] = $descr['uncompressed_size'];
				$record['next_offset'] = $this->offset;
			}
		}

		// Record type: CENTRAL FILE
		elseif ($record['type'] == self::RECORD_CENTRAL_FILE) {
			$record += self::unpack(self::FORMAT_CENTRAL_FILE, $this->read(42));
			$record['file_name'] = $this->read($record['file_name_length']);
			if ($record['extra_length'] > 0) {
				$this->processExtraFields($record);
			}
			if ($record['comment_length'] > 0) {
				$record['comment'] = $this->read($record['comment_length']);
			}
			$record['next_offset'] = $this->offset;
		}

		// Record type: END OF CENTRAL DIRECTORY
		elseif ($record['type'] == self::RECORD_ENDCENTRAL) {
			$record += self::unpack(self::FORMAT_ENDCENTRAL, $this->read(18));
			if ($record['comment_length'] > 0) {
				$record['comment'] = $this->read($record['comment_length']);
			}
			$record['next_offset'] = $this->offset;
			$this->fileCount = $record['entries_disk'];
		}

		// Record type: ZIP64 END OF CENTRAL DIRECTORY
		elseif ($record['type'] == self::RECORD_Z64_ENDCENTRAL) {
			$record += self::unpack(self::FORMAT_Z64_ENDCENTRAL, $this->read(50));
			$record['next_offset'] = $record['offset'] + self::int64($record['central_size'], $record['central_size_high']);
			$this->fileCount = self::int64($record['entries_disk'], $record['entries_disk_high']);
		}

		// Record type: ZIP64 END OF CENTRAL DIRECTORY LOCATOR
		elseif ($record['type'] == self::RECORD_Z64_ENDCENTRAL_LOC) {
			$record += self::unpack(self::FORMAT_Z64_ENDCENTRAL_LOC, $this->read(16));
			$record['next_offset'] = $this->offset;
		}

		// Skip everything else
		else {
			$record['next_offset'] = $this->offset + 1;
		}

		// Process any version numbers (-> major.minor)
		if (isset($record['version_made_num'])) {
			$num = $record['version_made_num'];
			$record['made_version'] = floor($num / 10).'.'.($num % 10);
		}
		if (isset($record['version_need_num'])) {
			$num = $record['version_need_num'];
			$record['need_version'] = floor($num / 10).'.'.($num % 10);
		}

		// Process Host OS info
		if (isset($record['version_made_os'])) {
			$os = $record['version_made_os'];
			$record['made_host_os'] = isset($this->hostOSNames[$os]) ? $this->hostOSNames[$os] : 'Unknown';
		}
		if (isset($record['version_need_os'])) {
			$os = $record['version_need_os'];
			$record['need_host_os'] = isset($this->hostOSNames[$os]) ? $this->hostOSNames[$os] : 'Unknown';
		}

		if ($record['type'] == self::RECORD_LOCAL_FILE || $record['type'] == self::RECORD_CENTRAL_FILE) {

			// Is the file encrypted?
			if ($record['flags'] & self::FILE_ENCRYPTED) {
				$record['is_encrypted'] = true;
			}

			// Is the Central Directory encrypted (masking Local File values)?
			if ($record['flags'] & self::FILE_CDR_ENCRYPTED) {
				$this->isEncrypted = true;
			}

			// Is this a directory entry? (quick check)
			if ($record['file_name'][$record['file_name_length'] - 1] == '/') {
				$record['is_dir'] = true;
			}

			// Is UTF8 encoding used?
			if ($record['flags'] & self::FILE_EFS_UTF8) {
				$record['is_utf8'] = true;
			}
		}
	}

	/**
	 * Processes Extra Field blocks for the current record.
	 *
	 * @param   array  $record  the current record to process
	 * @return  void
	 */
	protected function processExtraFields(&$record)
	{
		$end = $this->offset + $record['extra_length'];
		while ($this->offset < $end)
		{
			$field = array('type_name' => '');
			$field += self::unpack(self::FORMAT_EXTRA_FIELD, $this->read(4));
			$field['type_name'] = isset($this->extraFieldNames[$field['headerID']]) ? $this->extraFieldNames[$field['headerID']] : 'Unknown';

			// Field: ZIP64 format
			if ($field['headerID'] == self::EXTRA_ZIP64) {

				// Values are only included if the record values are set to 0xFFFFFFFF or 0xFFFF
				if ($record['uncompressed_size'] == 0xFFFFFFFF) {
					$field += self::unpack('Vuncompressed_size/Vuncompressed_size_high', $this->read(8));
					$record['uncompressed_size'] = self::int64($field['uncompressed_size'], $field['uncompressed_size_high']);
				}
				if ($record['compressed_size'] == 0xFFFFFFFF) {
					$field += self::unpack('Vcompressed_size/Vcompressed_size_high', $this->read(8));
					$record['compressed_size'] = self::int64($field['compressed_size'], $field['compressed_size_high']);
				}
				if (isset($record['rel_offset']) && $record['rel_offset'] == 0xFFFFFFFF) {
					$field += self::unpack('Vrel_offset/Vrel_offset_high', $this->read(8));
					$record['rel_offset'] = self::int64($field['rel_offset'], $field['rel_offset_high']);
				}
				if (isset($record['disk_start']) && $record['disk_start'] == 0xFFFF) {
					$field += self::unpack('Vdisk_start', $this->read(4));
					$record['disk_start'] = $field['disk_start'];
				}
			}

			// Field: UNIXTIME
			elseif ($field['headerID'] == self::EXTRA_UNIXTIME) {
				// Probably could do something with this
				$this->read($field['data_size']);

			// Default: skip field
			} else {
				$this->read($field['data_size']);
			}

			// Add the extra field info to the record
			$field['end_offset'] = $this->offset;
			$record['extra_fields'][] = $field;
		}
	}


	/**
	 * Resets the instance variables before parsing new data.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		parent::reset();

		$this->records = array();
		$this->isEncrypted = false;
	}

} // End ZipInfo class
