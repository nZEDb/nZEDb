<?php

require_once dirname(__FILE__) . '/archivereader.php';

/**
 * Par2Info class.
 *
 * A simple class for inspecting PAR2 file data and listing information about the
 * recovery set in pure PHP. Data can be streamed from a file or loaded directly
 * from memory. The redundancy of the format means that details of the recovery
 * set are repeated across multiple files, so inspecting any of them will normally
 * produce the same results (apart from the recovery blocks themselves).
 *
 * Example usage:
 *
 * <code>
 *
 *   // Load the PAR2 file or data
 *   $par2 = new Par2Info;
 *   $par2->open('./foo.par2'); // or $par2->setData($data);
 *   if ($par2->error) {
 *     echo "Error: {$par2->error}\n";
 *     exit;
 *   }
 *
 *   // Process the recovery set file list & hashes
 *   $files = $par2->getFileList();
 *   foreach ($files as $fileID => $file) {
 *     echo "Input file: {$file['name']} ({$file['size']}):\n";
 *     echo "-- MD5 hash: {$file['hash']}:\n";
 *     echo "-- MD5 hash (16KB): {$file['hash_16K']}:\n";
 *    }
 *   }
 *
 * </code>
 *
 * @link http://parchive.sourceforge.net/docs/specifications/parity-volume-spec/article-spec.html
 *
 * @author     Hecks
 * @copyright  (c) 2010-2013 Hecks
 * @license    Modified BSD
 * @version    1.7
 */
class Par2Info extends ArchiveReader
{
	// ------ Class constants -----------------------------------------------------

	/**#@+
	 * PAR2 file format values
	 */

	// Packet Marker
	const PACKET_MARKER            = "PAR2\x00PKT";

	// Core packet types
	const PACKET_MAIN              = "PAR 2.0\x00Main\x00\x00\x00\x00";
	const PACKET_FILEDESC          = "PAR 2.0\x00FileDesc";
	const PACKET_FILEVER           = "PAR 2.0\x00IFSC\x00\x00\x00\x00";
	const PACKET_RECOVERY          = "PAR 2.0\x00RecvSlic";
	const PACKET_CREATOR           = "PAR 2.0\x00Creator\x00";

	// Optional packet types
	const PACKET_FILENAME_UC       = "PAR 2.0\x00UniFileN";
	const PACKET_COMMENT_ASCII     = "PAR 2.0\x00CommASCI";
	const PACKET_COMMENT_UC        = "PAR 2.0\x00CommUni\x00";
	const PACKET_INPUT_BLOCK       = "PAR 2.0\x00FileSlic";
	const PACKET_RECOVERY_VER      = "PAR 2.0\x00RFSC\x00\x00\x00\x00";
	const PACKET_PACKED_MAIN       = "PAR 2.0\x00PkdMain\x00";
	const PACKET_PACKED_RECOVERY   = "PAR 2.0\x00PkdRecvS";

	/**#@-*/

	/**
	 * Format for unpacking each PAR2 packet header, in standard and Perl-compatible
	 * (PHP >= 5.5.0) versions.
	 */
	const FORMAT_PACKET_HEADER     = 'A8head_marker/Vhead_length/Vhead_length_high/H32head_hash/H32head_set_id/A16head_type';
	const PL_FORMAT_PACKET_HEADER  = 'a8head_marker/Vhead_length/Vhead_length_high/H32head_hash/H32head_set_id/a16head_type';

	/**
	 * Format for unpacking the body of a Main packet.
	 */
	const FORMAT_PACKET_MAIN = 'Vblock_size/Vblock_size_high/Vrec_file_count';

	/**
	 * Format for unpacking the body of a File Description packet.
	 */
	const FORMAT_PACKET_FILEDESC = 'H32file_id/H32file_hash/H32file_hash_16K/Vfile_length/Vfile_length_high';


	// ------ Instance variables and methods ---------------------------------------

	/**
	 * List of packet names corresponding to packet types.
	 * @var array
	 */
	protected $packetNames = array(
		self::PACKET_MAIN             => 'Main',
		self::PACKET_FILEDESC         => 'File Description',
		self::PACKET_FILEVER          => 'File Block Verification',
		self::PACKET_RECOVERY         => 'Recovery Block',
		self::PACKET_CREATOR          => 'Creator',
		// Optional
		self::PACKET_FILENAME_UC      => 'Unicode Filename',
		self::PACKET_COMMENT_ASCII    => 'Comment ASCII',
		self::PACKET_COMMENT_UC       => 'Comment Unicode',
		self::PACKET_INPUT_BLOCK      => 'Input File Block',
		self::PACKET_RECOVERY_VER     => 'Recovery File Block Verification',
		self::PACKET_PACKED_MAIN      => 'Packed Main',
		self::PACKET_PACKED_RECOVERY  => 'Packed Recovery',
	);

	/**
	 * Number of valid recovery blocks in the file/data.
	 * @var integer
	 */
	public $blockCount = 0;

