<?php
/**
 * PipeReader class.
 *
 * A very crude utility class for handling the piped output of an external command,
 * with basic methods for reading and seeking in the stream.
 *
 * Technical note: this class uses popen() instead of proc_open() because the latter
 * chokes on Windows if the pipe buffers fill due to PHP bugs #51800 & #60120 (pipes
 * can't be set to non-blocking). This means we don't have easy access to a STDERR pipe,
 * so the calling application will need to handle that itself by writing errors to a
 * file (add '2> errors.txt' to the command) or by combining with STDOUT ('2>&1').
 * Then the application must either read the file or parse the output for errors.
 *
 * @author     Hecks
 * @copyright  (c) 2010-2013 Hecks
 * @license    Modified BSD
 * @version    1.2
 */
class PipeReader
{
	/**
	 * The last error message.
	 * @var string
	 */
	public $error = '';

	/**
	 * The last process exit code.
	 * @var integer
	 */
	public $exitCode = 0;

	/**
	 * Default constructor for opening a pipe.
	 *
	 * @param   string  $command  the command to execute
	 * @return  void
	 */
	public function __construct($command=null)
	{
		if ($command) $this->open($command);
	}

	/**
	 * Opens a pipe to stream the output of a given command.
	 *
	 * Note that it's the responsibility of the calling application to sanitize
	 * the command with e.g. escapeshellcmd(), escapeshellarg(), etc.
	 *
	 * @param   string   $command  the command to execute
	 * @return  boolean  false if the pipe could not be openend
	 */
	public function open($command)
	{
		$this->reset();

		if (!($handle = popen($command, 'rb'))) {
			$this->error = "Could not execute command: ($command)";
			return false;
		}

		$this->handle  = $handle;
		$this->command = $command;

		return true;
	}

	/**
	 * Closes any open pipe handle and sets the exit code.
	 *
	 * @return  void
	 */
	public function close()
	{
		if (is_resource($this->handle)) {
			$this->exitCode = pclose($this->handle);
		}
		$this->handle = null;
	}

	/**
	 * Reads the given number of bytes from the piped command output and moves the
	 * offset pointer forward, with optional confirmation that the requested bytes
	 * are available.
	 *
	 * @param   integer   $num      number of bytes to read
	 * @param   booleann  $confirm  check available bytes?
	 * @return  string    the byte string
	 * @throws  InvalidArgumentException
	 */
	public function read($num, $confirm=true)
	{
		if ($num < 1) {
			throw new InvalidArgumentException("Could not read {$num} bytes from offset {$this->offset}");
		} elseif ($num == 0) {
			return '';
		}

		// Read the requested bytes
		if ($this->command && is_resource($this->handle)) {
			$read = ''; $rlen = $num;
			while ($rlen > 0 && !feof($this->handle)) {
				$data  = fread($this->handle, min($this->maxReadBytes, $rlen));
				$rlen -= strlen($data);
				$read .= $data;
			}
		}

		// Confirm the read length?
		if ($confirm && (!isset($read) || strlen($read) < $num)) {
			$this->offset = $this->tell();
			throw new InvalidArgumentException("Could not read {$num} bytes from offset {$this->offset}");
		}

		// Move the data pointer
		$this->offset = $this->tell();

		return isset($read) ? $read : '';
	}

	/**
	 * Convenience method for reading the remaining bytes from the piped output.
	 *
	 * @return  string  the remaining output data
	 */
	public function readAll()
	{
		$data = '';
		while ($read = $this->read($this->maxReadBytes, false)) {
			$data .= $read;
		}

		return $data;
	}

	/**
	 * Convenience method for reading a single line, with the line ending included
	 * in the output.
	 *
	 * @return  string|boolean  the next output line, or false if none available
	 */
	public function readLine()
	{
		if (!$this->command || !is_resource($this->handle) || feof($this->handle))
			return false;

		$line = fgets($this->handle, $this->maxReadBytes);
		$this->offset = $this->tell();

		return $line;
	}

	/**
	 * Moves the current offset pointer to a position in the piped command output.
	 *
	 * Since only seeking ahead in the pipe is possible - and only by reading the
	 * output stream - seeking to an earlier offset necessarily means invoking the
	 * command again, so care must be taken that any commands are idempotent.
	 *
	 * @param   integer  $pos  new pointer position
	 * @return  void
	 * @throws  InvalidArgumentException
	 */
	public function seek($pos)
	{
		if ($pos < 0)
			throw new InvalidArgumentException("Could not seek to offset: {$pos}");

		if ($this->command) {
			if ($pos < $this->offset) {
				$this->open($this->command);
			}
			if ($pos > $this->offset && $pos > 0) {
				$this->read($pos - $this->offset);
			}
		}

		$this->offset = $this->tell();
	}

	/**
	 * Provides the absolute position within the current piped output.
	 *
	 * @return  integer  the absolute position
	 */
	public function tell()
	{
		if ($this->command && is_resource($this->handle))
			return ftell($this->handle);

		return $this->offset;
	}

	/**
	 * Sets the maximum number of bytes to read in one operation.
	 *
	 * @param   integer  $bytes  the max bytes to read
	 * @return  void
	 */
	public function setMaxReadBytes($bytes)
	{
		if (is_int($bytes) && $bytes > 0) {
			$this->maxReadBytes = $bytes;
		}
	}

	/**
	 * The command string to be executed for piped output.
	 * @var string
	 */
	protected $command = '';

	/**
	 * Stream handle for the current pipe.
	 * @var resource
	 */
	protected $handle;

	/**
	 * The maximum number of bytes to read in one operation.
	 * @var integer
	 */
	protected $maxReadBytes = 1048576;

	/**
	 * The current position in the piped output.
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * Resets the instance variables.
	 *
	 * @return  void
	 */
	protected function reset()
	{
		$this->close();
		$this->error = '';
		$this->exitCode = 0;
		$this->command = '';
		$this->offset = 0;
	}

} // End PipeReader class
