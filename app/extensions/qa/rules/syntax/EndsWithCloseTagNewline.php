<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\qa\rules\syntax;

class EndsWithCloseTagNewline extends \li3_quality\qa\Rule
{
	public function apply($testable, array $config = [])
	{
		$message = "File does not end with ?>";
		$lines   = $testable->lines();

		$cnt = count($lines);
		if ($cnt > 1) {
			if ($lines[($cnt - 1)] !== "?>" &&
				($lines[($cnt - 1)] !== '' && $lines[($cnt - 2)] !== "?>")
			) {
				$this->addViolation(
					[
						'message' => $message,
						'line'    => $cnt - 1
					]);
			}
		}
	}
}

?>