	/**
	 * Size in bytes of the recovery blocks.
	 * @var integer
	 */
	public $blockSize = 0;

	/**
	 * Details of the client that created the PAR2 file/data.
	 * @var string
	 */
	public $client = '';

	/**
	 * Convenience method that outputs a summary list of the file/data information,
	 * useful for pretty-printing.
	 *
	 * @param   boolean   $full  add file list to output?
	 * @return  array     file/data summary
	 */
	public function getSummary($full=false)
	{
		$summary = array(
			'file_name'   => $this->file,
			'file_size'   => $this->fileSize,
			'data_size'   => $this->dataSize,
			'client'      => $this->client,
			'block_count' => $this->blockCount,
			'block_size'  => $this->blockSize,
			'file_count'  => $this->fileCount,
		);
		if ($full) {
			$summary['file_list'] = $this->getFileList();
		}
		if ($this->error) {
			$summary['error'] = $this->error;
		}

		return $summary;
	}

	/**
	 * Returns a list of the PAR2 packets found in the file/data in human-readable
	 * format (for debugging purposes only).
	 *
	 * @param   boolean  $full  include all packet details in output?
	 * @return  array|boolean   list of packets, or false if none available
	 */
	public function getPackets($full=false)
	{
		// Check that packets are stored
		if (empty($this->packets)) {return false;}

		// Build the packet list
		$ret = array();

		foreach ($this->packets AS $packet) {

			// File Block Verification packets are very verbose
			if (!$full && $packet['head_type'] == self::PACKET_FILEVER) {continue;}

			$p = array();
			$p['type_name'] = isset($this->packetNames[$packet['head_type']]) ? $this->packetNames[$packet['head_type']] : 'Unknown';
			$p += $packet;

			// Sanity check filename length
			if (isset($p['file_name'])) {$p['file_name'] = substr($p['file_name'], 0, $this->maxFilenameLength);}
			$ret[] = $p;
		}

		return $ret;
	}

	/**
	 * Parses the stored packets and returns a list of records for each of the
	 * files in the recovery set.
	 *
	 * @return  array  list of file records, empty if none are available
	 */
	public function getFileList()
	{
		$ret = array();
		foreach ($this->packets as $packet) {
			if ($packet['head_type'] == self::PACKET_FILEDESC && !isset($ret[$packet['file_id']])) {
				$ret[$packet['file_id']] = $this->getFilePacketSummary($packet);
			}
			if ($packet['head_type'] == self::PACKET_FILEVER && empty($ret[$packet['file_id']]['blocks'])) {
				$ret[$packet['file_id']]['blocks'] = count($packet['block_checksums']);
			}
		}

		return $ret;
	}

	/**
	 * List of File IDs found in the file/data.
	 * @var array
	 */
	protected $fileIDs = array();

	/**
	 * List of packets found in the file/data.
	 * @var array
	 */
	protected $packets = array();

	/**
	 * Returns a processed summary of a PAR2 File Description packet.
	 *
	 * @param   array  $packet  a valid File Description packet
	 * @return  array  summary information
	 */
	protected function getFilePacketSummary($packet)
	{
		return array(
			'name' => !empty($packet['file_name']) ? substr($packet['file_name'], 0, $this->maxFilenameLength) : 'Unknown',
			'size' => isset($packet['file_length']) ? $packet['file_length'] : 0,
			'hash' => $packet['file_hash'],
			'hash_16K' => $packet['file_hash_16K'],
			'blocks' => 0,
			'next_offset' => $packet['next_offset'],
		);
	}

