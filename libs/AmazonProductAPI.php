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
	 * Constants for product types
	 *
	 * @note More categories can be found here:
	 *       http://docs.amazonwebservices.com/AWSECommerceService/latest/DG/APPNDX_SearchIndexValues.html
	 *
	 * @var string
	 */
	const BOOKS      = "Books";
	const DIGITALMUS = "DigitalMusic";
	const DVD        = "DVD";
	// This can be DigitalDownloads as well.
	const GAMES       = "VideoGames";
	const MP3         = "MP3Downloads";
	const MUSICTRACKS = "MusicTracks";
	const MUSIC       = "Music";

	/**
	 * Your Amazon Access Key Id
	 * @access private
	 * @var string
	 */
	private $public_key = "";

	/**
	 * Your Amazon Secret Access Key
	 * @access private
	 * @var string
	 */
	private $private_key = "";

	 /**
	 * Your Amazon Secret Associate Tag
	 * @access private
	 * @var string
	 */
	private $associate_tag = "";

	/**
	 * The current search string.
	 * @var string
	 */
	private $searchString;

	/**
	 * The current search category.
	 * @var string
	 */
	private $category;

	/**
	 * The current search type.
	 * @var string
	 */
	private $searchType;

	/**
	 * The current search node.
	 * @var string
	 */
	private $searchNode;

	/**
	 * How many times have we tried to query amazon after being throttled
	 * @var int
	 */
	private $tries;

	/**
	 * How many seconds must will we sleep currently while throttled.
	 * @var int
	 */
	private $currentSleepTime = 0;

	/**
	 * Are we using this method currently?
	 * @var bool
	 */
	private $searchProducts = false;

	/**
	 * How many times should we try to query after being throttled.
	 * @var int
	 */
	const maxTries = 3;

	/**
	 * How many seconds should we wait after being throttled.
	 * @var int
	 */
	const sleepTime = 3;

	/**
	 * Every time we are throttled, increase current sleep time by this much.
	 */
	const sleepIncrease = 1;

	/**
	 * Construct.
	 *
	 * @param string $pubk Amazon public key.
	 * @param string $privk Amazon private key.
	 * @param string $associatetag Amazon associate tag.
	 */
	public function __construct($pubk, $privk, $associatetag)
	{
		$this->public_key = (string) $pubk;
		$this->private_key = (string) $privk;
		$this->associate_tag = (string) $associatetag;
		$this->tries = 0;
		$this->currentSleepTime = self::sleepTime;
	}

	/**
	 * Return details of products searched by various types
	 *
	 * @param string $search search term
	 * @param string $category search category
	 * @param string $searchType type of search
	 * @param string $searchNode
	 *
	 * @return bool|mixed
	 */
	public function searchProducts($search, $category, $searchType = "UPC", $searchNode="")
	{
		// Set class vars.
		$this->searchString = $search;
		$this->category = $category;
		$this->searchType = $searchType;
		$this->searchNode = $searchNode;
		$this->searchProducts = true;

		switch($searchType)
		{
			case "UPC" :
				$parameters =
					array(
						"Operation"     => "ItemLookup",
						"ItemId"        => $search,
						"SearchIndex"   => $category,
						"IdType"        => "UPC",
						"ResponseGroup" => "Medium");
				break;

			case "ISBN" :
				$parameters =
					array(
						"Operation" => "ItemLookup",
						"ItemId"        => $search,
						"SearchIndex"   => self::BOOKS,
						"IdType"        => "ISBN",
						"ResponseGroup" => "Medium"
					);
				break;

			case "TITLE" :
				switch($category)
				{
					case "MUSICTRACKS" :
						$parameters =
							array(
								"Operation"     => "ItemSearch",
								//"Title"       => $search,
								"Keywords"      => $search,
								"Sort"          => "titlerank",
								"SearchIndex"   => $category,
								"ResponseGroup" => "Large"
							);
						break;
					default :
						$parameters =
							array(
								"Operation"     => "ItemSearch",
								//"Title"       => $search,
								"Keywords"      => $search,
								"Sort"          => "relevancerank",
								"SearchIndex"   => $category,
								"ResponseGroup" => "Large"
							);
						break;
				}
				break;

			case "TITLE2" :
				$parameters =
					array(
						"Operation"      => "ItemSearch",
						"Title"          => $search,
						//"Keywords"     => $search,
						"Sort"           => "relevancerank",
						"SearchIndex"    => $category,
						"ResponseGroup"  => "Large"
					);
				break;

			// Same as TITLE but add BrowseNodeID param.
			case "NODE" :
				$parameters =
					array(
						"Operation"     => "ItemSearch",
						//"Title"       => $search,
						"Keywords"      => $search,
						"SearchIndex"   => $category,
						"BrowseNode"    => $searchNode,
						"ResponseGroup" => "Large"
					);
				break;
		}
		return $this->verifyXmlResponse($this->queryAmazon($parameters));
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
		$parameters =
			array(
				"Operation"     => "ItemLookup",
				"ItemId"        => $upc_code,
				"SearchIndex"   => $product_type,
				"IdType"        => "UPC",
				"ResponseGroup" => "Medium"
			);

		$xml_response = $this->queryAmazon($parameters);
		return $this->verifyXmlResponse($xml_response);
	}

	/**
	 * Return details of a product searched by ASIN.
	 *
	 * @param int    $asin_code ASIN code of the product to search
	 * @param string $region Domain name extension (com, ca, etc).
	 *
	 * @return bool|mixed
	 */
	public function getItemByAsin($asin_code, $region = "com")
	{
		$parameters =
			array(
				"Operation"      => "ItemLookup",
				"ItemId"         => $asin_code,
				"ResponseGroup"  => "Medium"
			);

		$xml_response = $this->queryAmazon($parameters, $region);
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
		$parameters =
			array(
				"Operation"    => "ItemSearch",
				"Keywords"     => $keyword,
				"SearchIndex"  => $product_type
			);

		$xml_response = $this->queryAmazon($parameters);
		return $this->verifyXmlResponse($xml_response);
	}

	/**
	 * Reset some class object variables.
	 * @void
	 */
	private function resetVars()
	{
		$this->currentSleepTime = self::sleepTime;
		$this->tries = 0;
		$this->searchProducts = false;
	}

	/**
	 * Check if the xml received from Amazon is valid
	 *
	 * @param mixed $response xml response to check
	 *
	 * @return bool|mixed false if the xml is invalid, mixed if the xml response if it is valid
	 * @throws exception if we could not connect to Amazon
	 */
	private function verifyXmlResponse($response)
	{
		// Check if there's an error.
		if (isset($response->Error)) {
			// Check if we are throttled.
			if ($this->searchProducts && strpos(strtolower($response->Error->Message), 'slower rate') !== false && $this->tries <= self::maxTries) {

				// Sleep to let the throttle wear off.
				sleep($this->currentSleepTime);

				// Increase next sleep time.
				$this->currentSleepTime += self::sleepIncrease;

				// Increment current tries.
				$this->tries++;

				// Try again.
				return $this->searchProducts($this->searchString, $this->category, $this->searchType, $this->searchNode);
			}
			// Echo the message.
			echo $response->Error->Message . "\n";
			$this->resetVars();
			throw new \Exception($response->Error->Message);
		} else if ($response === False) {
			$this->resetVars();
			throw new \Exception("Could not connect to Amazon.");
		} else if ($response == "missingkey") {
			$this->resetVars();
			throw new \Exception("Missing Amazon API key or associate tag.");
		} else {
			if (isset($response->Items->Item->ItemAttributes->Title)) {
				$this->resetVars();
				return ($response);
			} else {
				$this->resetVars();
				throw new \Exception("Invalid xml response.");
			}
		}
	}

	/**
	 * Query Amazon with the issued parameters
	 *
	 * @param array  $parameters parameters to query around
	 * @param string $region Domain name extension (com, ca, etc).
	 *
	 * @return bool|SimpleXMLElement|string xml query response
	 */
	private function queryAmazon($parameters, $region = "com")
	{
		return $this->aws_signed_request($region, $parameters, $this->public_key, $this->private_key, $this->associate_tag);
	}

	/**
	 * @param        $region
	 * @param        $params
	 * @param        $public_key
	 * @param        $private_key
	 * @param string $associate_tag
	 *
	 * @return bool|SimpleXMLElement|string
	 */
	private function aws_signed_request($region, $params, $public_key, $private_key, $associate_tag = "")
	{

		if ($public_key !== "" && $private_key !== "" && $associate_tag !== "")
		{
			$method = "GET";
			// Must be in small case.
			$host = "ecs.amazonaws.".$region;
			$uri = "/onca/xml";

			$params["Service"]        = "AWSECommerceService";
			$params["AWSAccessKeyId"] = $public_key;
			$params["AssociateTag"]   = $associate_tag;
			$params["Timestamp"]      = gmdate("Y-m-d\TH:i:s\Z");
			$params["Version"]        = "2009-03-31";

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

			/* Calculate the signature using HMAC with SHA256 and base64-encoding.
			* The 'hash_hmac' function is only available from PHP 5 >= 5.1.2.
			*/
			$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));

			// Encode the signature for the request.
			$signature = str_replace("%7E", "~", rawurlencode($signature));

			// Create request.
			$request = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$request);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt_array($ch, nzedb\utility\Utility::curlSslContextOptions());

			$xml_response = curl_exec($ch);
			if ($xml_response === False) {
				return False;
			} else {
				// Parse XML.
				$parsed_xml = @simplexml_load_string($xml_response);
				return ($parsed_xml === False) ? False : $parsed_xml;
			}
		} else {
			return "missingkey";
		}
	}
}
