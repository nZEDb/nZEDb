<?php

class ConsoleTools
{

	function ConsoleTools(){
		$this->lastMessageLength = 0;
	}

	function overWrite($message, $reset=False)
	{
			if ($reset)
				$this->lastMessageLength = 0;
			
			echo str_repeat(chr(8), $this->lastMessageLength);

			$this->lastMessageLength = strlen($message);
			echo $message;

	}

	function percentString($cur, $total)
	{
			$percent = 100 * $cur / $total;
			$formatString = "% ".strlen($total)."d/%d (% 2d%%)";
			return sprintf($formatString, $cur, $total, $percent);
	}

	//
	// Convert seconds to minutes or hours.
	// Accepts a number of time.
	// Returns a string of time + format.
	//
	public function convertTime($seconds)
	{
	
	}
}
?>
