<?php

	/**
	 * Class to access Amazons Product Advertising API
	 * @author Sameer Borate
	 * @link http://www.codediesel.com
	 * @version 1.0
	 * All requests are not implemented here. You can easily
	 * implement the others from the ones given below.
	 */
	
	
	/*
	Permission is hereby granted, free of charge, to any person obtaining a
	copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation
	the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
	THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
	DEALINGS IN THE SOFTWARE.
	
	http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/BasicAuthProcess.html

	*/
 	
	class AmazonProductAPI
	{
		/**
		 * Your Amazon Access Key Id
		 * @access private
		 * @var string
		 */
		private $public_key	 = "";
		
		/**
		 * Your Amazon Secret Access Key
		 * @access private
		 * @var string
		 */
		private $private_key	= "";
		
		 /**
		 * Your Amazon Secret Associate Tag
		 * @access private
		 * @var string
		 */
		private $associate_tag	= "";
		
		/**
		 * Constants for product types
		 * @access public
		 * @var string
		 */
		
		/*
			Only three categories are listed here. 
			More categories can be found here:
			http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/APPNDX_SearchIndexValues.html
		*/
		const MUSIC = "Music";
		const MP3 = "MP3Downloads"; //this could be DigitalDownloads as well
		const DVD   = "DVD";
		const GAMES = "VideoGames";
		const BOOKS = "Books";
		

				public function __construct($pubk, $privk, $associatetag)
				{
					$this->public_key = (string) $pubk;
					$this->private_key = (string) $privk;
					$this->associate_tag = (string) $associatetag;
				}
		
		/**
		 * Check if the xml received from Amazon is valid
		 * 
		 * @param mixed $response xml response to check
		 * @return bool false if the xml is invalid
		 * @return mixed the xml response if it is valid
		 * @return exception if we could not connect to Amazon
		 */
		private function verifyXmlResponse($response)
		{
			if ($response === False)
			{
				throw new Exception("Could not connect to Amazon.");
			}
			else if ($response == "missingkey")
			{
				throw new Exception("Missing Amazon API key or associate tag.");
			}
			else
			{
				if (isset($response->Items->Item->ItemAttributes->Title))
				{
					return ($response);
				}
				else
				{
					throw new Exception("Invalid xml response.");
				}
			}
		}
		
		
		/**
		 * Query Amazon with the issued parameters
		 * 
		 * @param array $parameters parameters to query around
		 * @return simpleXmlObject xml query response
		 */
		private function queryAmazon($parameters)
		{
			return aws_signed_request("com", $parameters, $this->public_key, $this->private_key, $this->associate_tag);
		}
		
		
		/**
		 * Return details of products searched by various types
		 * 
		 * @param string $search search term
		 * @param string $category search category		 
		 * @param string $searchType type of search
		 * @return mixed simpleXML object
		 */
		public function searchProducts($search, $category, $searchType = "UPC", $searchNode="")
		{
			$allowedTypes = array("UPC", "TITLE", "ARTIST", "KEYWORD", "NODE");
			$allowedCategories = array("Music", "DVD", "VideoGames", "MP3Downloads");
			
			switch($searchType) 
			{
				case "UPC" :	$parameters = array("Operation"	 => "ItemLookup",
													"ItemId"		=> $search,
													"SearchIndex"   => $category,
													"IdType"		=> "UPC",
													"ResponseGroup" => "Medium");
								break;
				
				case "TITLE" :  $parameters = array("Operation"	 => "ItemSearch",
													//"Title"	  	=> $search,
													"Keywords"	 	=> $search,
													"SearchIndex"   => $category,
													"ResponseGroup" => "Large");
								break;
				
				//same as TITLE but add BrowseNodeID param				
				case "NODE" :  $parameters = array("Operation"	 	=> "ItemSearch",
													//"Title"	  	=> $search,
													"Keywords"	 	=> $search,
													"SearchIndex"   => $category,
													"BrowseNode"	=> $searchNode,
													"ResponseGroup" => "Large");
								break;
			
			}
			
			$xml_response = $this->queryAmazon($parameters);
			
			return $this->verifyXmlResponse($xml_response);

		}
		
		
		/**
		 * Return details of a product searched by UPC
		 * 
		 * @param int $upc_code UPC code of the product to search
		 * @param string $product_type type of the product
		 * @return mixed simpleXML object
		 */
		public function getItemByUpc($upc_code, $product_type)
		{
			$parameters = array("Operation"	 => "ItemLookup",
								"ItemId"		=> $upc_code,
								"SearchIndex"   => $product_type,
								"IdType"		=> "UPC",
								"ResponseGroup" => "Medium");
								
			$xml_response = $this->queryAmazon($parameters);
			
			return $this->verifyXmlResponse($xml_response);

		}
		
		
		/**
		 * Return details of a product searched by ASIN
		 * 
		 * @param int $asin_code ASIN code of the product to search
		 * @return mixed simpleXML object
		 */
		public function getItemByAsin($asin_code)
		{
			$parameters = array("Operation"	 => "ItemLookup",
								"ItemId"		=> $asin_code,
								"ResponseGroup" => "Medium");
								
			$xml_response = $this->queryAmazon($parameters);
			
			return $this->verifyXmlResponse($xml_response);
		}
		
		
		/**
		 * Return details of a product searched by keyword
		 * 
		 * @param string $keyword keyword to search
		 * @param string $product_type type of the product
		 * @return mixed simpleXML object
		 */
		public function getItemByKeyword($keyword, $product_type)
		{
			$parameters = array("Operation"   => "ItemSearch",
								"Keywords"	=> $keyword,
								"SearchIndex" => $product_type);
								
			$xml_response = $this->queryAmazon($parameters);
			
			return $this->verifyXmlResponse($xml_response);
		}

	}
		

	function  aws_signed_request($region,$params,$public_key,$private_key,$associate_tag)
	{
		
		if ($public_key !== "" && $private_key !== "" && $associate_tag !== "")
		{
			$method = "GET";
			$host = "ecs.amazonaws.".$region; // must be in small case
			$uri = "/onca/xml";
		
		
			$params["Service"]		  = "AWSECommerceService";
			$params["AWSAccessKeyId"]   = $public_key;
			$params["AssociateTag"]		= $associate_tag;
			$params["Timestamp"]		= gmdate("Y-m-d\TH:i:s\Z");
			$params["Version"]		  = "2009-03-31";

			/* The params need to be sorted by the key, as Amazon does this at
			their end and then generates the hash of the same. If the params
			are not in order then the generated hash will be different thus
			failing the authetication process.
			*/
			ksort($params);
		
			$canonicalized_query = array();

			foreach ($params as $param=>$value)
			{
				$param = str_replace("%7E", "~", rawurlencode($param));
				$value = str_replace("%7E", "~", rawurlencode($value));
				$canonicalized_query[] = $param."=".$value;
			}
		
			$canonicalized_query = implode("&", $canonicalized_query);

			$string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
		
			/* calculate the signature using HMAC with SHA256 and base64-encoding.
			The 'hash_hmac' function is only available from PHP 5 >= 5.1.2.
			*/
			$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
		
			/* encode the signature for the request */
			$signature = str_replace("%7E", "~", rawurlencode($signature));
		
			/* create request */
			$request = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;
			/* I prefer using CURL */
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$request);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

			$xml_response = curl_exec($ch);
		
			/* If cURL doesn't work for you, then use the 'file_get_contents'
			function as given below.
			*/
			//$xml_response = file_get_contents($request);
		
			if ($xml_response === False)
			{
				return False;
			}
			else
			{
				/* parse XML */
				$parsed_xml = @simplexml_load_string($xml_response);
				return ($parsed_xml === False) ? False : $parsed_xml;
			}
		}
		else
		{
			return "missingkey";
		}
	}

