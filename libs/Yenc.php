<?php
/* This should be moved to the nZEDb_LIBS directory */

/*
* yenc.class.php - yEnc PHP Class.
* Copyright (c) 2002 Ryan Grove <ryan@wonko.com>. All rights reserved.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; either version 2 of the License, or any later
* version.
*
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
* Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/

/**
* yEnc PHP Class.
*
* This class provides functions to encode and decode yEnc files
* and strings. It meets the specifications of version 1.3 of the
* yEnc working draft (http://www.yenc.org/yenc-draft.1.3.txt)
* and also incorporates several unofficial (but recommended) features such
* as escaping of tab, space and period (.) characters.
*
* @author Ryan Grove (ryan\@wonko.com)
* @date November 26, 2002
* @version 1.0.0
*/
class Yenc
{
	/** Text of the most recent error message (if any). */
	var $error;

	/**
	 * yEncodes a string and returns it.
	 *
	 * @param string String to encode.
	 * @param filename Name to use as the filename in the yEnc header (this
	 *   does not have to be an actual file).
	 * @param linelen Line length to use (can be up to 254 characters).
	 * @param crc32 Set to <i>true</i> to include a CRC checksum in the
	 *   trailer to allow decoders to verify data integrity.
	 * @return yEncoded string or <i>false</i> on error.
	 * @see decode()
	 */
	function encode($string, $filename, $linelen = 128, $crc32 = true)
	{
		// yEnc 1.3 draft doesn't allow line lengths of more than 254 bytes.
		if ($linelen > 254)
			$linelen = 254;

		if ($linelen < 1)
		{
			$this->error = "$linelen is not a valid line length.";
			return false;
		}

		$encoded = '';
		// Encode each character of the string one at a time.
		for( $i = 0; $i < strlen($string); $i++)
		{
			$value = (ord($string{$i}) + 42) % 256;

			// Escape NULL, TAB, LF, CR, space, . and = characters.
			if ($value == 0 || $value == 9 || $value == 10 || $value == 13 || $value == 32 || $value == 46 || $value == 61)
				$encoded .= "=".chr(($value + 64) % 256);
			else
				$encoded .= chr($value);
		}

		// Wrap the lines to $linelen characters
		// TODO: Make sure we don't split escaped characters in half, as per the yEnc spec.
		$encoded = trim(chunk_split($encoded, $linelen));

		// Tack a yEnc header onto the encoded string.
		$encoded = "=ybegin line=$linelen size=".strlen($string)." name=".trim($filename)."\r\n".$encoded;
		$encoded .= "\r\n=yend size=".strlen($string);

		// Add a CRC32 checksum if desired.
		if ($crc32 === true)
			$encoded .= " crc32=".strtolower(sprintf("%04X", crc32($string)));

		return $encoded."\r\n";
	}

	/**
	 * yDecodes an encoded string and either writes the result to a file
	 * or returns it as a string.
	 *
	 * @param string yEncoded string to decode.
	 * @param destination Destination directory where the decoded file will
	 *   be written. This must be a valid directory <b>with no trailing
	 *   slash</b> to which PHP has write access. If <i>destination</i> is
	 *   not specified, the decoded file will be returned rather than
	 *   written to the disk.
	 * @return If <i>destination</i> is not set, the decoded file will be
	 *   returned as a string. Otherwise, <i>true</i> will be returned on
	 *   success. In either case, <i>false</i> will be returned on error.
	 * @see encode()
	 */
	function decode($string, $destination = "")
	{
		$encoded = array();
		$header  = array();
		$trailer = array();

		// Extract the yEnc string itself.
		preg_match("/^(=ybegin.*=yend[^$]*)$/ims", $string, $encoded);
		$encoded = $encoded[1];

		// Extract the file size from the header.
		preg_match("/^=ybegin.*size=([^ $]+)/im", $encoded, $header);
		$headersize = $header[1];

		// Extract the file name from the header.
		preg_match("/^=ybegin.*name=([^\\r\\n]+)/im", $encoded, $header);
		$filename = trim($header[1]);

		// Extract the file size from the trailer.
		preg_match("/^=yend.*size=([^ $\\r\\n]+)/im", $encoded, $trailer);
		$trailersize = $trailer[1];

		// Extract the CRC32 checksum from the trailer (if any).
		preg_match("/^=yend.*crc32=([^ $\\r\\n]+)/im", $encoded, $trailer);
		$crc = @trim(@$trailer[1]);

		// Remove the header and trailer from the string before parsing it.
		$encoded = preg_replace("/(^=ybegin.*\\r\\n)/im", "", $encoded, 1);
		$encoded = preg_replace("/(^=yend.*)/im", "", $encoded, 1);

		// Remove linebreaks from the string.
		$encoded = trim(str_replace("\r\n", "", $encoded));

		// Make sure the header and trailer filesizes match up.
		if ($headersize != $trailersize)
		{
			$this->error = "Header and trailer file sizes do not match. This is a violation of the yEnc specification.";
			return false;
		}

		// Decode
		$decoded = '';
		for( $i = 0; $i < strlen($encoded); $i++)
		{
			if ($encoded{$i} == "=")
			{
				$i++;
				$decoded .= chr((ord($encoded{$i}) - 64) - 42);
			}
			else
			{
				$decoded .= chr(ord($encoded{$i}) - 42);
			}
		}

		// Make sure the decoded filesize is the same as the size specified in the header.
		if (strlen($decoded) != $headersize)
		{
			$this->error = "Header file size and actual file size do not match. The file is probably corrupt.";
			return false;
		}

		// Check the CRC value
		if ($crc != "" && strtolower($crc) != strtolower(sprintf("%04X", crc32($decoded))))
		{
			$this->error = "CRC32 checksums do not match. The file is probably corrupt.";
			return false;
		}

		// Should we write to a file or spit back a string?
		if ($destination == "")
		{
			// Spit back a string.
			return $decoded;
		}
		else
		{
			// Make sure the destination directory exists.
			if (!is_dir($destination))
			{
				$this->error = "Destination directory ($destination) does not exist.";
				return false;
			}

			// Write the file.
			// TODO: Replace invalid characters in $filename with underscores.
			if ($fp = @fopen("$destination/$filename", "wb"))
			{
				fwrite($fp, $decoded);
				fclose($fp);
				return true;
			}
			else
			{
				$this->error = "Could not open $destination/$filename for write access.";
				return false;
			}
		}
	}

