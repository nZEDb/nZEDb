<?php
require_once(WWW_DIR."/lib/binaries.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/Net_NNTP/NNTP/Client.php");

class Nntp extends Net_NNTP_Client
{
	public $Compression = false;

	function Nntp()
	{
		$this->timeout = 15;
	}

	// Make a NNTP connection.
	function doConnect()
	{
		if ($this->_isConnected())
			return true;
		$enc = false;

		$s = new Sites();
		$site = $s->get();
		$compressionstatus = $site->compressedheaders;
		unset($s);
		unset($site);

		$retries = 10;
		while($retries >= 1)
		{
			usleep(10000);
			$retries--;
			if (defined("NNTP_SSLENABLED") && NNTP_SSLENABLED == true)
				$enc = 'ssl';

			$ret = $this->connect(NNTP_SERVER, $enc, NNTP_PORT, $this->timeout);
			if(PEAR::isError($ret))
			{
				if ($retries < 1)
					echo "Cannot connect to server ".NNTP_SERVER.(!$enc?" (nonssl) ":"(ssl) ").": ".$ret->getMessage();
			}
			if(!defined(NNTP_USERNAME) && NNTP_USERNAME!="" )
			{
				$ret2 = $this->authenticate(NNTP_USERNAME, NNTP_PASSWORD);
				if(PEAR::isError($ret2))
				{
					if ($retries < 1)
						echo "Cannot authenticate to server ".NNTP_SERVER.(!$enc?" (nonssl) ":" (ssl) ")." - ".NNTP_USERNAME." (".$ret2->getMessage().")";
				}
			}
			elseif(NNTP_USERNAME=="")
				$ret2 = 0;
			if($compressionstatus == "1")
			{
				$this->enableCompression();
			}
			return $ret && $ret2;
		}
	}

	// Make a nntp connection (alternate server).
	function doConnect_A()
	{
		if ($this->_isConnected())
			return true;
		$enc = false;

		$s = new Sites();
		$site = $s->get();
		$compressionstatus = $site->compressedheaders;
		unset($s);
		unset($site);

		$retries = 10;
		while($retries >= 1)
		{
			usleep(10000);
			$retries--;
			if (defined("NNTP_SSLENABLED_A") && NNTP_SSLENABLED_A == true)
				$enc = 'ssl';

			$ret = $this->connect(NNTP_SERVER_A, $enc, NNTP_PORT_A, $this->timeout);
			if(PEAR::isError($ret))
			{
				if ($retries < 1)
					echo "Cannot connect to server ".NNTP_SERVER_A.(!$enc?" (nonssl) ":"(ssl) ").": ".$ret->getMessage();
			}
			if(!defined(NNTP_USERNAME_A) && NNTP_USERNAME_A !="" )
			{
				$ret2 = $this->authenticate(NNTP_USERNAME_A, NNTP_PASSWORD_A);
				if(PEAR::isError($ret2))
				{
					if ($retries < 1)
						echo "Cannot authenticate to server ".NNTP_SERVER_A.(!$enc?" (nonssl) ":" (ssl) ")." - ".NNTP_USERNAME_A." (".$ret2->getMessage().")";
				}
			}
			if($compressionstatus == "1")
			{
				$this->enableCompression();
			}
			return $ret && $ret2;
		}
	}

	// Make a nntp connection (no XFeature GZip compression).
	function doConnectNC()
	{
		if ($this->_isConnected())
			return;
		$enc = false;
		if (defined("NNTP_SSLENABLED") && NNTP_SSLENABLED == true)
			$enc = 'ssl';

		$ret = $this->connect(NNTP_SERVER, $enc, NNTP_PORT, $this->timeout);
		if(PEAR::isError($ret))
		{
			echo "Cannot connect to server ".NNTP_SERVER.(!$enc?" (nonssl) ":"(ssl) ").": ".$ret->getMessage();
			die();
		}
		if(!defined(NNTP_USERNAME) && NNTP_USERNAME!="" )
		{
			$ret2 = $this->authenticate(NNTP_USERNAME, NNTP_PASSWORD);
			if(PEAR::isError($ret2))
			{
				echo "Cannot authenticate to server ".NNTP_SERVER.(!$enc?" (nonssl) ":" (ssl) ")." - ".NNTP_USERNAME." (".$ret2->getMessage().")";
				die();
			}
		}
	}

	// Quit the nntp connection.
	function doQuit()
	{
		$this->quit();
	}

