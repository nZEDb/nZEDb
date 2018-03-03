<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2014 nZEDb
 */
namespace app\extensions\qa\rules\syntax;

use lithium\util\Text;

class ControlStructuresHaveCorrectFormat extends \li3_quality\qa\rules\syntax\ControlStructuresHaveCorrectSpacing
{
	/**
	 * Items that help identify the correct patterns and error messages.
	 *
	 * @var array
	 */
	protected $_tokenMap = [
		T_CLASS => [
			'message' => 'Unexpected T_CLASS format. Should be: "[abstract ]class Foo[ extends bar][ implements baz]"',
			'patterns' => [
				"/^({:whitespace})(?:abstract )?class [^\s]+ (extends [\S]+ )?(implements .+ )?[^{]/",
			],
		],
		T_IF => [
			'message' => 'Unexpected T_IF format. Should be: "if (...) {" or "} else if () {"',
			'patterns' => [
				"/^{:whitespace}if {:bracket} \{/",
				"/^{:whitespace}\} elseif {:bracket} \{/",
			],
		],
		T_ELSEIF => [
			'message' => 'Unexpected T_ELSE format. Should be: "} elseif (...) {"',
			'patterns' => [
				"/^{:whitespace}\} elseif {:bracket} \{/",
			],
		],
		T_ELSE => [
			'message' => 'Unexpected T_ELSE format. Should be: "} else {"',
			'patterns' => [
				"/^{:whitespace}\} else \{/",
				"/^{:whitespace}\} elseif {:bracket} \{/",
			],
		],
		T_DO => [
			'message' => 'Unexpected T_DO format. Should be: "do {"',
			'patterns' => [
				"/^{:whitespace}do \{/",
			],
		],
		T_WHILE => [
			'message' => 'Unexpected T_WHILE format. Should be: "while (...) {" or "} while (...);',
			'patterns' => [
				"/^{:whitespace}while {:bracket} \{/",
				"/^{:whitespace}\} while {:bracket};/",
			],
		],
		T_FOR => [
			'message' => 'Unexpected T_FOR format. Should be: "for (...; ...; ...) {"',
			'patterns' => [
				"/^{:whitespace}for {:forBracket} \{/",
			],
		],
		T_FOREACH => [
			'message' => 'Unexpected T_FOREACH format. Should be: "foreach (...) {"',
			'patterns' => [
				"/^{:whitespace}foreach {:bracket} \{/",
			],
		],
		T_SWITCH => [
			'message' => 'Unexpected T_SWITCH format. Should be: "switch (...)"',
			'patterns' => [
				"/^{:whitespace}switch {:bracket}/",
			],
		],
		T_CASE => [
			'message' => 'Unexpected T_CASE format. Should be: "case ...:"',
			'patterns' => [
				"/^{:whitespace}case [^\\n]*:/",
			],
		],
		T_DEFAULT => [
			'message' => 'Unexpected T_SWITCH format. Should be: "default:"',
			'patterns' => [
				"/^{:whitespace}default:/",
			],
		],
	];

	/**
	 * Reusable expressions to make code easier to read and reusable
	 *
	 * @var array
	 */
	protected $_regexMap = [
		'whitespace' => '(\s+)?',
		'bracket'    => '\(([^ ].*[^ ]|[^ ]+)\)',
		'forBracket' => '\((?:(?:[^ ](?:[^;]+)?; )+)?(?:[^ ]([^;]+)?)?[^ ]\)',
	];

	/**
	 * Will iterate the given tokens finding them based on the keys of self::$_tokenMap.
	 * Upon finding the matching tokens it will attempt to match the line against a regular
	 * expression proivded in tokenMap and if none are found add a violation from the message
	 * provided in tokenMap.
	 *
	 * @param  Testable $testable The testable object
	 * @return void
	 */
	public function apply($testable, array $config = [])
	{
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
				$this->addViolation([
					'message' => $this->_tokenMap[$token['id']]['message'],
					'line' => $token['line'],
				]);
			} elseif (!$singleLine) {
				$this->addWarning([
					'message' => $this->_tokenMap[$token['id']]['message'] . ' on a signle line.',
					'line' => $token['line'],
				]);
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
	protected function _extractContent($tokenId, $tokens)
	{
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
	protected function _controlContent($tokenId, $tokens, $root = true)
	{
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
	protected function _matchPattern($patterns, $body)
	{
		foreach ($patterns as $pattern) {
			if (preg_match(Text::insert($pattern, $this->_regexMap), $body) === 1) {
				return true;
			}
		}
		return false;
	}

}

?>