	/**
	 * Returns the position of the first PAR2 Packet Marker string in the file/data.
	 *
	 * @return  mixed  Marker position, or false if none found
	 */
	public function findMarker()
	{
		if ($this->markerPosition !== null)
			return $this->markerPosition;

		try {
			$buff = $this->read(min($this->length, $this->maxReadBytes));
			$this->rewind();
			return $this->markerPosition = strpos($buff, self::PACKET_MARKER);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Parses the PAR2 data and stores a list of valid packets locally.
	 *
	 * @return  boolean  false if parsing fails
	 */
	protected function analyze()
	{
		// Find the first Packet Marker, if there is one
		if (($startPos = $this->findMarker()) === false) {
			$this->error = 'Could not find a Packet Marker, not a valid PAR2 file';
			return false;
		}
		$this->seek($startPos);

		// Analyze all packets
		while ($this->offset < $this->length) try {

			// Get the next packet header
			$packet = $this->getNextPacket();

			// Verify the packet
			if ($this->verifyPacket($packet) === false) {
				$this->error = "Packet failed checksum (offset: {$this->offset})";
				throw new Exception('Packet checksum failed');
			}

			// Process the current packet by type
			$this->processPacket($packet);

			// Add the current packet to the list
			$this->packets[] = $packet;

			// Skip to the next packet, if any
			if ($this->offset != $packet['next_offset']) {
				$this->seek($packet['next_offset']);
			}

			// Sanity check
			if ($packet['offset'] == $this->offset) {
				$this->error = 'Parsing seems to be stuck';
				$this->close();
				return false;
			}

		// No more readable data, or read error
		} catch (Exception $e) {
			if ($this->error) {$this->close(); return false;}
			break;
		}

		// Check for valid packets
		if (empty($this->packets)) {
			$this->error = 'No valid PAR2 packets were found';
			$this->close();
			return false;
		}

		// Analysis was successful
		$this->close();
		return true;
	}

	/**
	 * Reads the start of the next packet header and returns the common packet
	 * info before further processing by packet type.
	 *
	 * @return  array  the next packet header info
	 */
	protected function getNextPacket()
	{
		// Start the packet info
		$packet = array('offset' => $this->offset);

		// Unpack the packet header
		$format = (version_compare(PHP_VERSION, '5.5.0') >= 0)
			? self::PL_FORMAT_PACKET_HEADER
			: self::FORMAT_PACKET_HEADER;
		$packet += self::unpack($format, $this->read(64));

		// Convert packet size (64-bit integer)
		$packet['head_length'] = self::int64($packet['head_length'], $packet['head_length_high']);

		// Add offset info for next packet (if any)
		$packet['next_offset'] = $packet['offset'] + $packet['head_length'];

		// Return the packet info
		return $packet;
	}

	/**
	 * Verifies that the given packet is valid and parsable.
	 *
	 * @param   array    $packet  the packet to verify
	 * @return  boolean  false on failure
	 */
	protected function verifyPacket($packet)
	{
		$offset = $this->offset;

		// Check the MD5 hash of the data from head_set_id to packet end
		$this->seek($packet['offset'] + 32);
		$data = $this->read($packet['head_length'] - 32);
		$this->seek($offset);

		return (md5($data) === $packet['head_hash']);
	}

	/**
	 * Processes a packet passed by reference and unpacks its body.
	 *
	 * @param   array  $packet  the packet to process
	 * @return  void
	 */
	protected function processPacket(&$packet)
	{
		// Packet type: MAIN
		if ($packet['head_type'] == self::PACKET_MAIN) {
			$packet += self::unpack(self::FORMAT_PACKET_MAIN, $this->read(12));
			$packet['block_size'] = self::int64($packet['block_size'], $packet['block_size_high']);
			$this->blockSize = $packet['block_size'];

			// Unpack the File IDs of all files in the recovery set
			$recoverable = array();
			for ($i = 0; $i < $packet['rec_file_count']; $i++) {
				$recoverable = array_merge($recoverable, self::unpack('H32', $this->read(16)));
			}
			$packet['rec_file_ids'] = $recoverable;

			// Unpack any File IDs of files not in the recovery set
			$unrecoverable = array();
			while ($this->offset < $packet['next_offset']) {
				$unrecoverable = array_merge($unrecoverable, self::unpack('H32', $this->read(16)));
			}
			if (!empty($unrecoverable)) {
				$packet['other_file_ids'] = $unrecoverable;
			}
		}

		// Packet type: FILE DESCRIPTION
		elseif ($packet['head_type'] == self::PACKET_FILEDESC) {
			$packet += self::unpack(self::FORMAT_PACKET_FILEDESC, $this->read(56));
			$packet['file_length'] = self::int64($packet['file_length'], $packet['file_length_high']);
			$len = ($packet['offset'] + $packet['head_length']) - $this->offset;
			$packet['file_name'] = rtrim($this->read($len));

			// Add ID to the stored file list so we don't double-count
			if (!isset($this->fileIDs[$packet['file_id']])) {
				$this->fileIDs[$packet['file_id']] = true;
				$this->fileCount++;
			}
		}

		// Packet type: FILE BLOCK VERIFICATION
		elseif ($packet['head_type'] == self::PACKET_FILEVER) {
			$packet += self::unpack('H32file_id', $this->read(16));

			// Unpack the MD5/CRC32 checksum pairs
			$packet['block_checksums'] = array();
			while ($this->offset < $packet['next_offset']) {
				$packet['block_checksums'][] = self::unpack('H32md5/Vcrc32', $this->read(20));
			}
		}

		// Packet type: RECOVERY BLOCK
		elseif ($packet['head_type'] == self::PACKET_RECOVERY) {
			$packet += self::unpack('Vexponent', $this->read(4));
			$this->blockCount++;
		}

		// Packet type: CREATOR
		elseif ($packet['head_type'] == self::PACKET_CREATOR) {
			$len = ($packet['offset'] + $packet['head_length']) - $this->offset;
			$packet['client'] = $this->read($len);
			$this->client = rtrim($packet['client']);
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

		$this->client = '';
		$this->blockCount = 0;
		$this->blockSize = 0;
		$this->fileIDs = array();
		$this->packets = array();
	}

} // End Par2Info class
