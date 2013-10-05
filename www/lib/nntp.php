<?php
require_once(WWW_DIR.'lib/binaries.php');
require_once(WWW_DIR.'lib/framework/db.php');
require_once(WWW_DIR.'lib/site.php');
require_once(WWW_DIR.'lib/Net_NNTP/NNTP/Client.php');
require_once(WWW_DIR.'lib/ColorCLI.php');

/*
* Class for connecting to the usenet, retrieving articles and article headers, decoding yEnc articles, decompressing article headers.
*/

class Nntp extends Net_NNTP_Client
{
	public $Compression = false;

	public function Nntp()
	{
		$this->c = new ColorCLI;
		$this->primary = 'green';
		$this->warning = 'red';
		$this->header = 'yellow';
	}


	// Make an NNTP connection.
	public function doConnect($compression=true)
	{
		if ($compression === true && $this->_isConnected())
			return true;
		else
			$this->doQuit();

		$s = new Sites();
		$site = $s->get();
		$compressionstatus = $site->compressedheaders;
		unset($s, $site);
		$enc = $ret = $ret2 = $connected = false;

		if (defined('NNTP_SSLENABLED') && NNTP_SSLENABLED == true)
			$enc = 'ssl';

		$retries = 10;
		while($retries >= 1)
		{
			usleep(10000);
			$authent = false;
			$retries--;
			if ($connected === false)
				$ret = $this->connect(NNTP_SERVER, $enc, NNTP_PORT, 15);

			if(PEAR::isError($ret))
			{
				if ($retries < 1)
					echo $this->c->setcolor('bold', $this->warning).'Cannot connect to server '.NNTP_SERVER.(!$enc?' (nonssl) ':'(ssl) ').': '.$ret->getMessage().$this->c->rsetcolor();
			}
			else
				$connected = true;

			if ($connected === true && $authent === false && defined('NNTP_USERNAME'))
			{
				if (NNTP_USERNAME == '')
					$authent = true;
				else
				{
					$ret2 = $this->authenticate(NNTP_USERNAME, NNTP_PASSWORD);
					if(PEAR::isError($ret2))
					{
						if ($retries < 1)
							echo $this->c->setcolor('bold', $this->warning).'Cannot authenticate to server '.NNTP_SERVER.(!$enc?' (nonssl) ':' (ssl) ').' - '.NNTP_USERNAME.' ('.$ret2->getMessage().')'.$this->c->rsetcolor();
					}
					else
						$authent = true;
				}
			}

			if ($connected && $authent === true)
			{
				if($compression === true && $compressionstatus == '1')
					$this->enableCompression();
				return true;
			}
			else
				return false;
		}
	}

	// Make an nntp connection (alternate server).
	public function doConnect_A()
	{
		if ($this->_isConnected())
			return true;

		$s = new Sites();
		$site = $s->get();
		$compressionstatus = $site->compressedheaders;
		unset($s, $site);

		$enc = $ret = $ret2 = $connected = false;

		if (defined('NNTP_SSLENABLED_A') && NNTP_SSLENABLED_A == true)
			$enc = 'ssl';

		$retries = 10;
		while($retries >= 1)
		{
			usleep(10000);
			$authent = false;
			$retries--;
			if ($connected === false)
				$ret = $this->connect(NNTP_SERVER_A, $enc, NNTP_PORT_A, 15);

			if(PEAR::isError($ret))
			{
				if ($retries < 1)
					echo $this->c->setcolor('bold', $this->warning).'Cannot connect to server '.NNTP_SERVER_A.(!$enc?' (nonssl) ':'(ssl) ').': '.$ret->getMessage().$this->c->rsetcolor();
			}
			else
				$connected = true;

			if ($connected === true && $authent === false && defined('NNTP_USERNAME_A'))
			{
				if (NNTP_USERNAME_A == '')
					$authent = true;
				else
				{
					$ret2 = $this->authenticate(NNTP_USERNAME_A, NNTP_PASSWORD_A);
					if(PEAR::isError($ret2))
					{
						if ($retries < 1)
							echo $this->c->setcolor('bold', $this->warning).'Cannot authenticate to server '.NNTP_SERVER_A.(!$enc?' (nonssl) ':' (ssl) ').' - '.NNTP_USERNAME_A.' ('.$ret2->getMessage().')'.$this->c->setcolor();
					}
					else
						$authent = true;
				}
			}

			if ($connected && $authent === true)
			{
				if($compressionstatus == '1')
					$this->enableCompression();
				return true;
			}
			else
				return false;
		}
	}

