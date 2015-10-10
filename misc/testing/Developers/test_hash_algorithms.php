<?php

if (!isset($argv[1]) || !isset($argv[2])) {
	exit (
		'Argument 1 is a input string. ie PRE name.' . PHP_EOL .
		'Argument 2 is a expected hash or encoding. ie MD5 string. Passing true on Argument 3 ignores this.' . PHP_EOL .
		'Argument 3 (optional) False, exit on first match. True, write all matches to text file in current path.' . PHP_EOL .
		'ie: php test_hash_algorithms.php Dog.with.a.Blog.S02E16.Love.Loss.and.a.Beanbag.Toss.HDTV.x264-QCF 11506192c6d92e0c9c795b9997d9396226dbdf62' . PHP_EOL .
		'ie: php test_hash_algorithms.php Anchorman.2.The.Legend.Continues.2013.UNRATED.WEBRip.x264-FLS false true' . PHP_EOL
	);
}

/**
 * Test various hashing/encoding/etc on a string.
 * Class hash_algorithms
 */
class hash_algorithms
{
	/**
	 * The input string.
	 * @var string
	 */
	protected $_inputString;

	/**
	 * The string we are expecting to get.
	 * @var array
	 */
	protected $_expectedString;

	/**
	 * Write results to file?
	 * @var bool
	 */
	protected $_writeToFile;

	/**
	 * @param string $inputString
	 * @param string $expectedString
	 * @param bool $writeToFile
	 *
	 * @access public
	 */
	public function __construct($inputString, $expectedString, $writeToFile)
	{
		$this->_inputString = $inputString;
		$this->_expectedString = array(
			$expectedString,
			strtolower($expectedString),
			strtoupper($expectedString),
			strrev($expectedString)
		);
		$this->_writeToFile = $writeToFile;
		$this->_testStrings();
	}

	/**
	 * Test various hash algorithms on strings.
	 *
	 * @access protected
	 * @void
	 */
	protected function _testStrings()
	{
		if ($this->_writeToFile) {
			file_put_contents('hash_matches.txt', '');
		}

		$firstArray = $this->_hashesToArray($this->_inputString);

		$secondArray = array();
		foreach ($firstArray as $key => $value) {
			if (!$this->_writeToFile) {
				if (in_array($value, $this->_expectedString)) {
					exit(
						'[' .
						$this->_inputString .
						']=>[' .
						$key .
						']=>' .
						$value .
						']' .
						PHP_EOL
					);
				}
			} else {
				file_put_contents('hash_matches.txt', $key . "\t\t" . $value . PHP_EOL, FILE_APPEND);
			}
			$secondArray[$key] = $this->_hashesToArray($value);
		}

		$thirdArray = array();
		foreach ($secondArray as $key => $value) {
			foreach ($value as $key2 => $value2) {
				if (!$this->_writeToFile) {
					if (in_array($value2, $this->_expectedString)) {
						exit(
							'[' .
							$this->_inputString .
							']=>[' .
							$key .
							']=>[' .
							$firstArray[$key] .
							']=>[' .
							$key2 .
							']=>[' .
							$value2 .
							']' .
							PHP_EOL
						);
					}
				} else {
					file_put_contents('hash_matches.txt', $key . ' => ' . $key2 . "\t\t" . $value2 . PHP_EOL, FILE_APPEND);
				}
				$thirdArray[$key][$key2] = $this->_hashesToArray($value2);
			}
		}

		foreach ($thirdArray as $key => $value) {
			foreach ($value as $key2 => $value2) {
				foreach ($value2 as $key3 => $value3) {
					if (!$this->_writeToFile) {
						if (in_array($value3, $this->_expectedString)) {
							exit(
								'[' .
								$this->_inputString .
								']=>[' .
								$key .
								']=>[' .
								$firstArray[$key] .
								']=>[' .
								$key2 .
								']=>[' .
								$value2 .
								']=>[' .
								$key3 .
								']=>[' .
								$value3 .
								']' .
								PHP_EOL
							);
						}
					} else {
						file_put_contents('hash_matches.txt',
							$key . ' => ' . $key2 . ' => ' . $key3 . "\t\t" . $value3 . PHP_EOL, FILE_APPEND
						);
					}
				}
			}
		}
	}

	/**
	 * Return various versions of a input string to hash.
	 *
	 * @param string $string
	 *
	 * @return array
	 */
	protected function _hashesToArray($string)
	{
		$strings = array(
			'input'         => $string,
			'lower'         => strtolower($string),
			'lower_reverse' => strtolower(strrev($string)),
			'upper_reverse' => strtoupper(strrev($string)),
			'upper'         => strtoupper($string),
			'reverse'        => strrev($string),
			'reverse_upper' => strrev(strtoupper($string)),
			'reverse_lower' => strrev(strtolower($string)),
		);

		$hashTypes = array('md5', 'md4', 'sha1', 'sha256', 'sha512');
		$tmpArray = array();
		foreach ($hashTypes as $hash) {
			foreach ($strings as $key => $value) {
				$tmpArray[$hash . '_' . $key] = hash($hash, $value, false);
			}
		}

		foreach ($strings as $key => $value) {
			$tmpArray['input_' . $key] = $value;
			$tmpArray['base64_' . $key] = base64_encode($value);
			$tmpArray['crc32_' . $key] = crc32($value);
		}

		return $tmpArray;
	}
}

new hash_algorithms($argv[1], $argv[2], ((isset($argv[3]) && strtolower($argv[3]) === 'true') ? true : false));
