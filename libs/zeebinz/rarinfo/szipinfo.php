<?php

require_once dirname(__FILE__) . '/archivereader.php';
require_once dirname(__FILE__) . '/pipereader.php';

/**
 * SzipInfo class.
 *
 * A simple class for inspecting 7-zip (.7z) archive files and listing information
 * about their contents. Data can be streamed from a file or loaded directly.
 *
 * Technical note: this class is quite flakey, because of the many quirks of the 7z
 * format spec. If you really must use it, you should be aware that:
 *
 * 1) The headers for 7z archives are at the start and the end of files. Most of
 *    the useful info about file contents is in the end headers. The data streams
 *    are packed in blocks between the start and end headers, without separators.
 *
 * 2) If you're handling 7z fragments, aim for the end of the file, or the last
 *    volume in a split set. That should at least get some filenames. Probably.
 *    The other volumes contain no headers at all, just split streams.
 *
 * 3) Headers are compressed by default by the 7-zip client, although this can be
 *    disabled manually with a switch that nobody uses. So ... this class is just
 *    about useless without using an external client to extract the headers.
 *
 * You probably want to give up at this point and try one of the wrappers for the
 * 7-zip/7za clients or something with direct bindings, like these:
 *
 *    Archive_7Z for PHP : https://github.com/Gemorroj/Archive_7z
 *    pyLZMA for Python  : http://www.joachim-bauch.de/projects/pylzma/
 *
 * What you'll be missing is integration with the rest of the library, handling of
 * archive fragments, and full recursive support through ArchiveInfo. Or just a
 * way of digging deeper into the archive structure if needed. But configure that
 * external client first! 7za.exe (Windows) or p7zip (*nix):
 *
 * @link http://www.7-zip.org/download.html
 * @link http://p7zip.sourceforge.net/
 *
 * @author     Hecks
 * @copyright  (c) 2010-2013 Hecks
 * @license    Modified BSD
 * @version    1.4
 */
class SzipInfo extends ArchiveReader
{
	// ------ Class constants -----------------------------------------------------

	/**#@+
	 * 7z file format values
	 */

	// Main header types
	const PROPERTY_HEADER                  = 0x01;
	const PROPERTY_ARCHIVE_PROPERTIES      = 0x02;
	const PROPERTY_ADDITIONAL_STREAMS_INFO = 0x03;
	const PROPERTY_MAIN_STREAMS_INFO       = 0x04;
	const PROPERTY_FILES_INFO              = 0x05;
	const PROPERTY_ENCODED_HEADER          = 0x17;

	// Streams Info
	const PROPERTY_PACK_INFO               = 0x06;
	const PROPERTY_UNPACK_INFO             = 0x07;
	const PROPERTY_SUBSTREAMS_INFO         = 0x08;

	// Pack Info etc.
	const PROPERTY_SIZE                    = 0x09;
	const PROPERTY_CRC                     = 0x0a;

	// Unpack Info
	const PROPERTY_FOLDER                  = 0x0b;
	const PROPERTY_CODERS_UNPACK_SIZE      = 0x0c;

	// Substreams Info
	const PROPERTY_NUM_UNPACK_STREAM       = 0x0d;

	// Files Info
	const PROPERTY_EMPTY_STREAM            = 0x0e;
	const PROPERTY_EMPTY_FILE              = 0x0f;
	const PROPERTY_ANTI                    = 0x10;
	const PROPERTY_NAME                    = 0x11;
	const PROPERTY_CREATION_TIME           = 0x12;
	const PROPERTY_LAST_ACCESS_TIME        = 0x13;
	const PROPERTY_LAST_WRITE_TIME         = 0x14;
	const PROPERTY_ATTRIBUTES              = 0x15;

	// General properties
	const PROPERTY_END                     = 0x00;
	const PROPERTY_COMMENT                 = 0x16;
	const PROPERTY_START_POSITION          = 0x18;
	const PROPERTY_DUMMY                   = 0x19;

	// Encoding methods
	const METHOD_COPY      = '00';
	const METHOD_LZMA      = '03';
	const METHOD_CRYPTO    = '06';
	const METHOD_7Z_AES    = '06f10701';

	/**#@-*/

	/**
	 * Byte string marking the start of the file/data.
	 */
	const MARKER_SIGNATURE = "7z\xbc\xaf\x27\x1c";

	/**
	 * Type, format and size of the Start header following the signature.
	 */
	const START_HEADER         = 0x100;
	const START_HEADER_FORMAT  = 'Cversion_major/Cversion_minor/Vhead_crc/Vnext_head_offset/Vnext_head_offset_high/Vnext_head_size/Vnext_head_size_high/Vnext_head_crc';
	const START_HEADER_SIZE    = 26;


	// ------ Instance variables and methods ---------------------------------------

	/**
	 * List of header names corresponding to header types.
	 * @var array
	 */
	protected $headerNames = array(
		self::START_HEADER                      => 'Start',
		self::PROPERTY_HEADER                   => 'Header',
		self::PROPERTY_ADDITIONAL_STREAMS_INFO  => 'Additional Streams Info',
		self::PROPERTY_MAIN_STREAMS_INFO        => 'Main Streams Info',
		self::PROPERTY_FILES_INFO               => 'Files Info',
		self::PROPERTY_ENCODED_HEADER           => 'Encoded Header',
		self::PROPERTY_END                      => 'End',
	);

	/**
	 * Are the archive headers encrypted?
	 * @var boolean
	 */
	public $isEncrypted = false;

	/**
	 * Is the archive packed as a solid stream?
	 * @var boolean
	 */
	public $isSolid = false;