	// Make a nntp connection (no XFeature GZip compression).
	public function doConnectNC()
	{
		return $this->doConnect(false);
	}

	// Quit the nntp connection.
	public function doQuit()
	{
		$this->disconnect();
	}

	// Get only the body of an article (no header).
	public function getMessage($groupname, $partMsgId)
	{
		$summary = $this->selectGroup($groupname);
		if (PEAR::isError($summary))
			return false;

		$body = $this->getBody('<'.$partMsgId.'>', true);
		if (PEAR::isError($body))
		   return false;

		return $this->decodeYenc($body);
	}

	// Get multiple article bodies (string them together).
	public function getMessages($groupname, $msgIds)
	{
		$body = '';
		foreach ($msgIds as $m)
		{
			$message = $this->getMessage($groupname, $m);
			if ($message !== false)
				$body = $body.$message;
			else
				return false;
		}
		return $body;
	}

	// Get a full article (body + header).
	public function get_Article($groupname, $partMsgId)
	{
		$summary = $this->selectGroup($groupname);
		if (PEAR::isError($summary))
			return false;

		$body = $this->getArticle('<'.$partMsgId.'>', true);
		if (PEAR::isError($body))
			return false;

		return $this->decodeYenc($body);
	}

	// Get multiple articles (string them together).
	public function getArticles($groupname, $msgIds)
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

	// Decode a Yenc encoded article body.
	public function decodeYenc($yencodedvar)
	{
		$input = array();
		preg_match('/^(=ybegin.*=yend[^$]*)$/ims', $yencodedvar, $input);
		if (isset($input[1]))
		{
			$ret = '';
			$input = trim(preg_replace('/\r\n/im', '',  preg_replace('/(^=yend.*)/im', '', preg_replace('/(^=ypart.*\\r\\n)/im', '', preg_replace('/(^=ybegin.*\\r\\n)/im', '', $input[1], 1), 1), 1)));

			for( $chr = 0; $chr < strlen($input) ; $chr++)
				$ret .= ($input[$chr] != '=' ? chr(ord($input[$chr]) - 42) : chr((ord($input[++$chr]) - 64) - 42));

			return $ret;
		}
		return false;
	}

	// Enable XFeature compression support for the current connection. Original script : http://pastebin.com/A3YypDAJ
	public function enableCompression()
	{
		$response = $this->_sendCommand('XFEATURE COMPRESS GZIP');
		if (PEAR::isError($response) || $response != 290)
			return false;

		$this->Compression = true;
		return true;
	}

	// Override PEAR_NNTP's function when compression is enabled to use our _getXfeatureTextResponse function.
	public function _getTextResponse()
	{
		if ($this->Compression === true && isset($this->_currentStatusResponse[1]) && stripos($this->_currentStatusResponse[1], 'COMPRESS=GZIP') !== false)
			return $this->_getXfeatureTextResponse();
		else
			return parent::_getTextResponse();
	}