//
// LIST OF GAME BROWSENODE's (for future reference)
// Calculated from :
// $params = array('Operation'=>'BrowseNodeLookup', 'BrowseNodeId'=>'11846801');
// $resp = aws_signed_request("com", $params, $this->public_key, $this->private_key);
// print_r($resp);
//

/*
// Base Nodes:
[BrowseNodeId] => 468642
[Name] => Video Games

[BrowseNodeId] => 11846801
[Name] => Video Game Categories


// Video Game Categories Nodes:
[BrowseNodeId] => 14210751
[Name] => PlayStation 3

[BrowseNodeId] => 301712
[Name] => PlayStation 2

[BrowseNodeId] => 14220161
[Name] => Xbox 360

[BrowseNodeId] => 14218901
[Name] => Wii

[BrowseNodeId] => 229575
[Name] => PC Games

[BrowseNodeId] => 229647
[Name] => Mac Games

[BrowseNodeId] => 11075831
[Name] => Nintendo DS

[BrowseNodeId] => 11075221
[Name] => Sony PSP

[BrowseNodeId] => 294940
[Name] => More Systems


// More Systems Nodes
[BrowseNodeId] => 10988231
[Name] => 3DO

[BrowseNodeId] => 10989511
[Name] => Atari 2600

[BrowseNodeId] => 10990151
[Name] => Atari 5200

[BrowseNodeId] => 10990791
[Name] => Atari 7800

[BrowseNodeId] => 10991431
[Name] => Atari Jaguar

[BrowseNodeId] => 10992071
[Name] => Atari Lynx

[BrowseNodeId] => 10993351
[Name] => ColecoVision

[BrowseNodeId] => 10993991
[Name] => Commodore 64

[BrowseNodeId] => 10994631
[Name] => Commodore Amiga

[BrowseNodeId] => 1272528011
[Name] => Game Boy

[BrowseNodeId] => 229783
[Name] => Game Boy Color

[BrowseNodeId] => 541020
[Name] => Game Boy Advance

[BrowseNodeId] => 541022
[Name] => GameCube

[BrowseNodeId] => 10995911
[Name] => Intellivision

[BrowseNodeId] => 290573
[Name] => Linux Games

[BrowseNodeId] => 541018
[Name] => NEOGEO Pocket

[BrowseNodeId] => 566458
[Name] => Nintendo NES

[BrowseNodeId] => 229763
[Name] => Nintendo 64

[BrowseNodeId] => 10986071
[Name] => PDAs

[BrowseNodeId] => 229773
[Name] => PlayStation

[BrowseNodeId] => 11000181
[Name] => Sega CD

[BrowseNodeId] => 229793
[Name] => Sega Dreamcast

[BrowseNodeId] => 294942
[Name] => Sega Game Gear

[BrowseNodeId] => 294943
[Name] => Sega Genesis

[BrowseNodeId] => 11002481
[Name] => Sega Master System

[BrowseNodeId] => 294944
[Name] => Sega Saturn

[BrowseNodeId] => 294945
[Name] => Super Nintendo

[BrowseNodeId] => 11004961
[Name] => TurboGrafx 16

[BrowseNodeId] => 537504
[Name] => Xbox
*/

?>