	/**
	 * yEncodes a file and returns it as a string.
	 *
	 * @param filename Full path and filename of the file to be encoded.
	 *   This can also be a URL (http:// or ftp://).
	 * @param linelen Line length to use (can be up to 254 characters).
	 * @param crc32 Set to <i>true</i> to include a CRC checksum in the
	 *   trailer to allow decoders to verify data integrity.
	 * @return yEncoded file, or <i>false</i> on error.
	 * @see decodeFile()
	 */
	function encodeFile($filename, $linelen = 128, $crc32 = true)
	{
		// Read the file into memory.
		if ($fp = @fopen($filename, "rb"))
		{
			while (!feof($fp))
				$file .= fread($fp, 8192);

			fclose($fp);

			// Encode the file.
			return $this->encode($file, $filename, $linelen, $crc32);
		}
		else
		{
			$this->error = "Could not open $filename for read access.";
			return false;
		}
	}

	/**
	 * yDecodes an encoded file and writes the decoded file to the
	 * specified directory, or returns it as a string if no directory is
	 * specified.
	 *
	 * @param filename Full path and filename of the file to be decoded.
	 * @param destination Destination directory where the decoded file will
	 *   be written. This must be a valid directory <b>with no trailing
	 *   slash</b> to which PHP has write access. If <i>destination</i> is
	 *   not specified, the decoded file will be returned rather than
	 *   written to the disk.
	 * @return If <i>destination</i> is not set, the decoded file will be
	 *   returned as a string. Otherwise, <i>true</i> will be returned on
	 *   success. In either case, <i>false</i> will be returned on error.
	 * @see encodeFile()
	 */
	function decodeFile($filename, $destination = "")
	{
		// Read the encoded file into memory.
		if ($fp = @fopen($filename, "rb"))
		{
			while (!feof($fp))
				$infile .= fread($fp, 8192);

			fclose($fp);

			// Send the file to the decoder.
			if ($out = $this->decode($infile, $destination))
			{
				return $out;
			}
			else
			{
				// Decoding error.
				return false;
			}
		}
		else
		{
			$this->error = "Could not open $filename for read access.";
			return false;
		}
	}
}

/*========================================================================*
* Documentation (there's no actual code below here)                      *
*========================================================================*/

/**
* @mainpage yEnc PHP Class
*
* @section intro Introduction
*
* yEnc is an informal standard for efficiently encoding binary files for
* transmission on Usenet, in email, and in other similar mediums. It is
* more efficient than other widely-used encoding methods, resulting in
* smaller files (which in turn results in smaller downloads, which makes
* people happy).
*
* This class implements a working yEnc encoder and decoder according
* to the yEncode working draft specification as of version 1.3, which can
* be found at http://www.yenc.org/yenc-draft.1.3.txt
*
* The only part of the yEnc spec that this class does not implement is
* encoding and decoding of multipart yEncoded binaries. Support for this
* may be added at a later date, but don't get your hopes up.
*
* @section limitations Limitations of PHP
*
* PHP is not an ideal language for implementing something like this. The
* main issue is speed. The first thing you'll notice when you use the
* class is that it is @em incredibly slow. Despite the fact that the
* calculations involved are very simple, and as optimized as they can
* possibly be, the problem is that there are just a @em lot of them, and
* PHP is not a speedy language.
*
* If you want a fast yEnc implementation, you should use C.
*
* So why, then, did I write this class? I don't know really. I was bored.
* It's entirely possible that it could come in handy for dealing with
* very small binary files in a PHP-only environment. Mostly, I just like
* toying with new concepts.
*
* @section support Support & Contact Info
*
* If you find a bug, or if you have a suggestion or comment, I'd love to
* hear from you. If you need someone to hold your hand, please don't waste
* my time. I went to a lot more trouble than I probably should have making

* this class extremely easy to use, and I've also done my best to provide
* thorough documentation, so stupid questions will very likely be met with
* anger and profanity.
*
* That said, you can contact me via email at ryan\@wonko.com and you'll
* always be able to find the latest version of this class and its
* documentation at http://wonko.com/yenc/
*
* @section license License & Copyright
*
* Copyright (c) 2002 Ryan Grove. All rights reserved.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; either version 2 of the License, or any later
* version.
*
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
* Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/
?>