	// Loop over the data when compression is on, add it to a long string, look for a terminator, split the string into an array, return the headers.
	public function _getXfeatureTextResponse()
	{
		$tries = $bytesreceived = $totalbytesreceived = 0;
		$completed = false;
		$data = null;
		// Build a binary array that represents zero results, basically a compressed empty string terminated with .(period) char(13) char(10)
		$erend	= chr(0x03).chr(0x00).chr(0x00).chr(0x00).chr(0x00).chr(0x01).chr(0x2e).chr(0x0d).chr(0x0a);
		$er1	= chr(0x78).chr(0x9C).$erend;
		$er2	= chr(0x78).chr(0x01).$erend;
		$er3	= chr(0x78).chr(0x5e).$erend;
		$er4	= chr(0x78).chr(0xda).$erend;

		while (!feof($this->_socket))
		{
			$completed = false;
			// Get data from the stream.
			$buffer = fgets($this->_socket);
			// Get byte count.
			$bytesreceived = strlen($buffer);
			// If we got no bytes at all try one more time to pull data.
			if ($bytesreceived == 0)
			{
				$buffer = fgets($this->_socket);
				$bytesreceived = strlen($buffer);
			}

			// Get any socket error codes.
			 $errorcode = socket_last_error();

			// If the buffer is zero it's zero, return error.
			if ($bytesreceived === 0)
				return $this->throwError($this->c->setcolor('bold', $this->warning).'The NNTP server has returned no data.'.$this->c->rsetcolor(), 1000);

			// Keep going if no errors.
			if ($errorcode === 0)
			{
				// Append buffer to final data object.
				$data .= $buffer;

				// Update total bytes received.
				$totalbytesreceived += $bytesreceived;

				// Show bytes recieved
				if ($totalbytesreceived > 10240 && $totalbytesreceived%128 == 0)
					echo $this->c->setcolor('bold', $this->primary).'Receiving '.round($totalbytesreceived / 1024).'KB from '.$this->group().".\r".$this->c->rsetcolor();

				// Check to see if we have the magic terminator on the byte stream.
				$b1 = null;
				if ($bytesreceived > 2)
				{
					if (ord($buffer[$bytesreceived-3]) == 0x2e && ord($buffer[$bytesreceived-2]) == 0x0d && ord($buffer[$bytesreceived-1]) == 0x0a)
					{
						// Check if the returned binary string is 11 bytes long, generally an indicator of a compressed empty string.
						if ($totalbytesreceived == 11)
						{
							// Compare the data to the empty string if the data is a compressed empty string. If it is, throw an error.
							if ($data === $er1 || $data === $er2 || $data === $er3 || $data === $er4)
								return $this->throwError($this->c->setcolor('bold', $this->warning).'The NNTP server has returned an empty article. This is normal, the article is probably missing/removed.'.$this->c->rsetcolor(), 1000);
						}
						// We found the terminator.
						else
						{
							if ($totalbytesreceived > 10240)
								echo "\n";
							$completed = true;
						}
					}
				}
			 }
			 else
				 return $this->throwError('Socket error: '.socket_strerror($errorcode), 1000);

			if ($completed === true)
			{
				// Check if the header is valid for a gzip stream, then decompress it.
				if (ord($data[0]) == 0x78 && in_array(ord($data[1]), array(0x01, 0x5e, 0x9c, 0xda)))
					$decomp = @gzuncompress(mb_substr($data , 0 , -3, '8bit'));
				else
					return $this->throwError($this->c->setcolor('bold', $this->warning).'Unable to decompress the data, the header on the gzip stream is invalid.'.$this->c->rsetcolor(), 1000);

				// Split the string of headers into and array of individual headers, then return it.
				if ($decomp != false)
					return explode("\r\n", trim($decomp));
				else
				{
					// Try 5 times to decompress.
					if ($tries++ > 5)
						return $this->throwError($this->c->setcolor('bold', $this->warning).'Decompression Failed after 5 tries, connection closed.'.$this->c->rsetcolor(), 1000);
				}
			}
		}
		// Throw an error if we get out of the loop.
		if (!feof($this->_socket))
			return "Error: Could not find the end-of-file pointer on the gzip stream.\n";

		return $this->throwError($this->c->setcolor('bold', $this->warning).'Decompression Failed, connection closed.'.$this->c->rsetcolor(), 1000);
	}


	// If there is an error with selectGroup(), try to restart the connection, else show the error. Send a 3rd argument, false, for a connection with no compression.
	public function dataError($nntp, $group, $comp=true)
	{
		$nntp->doQuit();
		if ($nntp->doConnect($comp) === false)
			return false;

		$data = $nntp->selectGroup($group);
		if (PEAR::isError($data))
		{
			echo $this->c->setcolor('bold', $this->warning)."Error {$data->code}: {$data->message}\nSkipping group: {$group}\n".$this->c->rsetcolor();
			$nntp->doQuit();
			return false;
		}
		else
			return $data;
	}
}