	/**
	 * The number of packed streams in the archive.
	 * @var integer
	 */
	public $blockCount = 0;

	/**
	 * Convenience method that outputs a summary list of the file/data information,
	 * useful for pretty-printing.
	 *
	 * @param   boolean   $full      add file list to output?
	 * @param   boolean   $skipDirs  should directory entries be skipped?
	 * @return  array     file/data summary
	 */
	public function getSummary($full=false, $skipDirs=false)
	{
		$summary = array(
			'file_name'    => $this->file,
			'file_size'    => $this->fileSize,
			'data_size'    => $this->dataSize,
			'use_range'    => "{$this->start}-{$this->end}",
			'solid_pack'   => (int) $this->isSolid,
			'enc_header'   => (int) $this->hasEncodedHeader,
			'is_encrypted' => (int) $this->isEncrypted,
			'num_blocks'   => $this->blockCount,
		);
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
	 * Returns a list of the 7z headers found in the file/data in human-readable
	 * format (for debugging purposes only).
	 *
	 * @return  array|boolean  list of stored headers, or false if none available
	 */
	public function getHeaders()
	{
		if (empty($this->headers)) {return false;}
		$ret = array();

		foreach ($this->headers as $header) {
			$h = array();
			$h['type_name'] = isset($this->headerNames[$header['type']])
				? $this->headerNames[$header['type']] : 'Unknown';
			$h += $header;
			$ret[] = $h;
		}

		return $ret;
	}

	/**
	 * Parses the stored headers and returns a list of records for each of the
	 * files in the archive.
	 *
	 * @param   boolean  $skipDirs  should directory entries be skipped?
	 * @return  array  list of file records, empty if none are available
	 */
	public function getFileList($skipDirs=false)
	{
		// Check that headers are stored
		if (!($info = $this->getFilesHeaderInfo()) || empty($info['files']))
			return array();

		// Files may be stored in their own folders or as substreams
		$streams = $this->getMainStreamsInfo();
		if (!empty($streams['substreams']['unpack_sizes'])) {
			$unpackSizes = $streams['substreams']['unpack_sizes'];
		} elseif (!empty($streams['folders'])) {
			foreach ($streams['folders'] as $folder) {
				$unpackSizes[] = $this->getFolderUnpackSize($folder);
			}
		}
		$packRanges = $this->getPackedRanges();
		$ret = array();

		// Collate the file & streams info
		$folderIndex = $sizeIndex = $streamIndex = 0;
		foreach ($info['files'] as $file) {
			$item = array(
				'name' => substr($file['file_name'], 0, $this->maxFilenameLength),
				'size' => ($file['has_stream'] && isset($unpackSizes[$sizeIndex])) ? $unpackSizes[$sizeIndex] : 0,
				'date' => isset($file['utime']) ? $file['utime'] : 0,
				'pass' => 0,
				'compressed' => 0,
			);
			if (!empty($file['is_dir'])) {
				if ($skipDirs) {continue;}
				$item['is_dir'] = 1;
			}
			if ($file['has_stream']) {
				$numStreamsInFolder = 1;
				if (!empty($streams['folders'][$folderIndex])) {
					$folder = $streams['folders'][$folderIndex];
					$item['pass'] = $folder['is_encrypted'];
					$item['compressed'] = $folder['is_compressed'];
					$item['block'] = $folderIndex;
					if (isset($streams['substreams']['num_unpack_streams'][$folderIndex])) {
						$numStreamsInFolder = $streams['substreams']['num_unpack_streams'][$folderIndex];
					}
				}
				if ($packRanges[$folderIndex] != null) {
					$item['range'] = $packRanges[$folderIndex];
				}
				if (!empty($streams['substreams']['digests_defined'][$sizeIndex])) {
					$item['crc32'] = dechex($streams['substreams']['digests'][$sizeIndex]);
				}
				if (++$streamIndex == $numStreamsInFolder) {
					$streamIndex = 0;
					$folderIndex++;
				}
				$sizeIndex++;
			}
			$ret[] = $item;
		}

		return $ret;
	}

	/**
	 * Retrieves the raw data for the given filename. Note that this is only useful
	 * if the file hasn't been compressed or encrypted.
	 *
	 * @param   string  $filename  name of the file to retrieve
	 * @return  mixed   file data, or false if no file info available
	 */
	public function getFileData($filename)
	{
		// Check that headers are stored and data source is available
		if (empty($this->headers) || ($this->data == '' && $this->handle == null)) {
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
	 * Saves the raw data for the given filename to the given destination. Note
	 * that this is only useful if the file isn't compressed or encrypted.
	 *
	 * @param   string   $filename     name of the file to extract
	 * @param   string   $destination  full path of the file to create
	 * @return  integer|boolean  number of bytes saved or false on error
	 */
	public function saveFileData($filename, $destination)
	{
		// Check that headers are stored and data source is available
		if (empty($this->headers) || ($this->data == '' && $this->handle == null)) {
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
	 * Sets the archive password for decoding encypted headers.
	 *
	 * @param   string   $password  the password
	 * @return  void
	 */
	public function setPassword($password)
	{
		$this->password = $password;
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
			." e -so -bd -y -t7z {$pass} -- "
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
	 * Returns the position of the starting header signature in the file/data.
	 *
	 * @return  mixed  start position, or false if no valid signature found
	 */
	public function findMarker()
	{
		if ($this->markerPosition !== null)
			return $this->markerPosition;

		try {
			$buff = $this->read(min($this->length, $this->maxReadBytes));
			$this->rewind();
			return $this->markerPosition = strpos($buff, self::MARKER_SIGNATURE);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * List of headers found in the file/data.
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Are the archive headers encoded?
	 * @var boolean
	 */
	protected $hasEncodedHeader = false;

	/**
	 * The archive password for decoding encrypted headers.
	 * @var string
	 */
	protected $password = '';

	/**
	 * Full path to the external 7za client.
	 * @var string
	 */
	protected $externalClient = '';

	/**
	 * Parses the 7z data and stores a list of valid headers locally.
	 *
	 * @return  boolean  false if parsing fails
	 */
	protected function analyze()
	{
		// Find the marker signature, if there is one
		$startPos = $this->findMarker();
		if ($startPos === false && !$this->isFragment) {

			// Not a 7z fragment or valid file, so abort here
			$this->error = 'Could not find marker signature, not a valid 7z file';
			return false;

		} elseif ($startPos !== false) {

			// Unpack the Start header
			$this->seek($startPos + strlen(self::MARKER_SIGNATURE));
			$header = $this->readStartHeader();
			$this->headers[] = $header;

			// Go to the next header if available
			$this->seek(min($header['next_offset'], $this->length));

		} elseif ($this->isFragment) {

			// Search for a valid header and continue unpacking from there
			if (($startPos = $this->findHeader()) === false) {
				$this->error = 'Could not find a valid 7z header';
				return false;
			}
			$this->seek($startPos);
		}

		// Analyze all headers
		while ($this->offset < $this->length) try {

			// Get the next header
			if (($header = $this->readNextHeader()) === false)
				break;

			// Add the current header to the list
			$this->headers[] = $header;

			// Skip to the next header, if any
			if ($this->offset != $header['next_offset']) {
				$this->seek($header['next_offset']);
			}

			// Sanity check
			if ($header['offset'] == $this->offset) {
				$this->error = 'Parsing seems to be stuck';
				$this->close();
				return false;
			}

		// No more readable data, or read error
		} catch (Exception $e) {
			if ($this->error) {$this->close(); return false;}
			break;
		}

		// Check for valid headers
		if (empty($this->headers)) {
			$this->error = 'No valid 7z headers were found';
			return false;
		}

		// Analysis was successful
		if ($this->hasEncodedHeader && $this->externalClient) {
			return $this->extractHeaders();
		}
		return true;
	}

	/**
	 * Searches for the position of a valid header up to maxReadBytes, and sets
	 * it as the start of the data to analyze.
	 *
	 * @return  integer|boolean  the header offset, or false if none is found
	 */
	protected function findHeader()
	{
		// Buffer the data to search
		$start = $this->offset;
		try {
			$buffer = $this->read(min($this->length, $this->maxReadBytes));
			$this->rewind();
		} catch (Exception $e) {return false;}

		// Get all the offsets to test
		$searches = array(
			'unencoded' => pack('C*', self::PROPERTY_HEADER, self::PROPERTY_MAIN_STREAMS_INFO),
			'encoded'   => pack('C*', self::PROPERTY_ENCODED_HEADER, self::PROPERTY_PACK_INFO),
		);
		if (!($positions = self::strposall($buffer, $searches)))
			return false;

		foreach ($positions as $offset => $matches) try {
			$offset += $start;
			$this->seek(($matches[0] == 'encoded') ? $offset : $offset + 1);

			// Verify the Header or Encoded Header data
			if (($header = $this->readNextHeader()) && $this->sanityCheckStreamsInfo($header)) {
				return $this->markerPosition = $offset;
			}

		// No more readable data, or read error
		} catch (Exception $e) {continue;}

		return false;
	}

	/**
	 * Unpacks the Start header info from the current offset.
	 *
	 * @return  array|boolean  the Start header info, or false on error
	 */
	protected function readStartHeader()
	{
		$header = array(
			'offset' => $this->offset,
			'type'   => self::START_HEADER,
		);
		try {
			$header += self::unpack(self::START_HEADER_FORMAT, $this->read(self::START_HEADER_SIZE));
		} catch (Exception $e) {
			return false;
		}
		$header['next_head_offset'] = self::int64($header['next_head_offset'], $header['next_head_offset_high']);
		$header['next_head_size'] = self::int64($header['next_head_size'], $header['next_head_size_high']);
		$header['next_offset'] = $this->offset + $header['next_head_offset'];
		$header['data_offset'] = $this->offset;

		return $header;
	}

	/**
	 * Reads the start of the next header before further processing by type.
	 *
	 * @return  array|boolean  the next header info, or false on error
	 */
	protected function readNextHeader()
	{
		$header = array(
			'offset'      => $this->offset,
			'type'        => ord($this->read(1)),
			'next_offset' => $this->length,
		);
		switch ($header['type']) {

			// Start/end header markers
			case self::PROPERTY_HEADER:
			case self::PROPERTY_END:
				$header['next_offset'] = $header['offset'] + 1;
				return $header;

			case self::PROPERTY_ARCHIVE_PROPERTIES:
				return $this->processArchiveProperties($header);

			case self::PROPERTY_ADDITIONAL_STREAMS_INFO:
			case self::PROPERTY_MAIN_STREAMS_INFO:
			case self::PROPERTY_ENCODED_HEADER:
				return $this->processStreamsInfo($header);

			case self::PROPERTY_FILES_INFO:
				return $this->processFilesInfo($header);

			// Unknown types
			default:
				return $header;
		}
	}

	/**
	 * Reads & parses info about various archive streams from the current offset,
	 * and adds it to the given header record.
	 *
	 * @param   array    $header  a valid header record
	 * @return  boolean  false on error
	 */
	protected function processStreamsInfo(&$header)
	{
		$nid = ord($this->read(1));

		// Pack Info
		if ($nid == self::PROPERTY_PACK_INFO) {
			if (!$this->processPackInfo($header))
				return false;
			$this->isSolid = (bool) $header['is_solid'];
			$nid = ord($this->read(1));
		}

		// Unpack Info
		if ($nid == self::PROPERTY_UNPACK_INFO) {
			if (!$this->processUnpackInfo($header))
				return false;
			$this->blockCount = $header['num_folders'];
			$nid = ord($this->read(1));
		}

		// Substreams Info
		if ($nid == self::PROPERTY_SUBSTREAMS_INFO) {
			if (!$this->processSubstreamsInfo($header))
				return false;
			$nid = ord($this->read(1));
		}

		// End Streams Info
		if (!$this->checkIsEnd($nid)) {return false;}
		$this->hasEncodedHeader = ($header['type'] == self::PROPERTY_ENCODED_HEADER);
		$header['next_offset'] = $this->offset;

		return $header;
	}

	/**
	 * Reads & parses basic info about the packed streams in the archive.
	 *
	 * @param   array    $header  a valid header record
	 * @return  boolean  false on error
	 */
	protected function processPackInfo(&$header)
	{
		$header['pack_offset'] = $this->readNumber();
		$header['num_streams'] = $this->readNumber();
		$header['is_solid'] = (int) ($header['num_streams'] == 1);
		$nid = ord($this->read(1));

		// Packed sizes
		if ($nid == self::PROPERTY_SIZE) {
			$header['pack_sizes'] = array();
			for ($i = 0; $i < $header['num_streams']; $i++) {
				$header['pack_sizes'][$i] = $this->readNumber();
			}
			$nid = ord($this->read(1));

			// Packed CRC digests
			if ($nid == self::PROPERTY_CRC) {
				$digests = $this->readDigests($header['num_streams']);
				$header['pack_digests_defined'] = array();
				$header['pack_crcs'] = array();
				for ($i = 0; $i < $header['num_streams']; $i++) {
					$header['pack_digests_defined'][$i] = $digests['defined'][$i];
					$header['pack_crcs'][$i] = $digests['crcs'][$i];
				}
				$nid = ord($this->read(1));
			}
		}

		return $this->checkIsEnd($nid);
	}

	/**
	 * Reads & parses further info about the contents of the packed streams.
	 *
	 * @param   array    $header  a valid header record
	 * @return  boolean  false on error
	 */
	protected function processUnpackInfo(&$header)
	{
		$nid = ord($this->read(1));

		// Folders (packed stream blocks)
		if ($nid != self::PROPERTY_FOLDER) {
			$this->error = "Expecting PROPERTY_FOLDER but found: {$nid} at: ".($this->offset - 1);
			return false;
		}
		$header['num_folders'] = $this->readNumber();
		if (!$this->checkExternal()) {return false;}
		$this->processFolders($header);
		$nid = ord($this->read(1));

		// Unpack sizes
		if ($nid != self::PROPERTY_CODERS_UNPACK_SIZE) {
			$this->error = "Expecting PROPERTY_CODERS_UNPACK_SIZE but found: {$nid} at: ".($this->offset - 1);
			return false;
		}
		foreach ($header['folders'] as &$folder) {
			$folder['unpack_sizes'] = array();
			for ($i = 0; $i < $folder['total_out_streams']; $i++) {
				$folder['unpack_sizes'][] = $this->readNumber();
			}
		}
		$nid = ord($this->read(1));

		// Unpack digests
		if ($nid == self::PROPERTY_CRC) {
			$digests = $this->readDigests($header['num_folders']);
			for ($i = 0; $i < $header['num_folders']; $i++) {
				$header['folders'][$i]['digest_defined'] = $digests['defined'][$i];
				$header['folders'][$i]['unpack_crc'] = $digests['crcs'][$i];
			}
			$nid = ord($this->read(1));
		}

		return $this->checkIsEnd($nid);
	}

	/**
	 * Reads & parses info about the packed 'folders' or stream blocks. These
	 * may combine data from multiple files as substreams to improve compression,
	 * and may each use multiple (chained) encoding methods.
	 *
	 * @param   array  $header  a valid header record
	 * @return  void
	 */
	protected function processFolders(&$header)
	{
		$header['folders'] = array();

		for ($f = 0; $f < $header['num_folders']; $f++) {
			$folder = array(
				'is_encrypted'  => 0,
				'is_compressed' => 0,
				'num_coders'    => $this->readNumber(),
			);
			$totalInStreams = $totalOutStreams = 0;

			// Coders info
			$folder['coders'] = array();
			for ($c = 0; $c < $folder['num_coders']; $c++) {
				$coder = array();
				$coder['flags'] = ord($this->read(1));
				$codecSize = $coder['flags'] & 0x0f;
				$isComplex = $coder['flags'] & 0x10;
				$hasProps  = $coder['flags'] & 0x20;

				// In/out streams
				$coder += self::unpack('H*method', $this->read($codecSize));
				$coder['num_in_streams'] = $isComplex ? $this->readNumber() : 1;
				$coder['num_out_streams'] = $isComplex ? $this->readNumber() : 1;
				$totalInStreams  += $coder['num_in_streams'];
				$totalOutStreams += $coder['num_out_streams'];

				// Properties
				if ($hasProps) {
					$propSize = $this->readNumber();
					$coder['prop_size'] = $propSize;
					$coder += self::unpack('H*properties', $this->read($propSize));
				}
				$folder['coders'][] = $coder;

				// Encryption & compression
				if ($coder['method'] == self::METHOD_7Z_AES) {
					$folder['is_encrypted'] = 1;
					if ($header['type'] == self::PROPERTY_ENCODED_HEADER) {
						$this->isEncrypted = true;
					}
				} elseif ($coder['method'] != self::METHOD_COPY) {
					$folder['is_compressed'] = 1;
				}
			}
			$folder['total_out_streams'] = $totalOutStreams;
			$folder['total_in_streams']  = $totalInStreams;

			// Bind pairs
			$folder['num_bind_pairs'] = $numBindPairs = $totalOutStreams - 1;
			$bindPairs = array();
			if ($numBindPairs > 0) {
				for ($p = 0; $p < $numBindPairs; $p++) {
					$bindPairs[] = array(
						'in'  => $this->readNumber(),
						'out' => $this->readNumber(),
					);
				}
				$folder['bind_pairs'] = $bindPairs;
			}

			// Packed indexes
			$folder['num_packed_streams'] = $numPackedStreams = $totalInStreams - $numBindPairs;
			$packedIndexes = array();
			if ($numPackedStreams == 1) {
				for ($i = 0; $i < $totalInStreams; $i++) {
					if ($this->findBindPair($bindPairs, $i, 'in') < 1) {
						$packedIndexes[] = $i;
					}
				}
			} elseif ($numPackedStreams > 1) {
				for ($i = 0; $i < $numPackedStreams; $i++) {
					$packedIndexes[] = $this->readNumber();
				}
			}
			$folder['packed_indexes'] = $packedIndexes;
			$header['folders'][] = $folder;
		}
	}

	/**
	 * Reads & parses info about the substreams in each packed 'folder'.
	 *
	 * @param   array    $header  a valid header record
	 * @return  boolean  false on error
	 */
	protected function processSubstreamsInfo(&$header)
	{
		if (empty($header['folders'])) {
			$this->error = 'No folders found, cannot process substreams info';
			return false;
		}
		$nid = ord($this->read(1));
		$subs = array();

		// Number of unpack streams in each folder
		if ($nid == self::PROPERTY_NUM_UNPACK_STREAM) {
			$subs['num_unpack_streams'] = array();
			for ($i = 0; $i < $header['num_folders']; $i++) {
				$subs['num_unpack_streams'][] = $this->readNumber();
			}
			$nid = ord($this->read(1));
		} else {
			$subs['num_unpack_streams'] = array_fill(0, $header['num_folders'], 1);
		}

		// Substream unpack sizes
		if ($nid == self::PROPERTY_SIZE) {
			$subs['unpack_sizes'] = array();
			for ($i = 0; $i < $header['num_folders']; $i++) {
				$sum = 0;
				if (($numStreams = $subs['num_unpack_streams'][$i]) == 0)
					continue;
				for ($j = 1; $j < $numStreams; $j++) {
					$size = $this->readNumber();
					$subs['unpack_sizes'][] = $size;
					$sum += $size;
				}
				$subs['unpack_sizes'][] = $this->getFolderUnpackSize($header['folders'][$i]) - $sum;
			}
			$nid = ord($this->read(1));
		}

		// Substream unpack digests (for streams with unknown CRC)
		$numDigests = $numDigestsTotal = 0;
		for ($i = 0; $i < $header['num_folders']; $i++) {
			$numStreams = $subs['num_unpack_streams'][$i];
			if ($numStreams != 1 || empty($header['folders'][$i]['digest_defined'])) {
				$numDigests += $numStreams;
			}
			$numDigestsTotal += $numStreams;
		}
		if ($nid == self::PROPERTY_CRC) {
			$subs['digests_defined'] = array();
			$subs['digests'] = array();
			$digests = $this->readDigests($numDigests);
			$digestIndex = 0;
			for ($i = 0; $i < $header['num_folders']; $i++) {
				$numStreams = $subs['num_unpack_streams'][$i];
				if ($numStreams == 1 && !empty($header['folders'][$i]['digest_defined'])) {
					$subs['digests_defined'][] = 1;
					$subs['digests'][] = $header['folders'][$i]['unpack_crc'];
				} else {
					for ($j = 0; $j < $numStreams; $j++, $digestIndex++) {
						$subs['digests_defined'][] = $digests['defined'][$digestIndex];
						$subs['digests'][] = $digests['crcs'][$digestIndex];
					}
				}
			}
			$nid = ord($this->read(1));
		} else {
			$subs['digests_defined'] = array_fill(0, $numDigestsTotal, 0);
			$subs['digests'] = array_fill(0, $numDigestsTotal, 0);
		}

		$header['substreams'] = $subs;
		return $this->checkIsEnd($nid);
	}

	/**
	 * Reads & parses information about the files stored in the archive.
	 *
	 * @param   array    $header  a valid header record
	 * @return  boolean  false on error
	 */
	protected function processFilesInfo(&$header)
	{
		// Start the file list
		$header['num_files'] = $this->fileCount = $this->readNumber();
		$header['files'] = array();
		for ($i = 0; $i < $header['num_files']; $i++) {
			$header['files'][$i]['has_stream'] = 1;
		}
		$numEmptyStreams = 0;

		// Read the file info properties
		while ($this->offset < $this->length) {

			// Property type & size
			$type = $this->readNumber();
			if ($type > 255) {
				$this->error = "Invalid type, must be below 256: {$type} at: ".($this->offset - 1);
				return false;
			} elseif ($type == self::PROPERTY_END) {
				break;
			}
			$size = $this->readNumber();

			// File names
			if ($type == self::PROPERTY_NAME) {
				if (!$this->checkExternal()) {return false;}
				foreach ($header['files'] as &$file) {
					$name = '';
					while ($this->offset < $this->length) {
						$data = $this->read(2);
						if ($data == "\x00\x00") {
							$file['file_name'] = @iconv('UTF-16LE', 'UTF-8//IGNORE//TRANSLIT', $name);
							break;
						}
						$name .= $data;
					}
				}
			}

			// File times
			elseif ($type == self::PROPERTY_LAST_WRITE_TIME) {
				$this->processFileTimes($header, 'mtime');
			}
			elseif ($type == self::PROPERTY_CREATION_TIME) {
				$this->processFileTimes($header, 'ctime');
			}
			elseif ($type == self::PROPERTY_LAST_ACCESS_TIME) {
				$this->processFileTimes($header, 'atime');
			}

			// File attributes
			elseif ($type == self::PROPERTY_ATTRIBUTES) {
				$defined = $this->readBooleans($header['num_files'], true);
				if (!$this->checkExternal()) {return false;}
				foreach ($header['files'] as $i => &$file) {
					if ($defined[$i] == 1) {
						$file += self::unpack('Vattributes', $this->read(4));
					} else {
						$file['attributes'] = null;
					}
				}
			}

			// Start positions
			elseif ($type == self::PROPERTY_START_POSITION) {
				$defined = $this->readBooleans($header['num_files'], true);
				if (!$this->checkExternal()) {return false;}
				foreach ($header['files'] as $i => &$file) {
					if ($defined[$i] == 1) {
						$sp = self::unpack('Vlow/Vhigh', $this->read(8));
						$file['start_pos'] = self::int64($sp['low'], $sp['high']);
					} else {
						$file['start_pos'] = null;
					}
				}
			}

			// Empty streams/files flags
			elseif ($type == self::PROPERTY_EMPTY_STREAM) {
				$header['empty_streams'] = $this->readBooleans($header['num_files']);
				$numEmptyStreams = array_sum($header['empty_streams']);
			}
			elseif ($type == self::PROPERTY_EMPTY_FILE) {
				$header['empty_files'] = $this->readBooleans($numEmptyStreams);
			}
			elseif ($type == self::PROPERTY_ANTI) {
				$header['anti_files'] = $this->readBooleans($numEmptyStreams);
			}

			// Skip unknowns
			else {$this->read($size);}
		}

		// Process empty streams/files
		$emptyFileIndex = 0;
		foreach ($header['files'] as $i => &$file) {
			if (!empty($header['empty_streams'][$i])) {
				$file['has_stream'] = 0;
				if (empty($header['empty_files'][$emptyFileIndex])) {
					$file['is_dir'] = 1;
				}
				if (!empty($header['anti_files'][$emptyFileIndex])) {
					$file['is_anti'] = 1;
				}
				$emptyFileIndex++;
			}
		}

		// End Files Info
		$header['next_offset'] = $this->offset;
		return $header;
	}

	/**
	 * Reads & parses info about the given file time properties.
	 *
	 * @param   array    $header  a valid header record
	 * @param   string   $type    the file time type
	 * @return  boolean  false on error
	 */
	protected function processFileTimes(&$header, $type)
	{
		$defined = $this->readBooleans($header['num_files'], true);
		if (!$this->checkExternal()) {return false;}

		foreach ($header['files'] as $i => &$file) {
			if ($defined[$i] == 1) {
				$time = self::unpack('Vlow/Vhigh', $this->read(8));
				$file[$type] = $time;
				if ($type == 'mtime') {
					$utime = self::win2unixtime($time['low'], $time['high']);
					$file['utime'] = $utime;
				}
			} else {
				$file[$type] = null;
			}
		}
	}

	/**
	 * This doesn't seem to be implemented by any client, not even in the reference
	 * C++ code for the 7-zip client.
	 *
	 * @param   array    $header  a valid header record
	 * @return  boolean  false on error
	 */
	protected function processArchiveProperties(&$header)
	{
		$this->error = 'Archive properties not implemented, at: '.$this->offset;
		return false;
	}

	/**
	 * Determines whether the given property ID byte is an end ID, otherwise sets
	 * an error for the related offset.
	 *
	 * @param   string   $nid  the property ID
	 * @return  boolean  false on error
	 */
	protected function checkIsEnd($nid)
	{
		if ($nid != self::PROPERTY_END) {
			$this->error = "Expecting PROPERTY_END but found: {$nid} at: ".($this->offset - 1);
			return false;
		}
		return true;
	}

	/**
	 * Determines whether an external switch has been set at the current offset
	 * and sets an error if it has, since the feature isn't supported.
	 *
	 * @return  boolean  false if the external switch is set
	 */
	protected function checkExternal()
	{
		$external = ord($this->read(1));
		if ($external != 0) {
			$this->error = "External switch not implemented, at: ".($this->offset - 1);
			return false;
		}
		return true;
	}

	/**
	 * Tests whether the given header is a valid streams info header.
	 *
	 * @param   array    $header  the header to sanity check
	 * @param   integer  $limit   the minimum failure threshold
	 * @return  boolean  false if the sanity check fails
	 */
	protected function sanityCheckStreamsInfo($header, $limit=3)
	{
		$fail = (!isset($header['pack_offset']) || $header['pack_offset'] > PHP_INT_MAX)
		      + (empty($header['pack_sizes'])   || $header['pack_sizes'][0] > PHP_INT_MAX)
		      + (empty($header['num_folders'])  || $header['num_folders'] > 50)
		      + (empty($header['num_streams'])  || $header['num_streams'] > 50)
		      + (empty($header['folders'])      || empty($header['folders']['coders']));

		return $fail < $limit;
	}

	/**
	 * Reads a variable length integer value from the current offset, which may be
	 * an unsigned integer or float depending on the size and system.
	 *
	 * The first byte in the little-endian sequence contains the continuation bit
	 * flags, where 1 = read a new byte and add it to the value, and any remaining
	 * bits after 0 are the high bits of the value. The maximum value is an unsigned
	 * 64-bit integer in a 9-byte sequence.
	 *
	 * @return  integer|float  the variable length value, or zero on under/overflow
	 */
	protected function readNumber()
	{
		$first = ord($this->read(1));
		$low = $high = 0;
		$mask = 0x80;

		for ($count = 0; $count < 8; $count++) {
			if (($first & $mask) == 0) {
				$remainder = ($first & ($mask - 1));
				if ($count < 4) {
					$low  += $remainder << ($count * 8);
				} else {
					$high += $remainder << (($count - 4) * 8);
				}
				if ($low < 0) {$low += 0x100000000;}
				if ($high < 0) {$high += 0x100000000;}
				return ($high ? self::int64($low, $high) : $low);
			}
			$next = ord($this->read(1));
			if ($count < 4) {
				$low  += $next << ($count * 8);
			} else {
				$high += $next << (($count - 4) * 8);
			}
			$mask >>= 1;
		}

		return 0;
	}

	/**
	 * Reads a list of boolean bit flags from the current offset.
	 *
	 * @param   integer  $count     the number of booleans to read
	 * @param   integer  $checkAll  read an all defined flag first?
	 * @return  array    the list of boolean flags
	 */
	protected function readBooleans($count, $checkAll=false)
	{
		if ($checkAll) {
			$allDefined = ord($this->read(1));
			if ($allDefined != 0) {
				return array_fill(0, $count, 1);
			}
		}
		$result = array();
		$byte = $mask = 0;
		for ($i = 0; $i < $count; $i++) {
			if ($mask == 0) {
				$byte = ord($this->read(1));
				$mask = 0x80;
			}
			$result[$i] = (int) (($byte & $mask) != 0);
			$mask >>= 1;
		}

		return $result;
	}

	/**
	 * Reads a list of CRC digests from the current offset.
	 *
	 * @param   integer  $count  the number of digests to read
	 * @return  array    the digests info
	 */
	protected function readDigests($count)
	{
		$digests = array(
			'defined' => $this->readBooleans($count, true),
			'crcs'    => array(),
		);
		for ($i = 0; $i < $count; $i++) {
			if (!empty($digests['defined'][$i])) {
				$crc = self::unpack('V', $this->read(4));
				$digests['crcs'][$i] = $crc[1];
			} else {
				$digests['crcs'][$i] = 0;
			}
		}

		return $digests;
	}

	/**
	 * Searches for a bind pair that corresponds with the given stream index.
	 *
	 * @param   array    $pairs   the bind pair list
	 * @param   integer  $index   the stream index to search
	 * @param   string   $type    the type of bind pair ('in' or 'out')
	 * @return  integer  the bind pair index, or -1 if none found
	 */
	protected function findBindPair($pairs, $index, $type)
	{
		foreach ($pairs as $idx => $pair) {
			if ($pair[$type] == $index)
				return $idx;
		}
		return -1;
	}

	/**
	 * Calculates the final unpack size for the given packed 'folder'.
	 *
	 * @param   array    $folder   a valid folder record
	 * @return  integer  the final unpack size in bytes
	 */
	protected function getFolderUnpackSize($folder)
	{
		if (empty($folder['unpack_sizes']))
			return 0;

		$pairs = isset($folder['bind_pairs']) ?  $folder['bind_pairs'] : array();
		for ($i = count($folder['unpack_sizes']) - 1; $i >= 0; $i--) {
			if ($this->findBindPair($pairs, $i, 'out') < 0) {
				return $folder['unpack_sizes'][$i];
			}
		}
		return 0;
	}

	/**
	 * Calculates the absolute start and end positions for each of the packed
	 * blocks in the current file/data, including for sources with partial data,
	 * i.e. fragments or any split archive volumes. The range is set to null only
	 * if the packed data for the block is completely missing.
	 *
	 * @return  array  the list of absolute ranges
	 */
	protected function getPackedRanges()
	{
		if (!($mainStreams = $this->getMainStreamsInfo()))
			return false;

		$startHeader   = $this->getStartHeader();
		$mainHeader    = $this->getMainHeader();
		$encodedHeader = $this->getEncodedHeader();
		$packSizes     = $mainStreams['pack_sizes'];

		if ($startHeader) {
			$start = $this->start + $startHeader['data_offset'];
			$end   = $start + array_sum($packSizes) - 1;
		} else {
			$start = $this->start;
			if ($encodedHeader) {
				$end = $start + $encodedHeader['offset'] - $encodedHeader['pack_sizes'][0] - 1;
			} else {
				$end = $start + $mainHeader['offset'] - 1;
			}
		}

		$ranges = array();
		$blockEnd = $end;
		foreach (array_reverse($packSizes) as $size) {
			if ($blockEnd < $start) {
				$ranges[] = null;
				continue;
			}
			$blockStart = max($start, $blockEnd - $size + 1);
			$ranges[]   = "{$blockStart}-{$blockEnd}";
			$blockEnd  -= $size;
		}

		return array_reverse($ranges);
	}

	/**
	 * Retrieves the stored Main Streams Info header.
	 *
	 * @return  array|boolean  the header info, or false if none available
	 */
	protected function getMainStreamsInfo()
	{
		foreach ($this->headers as $header) {
			if ($header['type'] == self::PROPERTY_MAIN_STREAMS_INFO)
				return $header;
		}
		return false;
	}

	/**
	 * Retrieves the stored Encoded Header info.
	 *
	 * @return  array|boolean  the header info, or false if none available
	 */
	protected function getEncodedHeader()
	{
		foreach ($this->headers as $header) {
			if ($header['type'] == self::PROPERTY_ENCODED_HEADER)
				return $header;
		}
		return false;
	}

	/**
	 * Retrieves the stored main (unencoded) Header info.
	 *
	 * @return  array|boolean  the header info, or false if none available
	 */
	protected function getMainHeader()
	{
		foreach ($this->headers as $header) {
			if ($header['type'] == self::PROPERTY_HEADER)
				return $header;
		}
		return false;
	}

	/**
	 * Retrieves the stored Start header info.
	 *
	 * @return  array|boolean  the header info, or false if none available
	 */
	protected function getStartHeader()
	{
		foreach ($this->headers as $header) {
			if ($header['type'] == self::START_HEADER)
				return $header;
		}
		return false;
	}

	/**
	 * Retrieves the stored Files Info header.
	 *
	 * @return  array|boolean  the header info, or false if none available
	 */
	protected function getFilesHeaderInfo()
	{
		foreach ($this->headers as $header) {
			if ($header['type'] == self::PROPERTY_FILES_INFO)
				return $header;
		}
		return false;
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
	 * Extracts any encoded headers by creating a dummy archive with the encoded
	 * header data as the file, and extracting that file with an external client.
	 * The uncompressed/decrypted header info is then appended to the local stored
	 * headers list.
	 *
	 * @return  boolean  true if extraction was successful
	 */
	protected function extractHeaders()
	{
		if (!$this->externalClient) {
			$this->error = 'A valid external client is needed to extract headers';
			return false;
		}
		if ($this->isEncrypted && !$this->password) {
			$this->error = 'Archive headers are encrypted, password needed';
			return false;
		}
		if (($encoded = $this->getEncodedHeader()) === false)
			return false;

		try {
			// Fetch the Encoded Header packed data
			$packSize = $encoded['pack_sizes'][0];
			if ($startHeader = $this->getStartHeader()) {
				$this->seek($startHeader['data_offset'] + $encoded['pack_offset']);
			} else {
				$pos = $encoded['offset'] - $packSize;
				if ($pos < 0) {
					$this->error = 'Not enough data available to decode headers';
					return false;
				}
				$this->seek($pos);
			}
			$data = $this->read($packSize);

			// Header + Main Streams Info start
			$head = pack('C*', self::PROPERTY_HEADER, self::PROPERTY_MAIN_STREAMS_INFO);
			$this->seek($encoded['offset'] + 1);   // skip Encoded Header id
			$head .= $this->read(1);               // Pack Info id
			$this->readNumber();                   // skip pack_offset
			$head .= "\x00";                       // make pack_offset = 0

			// Remainder (skipping final null)
			$head .= $this->read($encoded['next_offset'] - $this->offset - 1);

		} catch (Exception $e) {return false;}

		// Substreams Info (skipping digests)
		$head .= pack('C*', self::PROPERTY_SUBSTREAMS_INFO, self::PROPERTY_NUM_UNPACK_STREAM, 1, 0, 0);

		// File Info (for dummy 'header.txt');
		$head .= pack('C', self::PROPERTY_FILES_INFO);
		$head .= pack('H*', '011117006800650061006400650072002e00740078007400');
		$head .= pack('H*', '0000140a010059ca701f4a7ece0115060100200000000000');

		// Start Header
		$major  = $startHeader ? $startHeader['version_major'] : 0;
		$minor  = $startHeader ? $startHeader['version_minor'] : 3;
		$start  = self::MARKER_SIGNATURE;                          // signature
		$start .= pack('C*', $major, $minor);                      // version info
		$startData  = pack('V*', $packSize, 0);                    // next_head_offset
		$startData .= pack('V*', strlen($head), 0, crc32($head));  // next_head_size, next_head_crc
		$start .= pack('V', crc32($startData));                    // start head_crc
		$start .= $startData;                                      // remainder

		// Save the dummy to a temporary file
		list($hash, $temp) = $this->getTempFileName($data);
		$this->tempFiles[$hash] = $temp;
		file_put_contents($temp, $start.$data.$head);
		unset($start, $data, $head);

		// Extract the header info with a new instance
		$szip = new self($temp, true);
		$szip->externalClient = $this->externalClient;
		if (($data = $szip->extractFile('header.txt', null, $this->password))
		  && $szip->setData($data, true)
		) {
			$this->headers = array_merge($this->headers, $szip->getHeaders());
			$this->isSolid    = $szip->isSolid;
			$this->blockCount = $szip->blockCount;
			$this->fileCount  = $szip->fileCount;
			$this->error = '';
			return true;
		}

		// Failed miserably!
		$this->error = $szip->error;
		return false;
	}

	/**
	 * Resets the instance variables before parsing new data.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		parent::reset();
		$this->headers = array();
		$this->isEncrypted = false;
		$this->hasEncodedHeader = false;
		$this->isSolid = false;
		$this->blockCount = 0;
	}

} // End SzipInfo class
