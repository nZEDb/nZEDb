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
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2014 nZEDb
 */

use nzedb\Category;

/**
 * Returns the value of the specified Category constant.
 *
 * @param string $category Name of constant whose value to return.
 *
 * @return Value of the specified Category constant.
 */
function getCategoryValue($category)
{
	return Category::getCategoryValue($category);
}

// Function inspired by c0r3@newznabforums adds country flags on the browse page.
/**
 * @param string $text	Text to match against.
 * @param string $page	Type of page. browse or search.
 *
 * @return string|false
 */
function release_flag($text, $page)
{
	$code = $language = "";

	switch (true) {
		case preg_match('/Arabic/i', $text):
			$code = "pk";
			$language = "Arabic";
			break;
		case preg_match('/Cantonese/i', $text):
			$code = "tw";
			$language = "Cantonese";
			break;
		case preg_match('/Chinese|Mandarin|\bc[hn]\b/i', $text):
			$code = "cn";
			$language = "Chinese";
			break;
		case preg_match('/\bCzech\b/i', $text):
			$code = "cz";
			$language = "Czech";
			break;
		case preg_match('/Danish/i', $text):
			$code = "dk";
			$language = "Danish";
			break;
		case preg_match('/Finnish/i', $text):
			$code = "fi";
			$language = "Finnish";
			break;
		case preg_match('/Flemish|\b(Dutch|nl)\b|NlSub/i', $text):
			$code = "nl";
			$language = "Dutch";
			break;
		case preg_match('/French|Vostfr|Multi/i', $text):
			$code = "fr";
			$language = "French";
			break;
		case preg_match('/German(bed)?|\bger\b/i', $text):
			$code = "de";
			$language = "German";
			break;
		case preg_match('/\bGreek\b/i', $text):
			$code = "gr";
			$language = "Greek";
			break;
		case preg_match('/Hebrew|Yiddish/i', $text):
			$code = "il";
			$language = "Hebrew";
			break;
		case preg_match('/\bHindi\b/i', $text):
			$code = "in";
			$language = "Hindi";
			break;
		case preg_match('/Hungarian|\bhun\b/i', $text):
			$code = "hu";
			$language = "Hungarian";
			break;
		case preg_match('/Italian|\bita\b/i', $text):
			$code = "it";
			$language = "Italian";
			break;
		case preg_match('/Japanese|\bjp\b/i', $text):
			$code = "jp";
			$language = "Japanese";
			break;
		case preg_match('/Korean|\bkr\b/i', $text):
			$code = "kr";
			$language = "Korean";
			break;
		case preg_match('/Norwegian/i', $text):
			$code = "no";
			$language = "Norwegian";
			break;
		case preg_match('/Polish/i', $text):
			$code = "pl";
			$language = "Polish";
			break;
		case preg_match('/Portugese/i', $text):
			$code = "pt";
			$language = "Portugese";
			break;
		case preg_match('/Romanian/i', $text):
			$code = "ro";
			$language = "Romanian";
			break;
		case preg_match('/Spanish/i', $text):
			$code = "es";
			$language = "Spanish";
			break;
		case preg_match('/Swe(dish|sub)/i', $text):
			$code = "se";
			$language = "Swedish";
			break;
		case preg_match('/Tagalog|Filipino/i', $text):
			$code = "ph";
			$language = "Tagalog|Filipino";
			break;
		case preg_match('/\bThai\b/i', $text):
			$code = "th";
			$language = "Thai";
			break;
		case preg_match('/Turkish/i', $text):
			$code = "tr";
			$language = "Turkish";
			break;
		case preg_match('/Russian/i', $text):
			$code = "ru";
			$language = "Russian";
			break;
		case preg_match('/Vietnamese/i', $text):
			$code = "vn";
			$language = "Vietnamese";
			break;
	}

	if ($code !== '' && $page == "browse") {
		$www = WWW_TOP;
		if (!in_array(substr($www, -1), ['\\', '/'])) {
			$www .= DS;
		}

		return
			'<img title="' . $language . '" alt="' . $language . '" src="' . $www . 'themes/shared/img/flags/' . $code . '.png"/>';
	} else if ($page == "search") {
		if ($code == "") {
			return false;
		} else {
			return $code;
		}
	}
	return '';
}

?>
