<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\test\rules;

class EndsWithCloseTagNewline extends \li3_quality\test\Rule {

	public function apply($testable, array $config = array()) {
		$message = "File does not end with ?>";
		$lines = $testable->lines();

		$cnt = count($lines);
		if ($lines[$cnt - 1] !== "?>" && ($lines[$cnt - 1] !== '' && $lines[$cnt - 2] !== "?>")) {
			$this->addViolation(array(
				'message' => $message,
				'line' => count($lines) - 1
			));
		}
	}
}

?>