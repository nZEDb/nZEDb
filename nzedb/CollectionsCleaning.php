<?php
namespace nzedb;

use nzedb\db\Settings;

/**
 * Cleans names for collections/imports/namefixer.
 *
 * Class CollectionsCleaning
 */
class CollectionsCleaning
{
	/**
	 * Used for matching endings in article subjects.
	 * @const
	 * @string
	 */
	const REGEX_END = '[-_\s]{0,3}yEnc$/ui';

	/**
	 * Used for matching file extension endings in article subjects.
	 * @const
	 * @string
	 */
	const REGEX_FILE_EXTENSIONS = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar|\.7z)?(\d{1,3}\.rev"|\.vol\d+\+\d+.+?"|\.[A-Za-z0-9]{2,4}"|")';

	/**
	 * Used for matching size strings in article subjects.
	 * @example ' - 365.15 KB - '
	 * @const
	 * @string
	 */
	const REGEX_SUBJECT_SIZE = '[-_\s]{0,3}\d+([.,]\d+)? [kKmMgG][bB][-_\s]{0,3}';

	/**
	 * @var string
	 */
	public $e0;

	/**
	 * @var string
	 */
	public $e1;

	/**
	 * @var string
	 */
	public $e2;

	/**
	 * @var string
	 */
	public $groupName = '';

	/**
	 * @var string
	 */
	public $subject = '';

	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var Regexes
	 */
	protected $_regexes;

	/**
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		// Extensions.
		$this->e0 = self::REGEX_FILE_EXTENSIONS;
		$this->e1 = self::REGEX_FILE_EXTENSIONS . self::REGEX_END;
		$this->e2 = self::REGEX_FILE_EXTENSIONS . self::REGEX_SUBJECT_SIZE . self::REGEX_END;

		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->_regexes = new Regexes(['Settings' => $this->pdo, 'Table_Name' => 'collection_regexes']);
	}

	/**
	 * Cleans a usenet subject returning a string that can be used to "merge" files together, a pretty subject, a categoryID and the name status.
	 *
	 * @param string $subject   Subject to parse.
	 * @param string $groupName Group to work in.
	 *
	 * @return string
	 */
	public function collectionsCleaner($subject, $groupName)
	{
		$this->subject = $subject;
		$this->groupName = $groupName;

		// Try DB regex first.
		$potentialString = $this->_regexes->tryRegex($subject, $groupName);
		if ($potentialString) {
			return $potentialString;
		}

		switch ($groupName) {
			/*
			case 'alt.binaries.this.is.an.example':
				return $this->_example_method_name();
			*/
			case null:
			default:
				return $this->generic();
		}
	}

	//	Cleans usenet subject before inserting, used for collectionhash. If no regexes matched on collectionsCleaner.
	protected function generic()
	{
		// For non music groups.
		if (!preg_match('/\.(flac|lossless|mp3|music|sounds)/', $this->groupName)) {
			// File/part count.
			$cleanSubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\)|\(|\[|\s)\d{1,5}(\/|(\s|_)of(\s|_)|-)\d{1,5}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|[^\d]{4}-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $this->subject);
			// File extensions.
			$cleanSubject = preg_replace('/' . $this->e0 . '/i', ' ', $cleanSubject);
			// File extensions - If it was not in quotes.
			$cleanSubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleanSubject);
			// File Sizes - Non unique ones.
			$cleanSubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d+KB\s(yENC)?|"?\s\d+\sbytes?|[- ]?\d+(\.|,)?\d+\s(g|k|m)?B\s-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc$/i', ' ', $cleanSubject);
			// Random stuff.
			$cleanSubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleanSubject);
			// Multi spaces.
			return utf8_encode(trim(preg_replace('/\s\s+/', ' ', $cleanSubject)));
		} // Music groups.
		else {
			// Try some music group regexes.
			$musicSubject = $this->musicSubject();
			if ($musicSubject !== false) {
				return $musicSubject;
				// Parts/files
			} else {
				$cleanSubject = preg_replace('/((( \(\d\d\) -|(\d\d)? - \d\d\.|\d{4} \d\d -) | - \d\d-| \d\d\. [a-z]).+| \d\d of \d\d| \dof\d)\.mp3"?|(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|-)\d{1,4}(\)|\]|\s|$|:)|\(\d{1,3}\|\d{1,3}\)|-\d{1,3}-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s|\d{1,3} - of \d{1,3}/i', ' ', $this->subject);
			}
			// Anything between the quotes. Too much variance within the quotes, so remove it completely.
			$cleanSubject = preg_replace('/".+"/i', ' ', $cleanSubject);
			// File extensions - If it was not in quotes.
			$cleanSubject = preg_replace('/(-? [a-z0-9]+-?|\(?\d{4}\)?(_|-)[a-z0-9]+)\.jpg"?| [a-z0-9]+\.mu3"?|((\d{1,3})?\.part(\d{1,5})?|\d{1,5} ?|sample|- Partie \d+)?\.(7z|\d{3}(?=(\s|"))|avi|diz|docx?|epub|idx|iso|jpg|m3u|m4a|mds|mkv|mobi|mp4|nfo|nzb|par(\s?2|")|pdf|rar|rev|rtf|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|xls|zip|z{2,3})"?|(\s|(\d{2,3})?-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', ' ', $cleanSubject);
			// File Sizes - Non unique ones.
			$cleanSubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d+KB\s(yENC)?|"?\s\d+\sbytes?|[- ]?\d+[.,]?\d+\s(g|k|m)?B\s-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s|yEnc \d+k$|{\d+ yEnc bytes}|yEnc \d+ |\(\d+ ?(k|m|g)?b(ytes)?\) yEnc$/i', ' ', $cleanSubject);
			// Random stuff.
			$cleanSubject = preg_replace('/AutoRarPar\d{1,5}|\(\d+\)( |  )yEnc|\d+(Amateur|Classic)| \d{4,}[a-z]{4,} |part\d+/i', ' ', $cleanSubject);
			// Multi spaces.
			$cleanSubject = utf8_encode(trim(preg_replace('/\s\s+/i', ' ', $cleanSubject)));
			// If the subject is too similar to another because it is so short, try to extract info from the subject.
			if (strlen($cleanSubject) <= 10 || preg_match('/^[-a-z0-9$ ]{1,7}yEnc$/i', $cleanSubject)) {
				$x = '';
				if (preg_match('/.*("[A-Z0-9]+).*?"/i', $this->subject, $match)) {
					$x = $match[1];
				}
				if (preg_match_all('/[^A-Z0-9]/i', $this->subject, $match1)) {
					$start = 0;
					foreach ($match1[0] as $add) {
						if ($start > 2) {
							break;
						}
						$x .= $add;
						$start++;
					}
				}
				$newName = preg_replace('/".+?"/', '', $this->subject);
				$newName = preg_replace('/[a-z0-9]|' . $this->e0 . '/i', '', $newName);
				return $cleanSubject . $newName . $x;
			} else {
				return $cleanSubject;
			}
		}
	}

	// Generic regexes for music groups.
	protected function musicSubject()
	{
		//Broderick_Smith-Unknown_Country-2009-404 "00-broderick_smith-unknown_country-2009.sfv" yEnc
		if (preg_match('/^(\w{10,}-[a-zA-Z0-9]+ ")\d\d-.+?" yEnc$/', $this->subject, $match)) {
			return $match[1];
		} else {
			return false;
		}
	}
}
