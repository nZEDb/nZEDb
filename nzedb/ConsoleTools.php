<?php

class ConsoleTools
{

	function __construct()
	{
		$this->lastMessageLength = 0;
		$this->c = new ColorCLI();
	}

	function overWriteHeader($message, $reset = False)
	{
		if ($reset)
			$this->lastMessageLength = 0;

		echo str_repeat(chr(8), $this->lastMessageLength);
		echo str_repeat(" ", $this->lastMessageLength);
		echo str_repeat(chr(8), $this->lastMessageLength);

		$this->lastMessageLength = strlen($message);
		echo $this->c->headerOver($message);
	}

	function overWritePrimary($message, $reset = False)
	{
		if ($reset) {
			$this->lastMessageLength = 0;
		}

		echo str_repeat(chr(8), $this->lastMessageLength);
		echo str_repeat(" ", $this->lastMessageLength);
		echo str_repeat(chr(8), $this->lastMessageLength);

		$this->lastMessageLength = strlen($message);
		echo $this->c->primaryOver($message);
	}

	function overWrite($message, $reset = False)
	{
		if ($reset)
			$this->lastMessageLength = 0;

		echo str_repeat(chr(8), $this->lastMessageLength);
		echo str_repeat(" ", $this->lastMessageLength);
		echo str_repeat(chr(8), $this->lastMessageLength);

		$this->lastMessageLength = strlen($message);
		echo $message;
	}

	function appendWrite($message)
	{
		echo $message;
		$this->lastMessageLength = $this->lastMessageLength + strlen($message);
	}

	function percentString($cur, $total)
	{
		$percent = 100 * $cur / $total;
		$formatString = "% " . strlen($total) . "d/%d (% 2d%%)";
		return sprintf($formatString, $cur, $total, $percent);
	}

	function percentString2($first, $last, $total)
	{
		$percent1 = 100 * ($first - 1) / $total;
		$percent2 = 100 * $last / $total;
		$formatString = "% " . strlen($total) . "d-% " . strlen($total) . "d/%d (% 2d%%-% 3d%%)";
		return sprintf($formatString, $first, $last, $total, $percent1, $percent2);
	}

	//
	// Convert seconds to minutes or hours.
	// Accepts a number of time.
	// Returns a string of time + format.
	//
	public function convertTime($seconds)
	{
		if ($seconds < 60)
			return $seconds . " second(s)";
		if ($seconds > 60 && $seconds < 3600)
			return round($seconds / 60) . " minute(s)";
		if ($seconds > 3600)
			return round($seconds / 3600) . " hour(s)";
	}

	//
	// Convert seconds to a timer, 00h:00m:00s
	//
	public function convertTimer($seconds)
	{
		$s = $seconds % 60;
		$m = floor(($seconds / 60) % 60);
		$h = floor($seconds / 3600);
		return " " . sprintf("%02dh:%02dm:%02ds", $h, $m, $s);
	}

	public function showSleep($seconds)
	{
		for ($i = $seconds; $i >= 0; $i--) {
			$this->overWriteHeader("Sleeping for " . $i . " seconds.");
			usleep(1000000);
		}
		echo "\n";
	}

}
