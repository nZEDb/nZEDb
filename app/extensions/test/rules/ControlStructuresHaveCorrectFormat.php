<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace app\extensions\test\rules;

use lithium\util\String;

class ControlStructuresHaveCorrectFormat extends \li3_quality\test\Rule
{
	/**
	 * Items that help identify the correct patterns and error messages.
	 *
	 * @var array
	 */
	protected $_tokenMap = array(
		T_CLASS => array(
			'message' => 'Unexpected T_CLASS format. Should be: "[abstract ]class Foo[ extends bar][ implements baz]"',
			'patterns' => array(
				"/^({:whitespace})(?:abstract )?class [^\s]+ (extends [\S]+ )?(implements .+ )?[^{]/",
			),
		),
		T_IF => array(
			'message' => 'Unexpected T_IF format. Should be: "if (...) {" or "} else if () {"',
			'patterns' => array(
				"/^{:whitespace}if {:bracket} \{/",
				"/^{:whitespace}\} elseif {:bracket} \{/",
			),
		),
		T_ELSEIF => array(
			'message' => 'Unexpected T_ELSE format. Should be: "} elseif (...) {"',
			'patterns' => array(
				"/^{:whitespace}\} elseif {:bracket} \{/",
			),
		),
		T_ELSE => array(
			'message' => 'Unexpected T_ELSE format. Should be: "} else {"',
			'patterns' => array(
				"/^{:whitespace}\} else \{/",
				"/^{:whitespace}\} elseif {:bracket} \{/",
			),
		),
		T_DO => array(
			'message' => 'Unexpected T_DO format. Should be: "do {"',
			'patterns' => array(
				"/^{:whitespace}do \{/",
			),
		),
		T_WHILE => array(
			'message' => 'Unexpected T_WHILE format. Should be: "while (...) {" or "} while (...);',
			'patterns' => array(
				"/^{:whitespace}while {:bracket} \{/",
				"/^{:whitespace}\} while {:bracket};/",
			),
		),
		T_FOR => array(
			'message' => 'Unexpected T_FOR format. Should be: "for (...; ...; ...) {"',
			'patterns' => array(
				"/^{:whitespace}for {:forBracket} \{/",
			),
		),
		T_FOREACH => array(
			'message' => 'Unexpected T_FOREACH format. Should be: "foreach (...) {"',
			'patterns' => array(
				"/^{:whitespace}foreach {:bracket} \{/",
			),
		),
		T_SWITCH => array(
			'message' => 'Unexpected T_SWITCH format. Should be: "switch (...)"',
			'patterns' => array(
				"/^{:whitespace}switch {:bracket}/",
			),
		),
		T_CASE => array(
			'message' => 'Unexpected T_CASE format. Should be: "case ...:"',
			'patterns' => array(
				"/^{:whitespace}case [^\\n]*:/",
			),
		),
		T_DEFAULT => array(
			'message' => 'Unexpected T_SWITCH format. Should be: "default:"',
			'patterns' => array(
				"/^{:whitespace}default:/",
			),
		),
	);

	/**
	 * Reusable expressions to make code easier to read and reusable
	 *
	 * @var array
	 */
	protected $_regexMap = array(
		'whitespace' => '(\s+)?',
		'bracket'    => '\(([^ ].*[^ ]|[^ ]+)\)',
		'forBracket' => '\((?:(?:[^ ](?:[^;]+)?; )+)?(?:[^ ]([^;]+)?)?[^ ]\)',
	);

	/**
	 * Will iterate the given tokens finding them based on the keys of self::$_tokenMap.
	 * Upon finding the matching tokens it will attempt to match the line against a regular
	 * expression proivded in tokenMap and if none are found add a violation from the message
	 * provided in tokenMap.
	 *
	 * @param  Testable $testable The testable object
	 * @return void
	 */
	public function apply($testable, array $config = array()) {
		$lines = $testable->lines();
		$tokens = $testable->tokens();
		$filtered = $testable->findAll(array_keys($this->_tokenMap));

		foreach ($filtered as $tokenId) {
			$token = $tokens[$tokenId];
			$tokenMap = $this->_tokenMap[$token['id']];
			$patterns = $tokenMap['patterns'];

			$body = $this->_extractContent($tokenId, $tokens);
			$singleLine = $this->_matchPattern($patterns, $body);
			$multiLine = false;
			if (!$singleLine) {
				foreach ($patterns as $key => $value) {
					$patterns[$key] .= 's';
				}
				$multiLine = $this->_matchPattern($patterns, $body);
			}
			if (!$singleLine && !$multiLine) {
				$this->addViolation(array(
					'message' => $this->_tokenMap[$token['id']]['message'],
					'line' => $token['line'],
				));
			} elseif (!$singleLine) {
				$this->addWarning(array(
					'message' => $this->_tokenMap[$token['id']]['message'] . ' on a signle line.',
					'line' => $token['line'],
				));
			}
		}
	}

	/**
	 * Extract the Control content and its prefix
	 *
	 * @param  array  $tokenId The id of the token.
	 * @param  array  $tokens  The tokens from $testable->tokens()
	 * @return string          The extracted content + prefix
	 */
	protected function _extractContent($tokenId, $tokens) {
		$token = $tokens[$tokenId];
		$body = $this->_controlContent($tokenId, $tokens);
		$line = $token['line'];
		while (--$tokenId >= 0 && $tokens[$tokenId]['line'] === $line) {
			$body = $tokens[$tokenId]['content'] . $body;
		}
		return $body;
	}

	/**
	 * Extract the control content
	 *
	 * @param  array  $tokenId The id of the token.
	 * @param  array  $tokens  The tokens from $testable->tokens()
	 * @return string          The extracted content
	 */
	protected function _controlContent($tokenId, $tokens, $root = true) {
		$token = $tokens[$tokenId];
		$body = $token['content'];

		foreach ($token['children'] as $childrenId) {
			if (!$tokens[$childrenId]['children']) {
				$body .= $tokens[$childrenId]['content'];
			} elseif ($root && $tokens[$childrenId]['content'] === '{') {
				$body .= $tokens[$childrenId]['content'];
				break;
			} else {
				$body .= $this->_controlContent($childrenId, $tokens, false);
			}
		}
		return $body;
	}

	/**
	 * Abstracts the matching out. Will return true if any of the patterns match correctly.
	 *
	 * @param  array  $patterns The patterns to match overs.
	 * @param  string $body     The string body.
	 * @return bool
	 */
	protected function _matchPattern($patterns, $body) {
		foreach ($patterns as $pattern) {
			if (preg_match(String::insert($pattern, $this->_regexMap), $body) === 1) {
				return true;
			}
		}
		return false;
	}

}

?>