	// Get only the body of an article (no header).
	function getMessage($groupname, $partMsgId)
	{
		$summary = $this->selectGroup($groupname);
		$message = $dec = '';

		if (PEAR::isError($summary))
		{
			echo $summary->getMessage();
			return false;
		}

		$body = $this->getBody('<'.$partMsgId.'>', true);
		if (PEAR::isError($body))
		{
		   //echo 'Error fetching part number '.$partMsgId.' in '.$groupname.' (Server response: '. $body->getMessage().')'."\n";
		   return false;
		}

		$message = $this->decodeYenc($body);
		if (!$message)
		{
			//echo "Yenc decode failure";
			return false;
		}

		return $message;
	}

	// Get multiple article bodies (string them together).
	function getMessages($groupname, $msgIds)
	{
		$body = '';

		foreach ($msgIds as $m)
		{
			$message = $this->getMessage($groupname, $m);
			if ($message !== false)
				$body = $body . $message;
			else
				return false;
		}
		return $body;
	}

	// Get a full article (body + header).
	function get_Article($groupname, $partMsgId)
	{
		$summary = $this->selectGroup($groupname);
		$message = $dec = '';

		if (PEAR::isError($summary))
		{
			echo $summary->getMessage();
			return false;
		}

		$body = $this->getArticle('<'.$partMsgId.'>', true);
		if (PEAR::isError($body))
		{
			//echo 'Error fetching part number '.$partMsgId.' in '.$groupname.' (Server response: '. $body->getMessage().')'."\n";
			return false;
		}

		$message = $this->decodeYenc($body);
		if (!$message)
		{
			//echo "Yenc decode failure";
			return false;
		}

		return $message;
	}

	// Get multiple articles (string them together).
	function getArticles($groupname, $msgIds)
	{
		$body = '';

		foreach ($msgIds as $m)
		{
			$message = $this->get_Article($groupname, $m);
			if ($message !== false)
				$body = $body . $message;
			else
				return false;
		}
		return $body;
	}

	function getBinary($binaryId, $isNfo=false)
	{
		$db = new DB();
		$bin = new Binaries();

		$binary = $bin->getById($binaryId);
		if (!$binary)
			return false;

		$summary = $this->selectGroup($binary['groupname']);
		$message = $dec = '';

		if (PEAR::isError($summary))
		{
			echo $summary->getMessage();
			return false;
		}

		$resparts = $db->query(sprintf("SELECT size, partnumber, messageID FROM parts WHERE binaryID = %d ORDER BY partnumber", $binaryId));

		if (sizeof($resparts) > 1 && $isNfo === true)
		{
			echo 'NFO is more than 1 part, skipping. ';
			return false;
		}

		foreach($resparts as $part)
		{
			$messageID = '<'.$part['messageID'].'>';
			$body = $this->getBody($messageID, true);
			if (PEAR::isError($body))
			{
			   return false;
			}

			$dec = $this->decodeYenc($body);
			if (!$dec)
			{
				echo "Yenc decode failure";
				return false;
			}

			$message .= $dec;
		}
		return $message;
	}

	// Decode a Yenc encoded article body.
	function decodeYenc($yencodedvar)
	{
		$input = array();
		preg_match("/^(=ybegin.*=yend[^$]*)$/ims", $yencodedvar, $input);
		if (isset($input[1]))
		{
			$ret = "";
			$input = trim(preg_replace("/\r\n/im", "",  preg_replace("/(^=yend.*)/im", "", preg_replace("/(^=ypart.*\\r\\n)/im", "", preg_replace("/(^=ybegin.*\\r\\n)/im", "", $input[1], 1), 1), 1)));

			for( $chr = 0; $chr < strlen($input) ; $chr++)
				$ret .= ($input[$chr] != "=" ? chr(ord($input[$chr]) - 42) : chr((ord($input[++$chr]) - 64) - 42));

			return $ret;
		}
		return false;
	}

	/*
	* Code by Wafflehouse : http://pastebin.com/A3YypDAJ
	* Enable XFeature compression support for the current connection.
	*/
	function enableCompression()
	{
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');

		if (PEAR::isError($response) || $response != 290)
		{
			return false;
		}

		$this->Compression = true;
		return true;
	}

	/**
	 * Override to intercept any Xfeature compressed responses.
	 */
	function _getTextResponse()
	{
		if ($this->Compression && isset($this->_currentStatusResponse[1]) && stripos($this->_currentStatusResponse[1], 'COMPRESS=GZIP') !== false)
		{
			return $this->_getXfeatureTextResponse();
		}

		return parent::_getTextResponse();
	}

