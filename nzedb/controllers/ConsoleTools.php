<?php

/**
 * Class ConsoleTools
 */
class ConsoleTools
{

	/**
	 * @var ColorCLI
	 */
	public $cli;

	/**
	 * @var int
	 */
	public $lastMessageLength;

	/**
	 * Construct.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'ColorCLI' => null
		];
		$options += $defaults;

		$this->cli = ($options['ColorCLI'] instanceof \ColorCLI ? $options['ColorCLI'] : new \ColorCLI());

		$this->lastMessageLength = 0;
	}

	/**
	 * @param string $message
	 * @param bool   $reset
	 */
	public function overWriteHeader($message, $reset = False)
	{
		if ($reset)
			$this->lastMessageLength = 0;

		echo str_repeat(chr(8), $this->lastMessageLength);
		echo str_repeat(" ", $this->lastMessageLength);
		echo str_repeat(chr(8), $this->lastMessageLength);

		$this->lastMessageLength = strlen($message);
		echo $this->cli->headerOver($message);
	}

	/**
	 * @param string $message
	 * @param bool   $reset
	 */
	public function overWritePrimary($message, $reset = False)
	{
		if ($reset) {
			$this->lastMessageLength = 0;
		}

		echo str_repeat(chr(8), $this->lastMessageLength);
		echo str_repeat(" ", $this->lastMessageLength);
		echo str_repeat(chr(8), $this->lastMessageLength);

		$this->lastMessageLength = strlen($message);
		echo $this->cli->primaryOver($message);
	}

	/**
	 * @param string $message
	 * @param bool   $reset
	 */
	public function overWrite($message, $reset = False)
	{
		if ($reset) {
			$this->lastMessageLength = 0;
		}

		echo str_repeat(chr(8), $this->lastMessageLength);
		echo str_repeat(" ", $this->lastMessageLength);
		echo str_repeat(chr(8), $this->lastMessageLength);

		$this->lastMessageLength = strlen($message);
		echo $message;
	}

	/**
	 * @param string $message
	 */
	public function appendWrite($message)
	{
		echo $message;
		$this->lastMessageLength = $this->lastMessageLength + strlen($message);
	}

	/**
	 * @param int $cur
	 * @param int $total
	 *
	 * @return string
	 */
	public function percentString($cur, $total)
	{
		$percent = 100 * $cur / $total;
		$formatString = "% " . strlen($total) . "d/%d (% 2d%%)";
		return sprintf($formatString, $cur, $total, $percent);
	}

	/**
	 * @param int $first
	 * @param int $last
	 * @param int $total
	 *
	 * @return string
	 */
	public function percentString2($first, $last, $total)
	{
		$percent1 = 100 * ($first - 1) / $total;
		$percent2 = 100 * $last / $total;
		$formatString = "% " . strlen($total) . "d-% " . strlen($total) . "d/%d (% 2d%%-% 3d%%)";
		return sprintf($formatString, $first, $last, $total, $percent1, $percent2);
	}

	/**
	 * Convert seconds to minutes or hours, appending type at the end.
	 *
	 * @param int $seconds
	 *
	 * @return string
	 */
	public function convertTime($seconds)
	{

		if ($seconds > 3600) {
			return round($seconds / 3600) . " hour(s)";
		} else if ($seconds > 60) {
			return round($seconds / 60) . " minute(s)";
		} else {
			return $seconds . " second(s)";
		}
	}

	/**
	 * Convert seconds to a timer, 00h:00m:00s
	 *
	 * @param int $seconds
	 *
	 * @return string
	 */
	public function convertTimer($seconds)
	{
		return " " . sprintf("%02dh:%02dm:%02ds", floor($seconds / 3600), floor(($seconds / 60) % 60), $seconds % 60);
	}

	/**
	 * Sleep for x seconds, printing timer on screen.
	 *
	 * @param int $seconds
	 */
	public function showSleep($seconds)
	{
		for ($i = $seconds; $i >= 0; $i--) {
			$this->overWriteHeader("Sleeping for " . $i . " seconds.");
			sleep(1);
		}
		echo PHP_EOL;
	}
}