	function _getXfeatureTextResponse()
	{
		$tries              = 0;
		$bytesreceived      = 0;
		$totalbytesreceived = 0;
		$completed          = false;
		$data               = null;
		// Build binary array that represents zero results basically a compressed empty string terminated with .(period) char(13) char(10)
		$emptyreturnend     = chr(0x03).chr(0x00).chr(0x00).chr(0x00).chr(0x00).chr(0x01).chr(0x2e).chr(0x0d).chr(0x0a);
		$emptyreturn        = chr(0x78).chr(0x9C).$emptyreturnend;
		$emptyreturn2       = chr(0x78).chr(0x01).$emptyreturnend;
		$emptyreturn3       = chr(0x78).chr(0x5e).$emptyreturnend;
		$emptyreturn4       = chr(0x78).chr(0xda).$emptyreturnend;

		while (!feof($this->_socket))
		{
			$completed = false;
			// Get data from the stream.
			$buffer = fgets($this->_socket);
			// Get byte count and update total bytes.
			$bytesreceived = strlen($buffer);
			// If we got no bytes at all try one more time to pull data.
			if ($bytesreceived == 0)
			{
				$buffer = fgets($this->_socket);
			}
			// Get any socket error codes.
			 $errorcode = socket_last_error();

			// If the buffer is zero it's zero...
			if ($bytesreceived === 0)
				return $this->throwError('No data returned.', 1000);
			// Did we have any socket errors?
			if ($errorcode === 0)
			{
				// Append buffer to final data object.
				 $data .= $buffer;
				 $totalbytesreceived = $totalbytesreceived+$bytesreceived;

				 // Output byte count in real time once we have 10KB of data.
				if ($totalbytesreceived > 10240)
				if ($totalbytesreceived%128 == 0)
				{
					$kb = 1024;
					echo "Receiving ".round($totalbytesreceived/$kb)."KB\r";
				}

				// Check to see if we have the magic terminator on the byte stream.
				$b1 = null;
				if ($bytesreceived > 2)
				if (ord($buffer[$bytesreceived-3]) == 0x2e && ord($buffer[$bytesreceived-2]) == 0x0d && ord($buffer[$bytesreceived-1]) == 0x0a)
				{
					// Check if the returned binary string is 11 bytes long generally and indicator of an compressed empty string.
					if ($totalbytesreceived==11)
					{
						// Compare the data to the empty string if the data is a compressed empty string. Throw an error, else return the data.
						if (($data === $emptyreturn)||($data === $emptyreturn2)||($data === $emptyreturn3)||($data === $emptyreturn4))
						{
							return $this->throwError('No data returned. This is normal. Do not cry.', 1000);
						}
					}
					else
					{
						if ($totalbytesreceived > 10240)
							echo "\n";
						$completed = true;
					}
				}
			 }
			 else
			 {
				 echo "Failed to read line from socket.\n";
				 return $this->throwError('Failed to read line from socket.', 1000);
			 }

			if ($completed)
			{
				// Check if the header is valid for a gzip stream.
				if(ord($data[0]) == 0x78 && in_array(ord($data[1]),array(0x01,0x5e,0x9c,0xda)))
				{
					$decomp = @gzuncompress(mb_substr ( $data , 0 ,-3, '8bit' ));
				}
				else
				{
					echo "Invalid header on the gzip stream.\n";
					return $this->throwError('Invalid gzip stream.', 1000);
				}

				if ($decomp != false)
				{
					$decomp = explode("\r\n", trim($decomp));
					return $decomp;
				}
				else
				{
					$tries++;
					echo "Decompression failed. Retry number: $tries\n";
					if ($tries > 10)
					{
						echo "10 tries and it still failed, so skipping";
						return $this->throwError('Decompression Failed, connection closed.', 1000);
					}
				}
			}
		}
		// Throw an error if we get out of the loop.
		if (!feof($this->_socket))
		{
			return "\nError: unexpected fgets() fail.\n";
		}
		return $this->throwError('Decompression Failed, connection closed.', 1000);
	}

	// If there is an error with selectGroup(), try to restart the connection, else show the error.
	// Send a 3rd argument, false, for a connection with no compression.
	public function dataError($nntp, $group, $comp=true)
	{
		$nntp->doQuit();
		if ($comp === false)
			$nntp->doConnectNC();
		else
			$nntp->doConnect();
		$data = $nntp->selectGroup($group);
		if (PEAR::isError($data))
		{
			echo "Error {$data->code}: {$data->message}\nSkipping group: {$group}\n";
			$nntp->doQuit();
			return false;
		}
		else
			return $data;
	}
}
