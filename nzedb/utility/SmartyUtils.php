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

// Function inspired by c0r3@newznabforums adds country flags on the browse page.
/**
 * @param string $text	Text to match against.
 * @param string $page	Type of page. browse or search.
 *
*@return bool|string
 */
function release_flag($text, $page)
{
	$code = $language = "";

	if (preg_match('/\bCzech\b/i', $text)) {
		$code = "cz";
		$language = "Czech";
	} else if (preg_match('/Chinese|Mandarin|\bc[hn]\b/i', $text)) {
		$code = "cn";
		$language = "Chinese";
	} else if (preg_match('/German(bed)?|\bger\b/i', $text)) {
		$code = "de";
		$language = "German";
	} else if (preg_match('/Danish/i', $text)) {
		$code = "dk";
		$language = "Danish";
	} else if (preg_match('/English|\beng?\b/i', $text)) {
		$code = "en";
		$language = "English";
	} else if (preg_match('/Spanish/i', $text)) {
		$code = "es";
		$language = "Spanish";
	} else if (preg_match('/Finnish/i', $text)) {
		$code = "fi";
		$language = "Finnish";
	} else if (preg_match('/French|Vostfr|Multi/i', $text)) {
		$code = "fr";
		$language = "French";
	} else if (preg_match('/\bGreek\b/i', $text)) {
		$code = "gr";
		$language = "Greek";
	} else if (preg_match('/Hungarian|\bhun\b/i', $text)) {
		$code = "hu";
		$language = "Hungarian";
	} else if (preg_match('/Hebrew|Yiddish/i', $text)) {
		$code = "il";
		$language = "Hebrew";
	} else if (preg_match('/\bHindi\b/i', $text)) {
		$code = "in";
		$language = "Hindi";
	} else if (preg_match('/Italian|\bita\b/i', $text)) {
		$code = "it";
		$language = "Italian";
	} else if (preg_match('/Japanese|\bjp\b/i', $text)) {
		$code = "jp";
		$language = "Japanese";
	} else if (preg_match('/Korean|\bkr\b/i', $text)) {
		$code = "kr";
		$language = "Korean";
	} else if (preg_match('/Flemish|\b(Dutch|nl)\b|NlSub/i', $text)) {
		$code = "nl";
		$language = "Dutch";
	} else if (preg_match('/Norwegian/i', $text)) {
		$code = "no";
		$language = "Norwegian";
	} else if (preg_match('/Tagalog|Filipino/i', $text)) {
		$code = "ph";
		$language = "Tagalog|Filipino";
	} else if (preg_match('/Arabic/i', $text)) {
		$code = "pk";
		$language = "Arabic";
	} else if (preg_match('/Polish/i', $text)) {
		$code = "pl";
		$language = "Polish";
	} else if (preg_match('/Portugese/i', $text)) {
		$code = "pt";
		$language = "Portugese";
	} else if (preg_match('/Romanian/i', $text)) {
		$code = "ro";
		$language = "Romanian";
	} else if (preg_match('/Russian/i', $text)) {
		$code = "ru";
		$language = "Russian";
	} else if (preg_match('/Swe(dish|sub)/i', $text)) {
		$code = "se";
		$language = "Swedish";
	} else if (preg_match('/\bThai\b/i', $text)) {
		$code = "th";
		$language = "Thai";
	} else if (preg_match('/Turkish/i', $text)) {
		$code = "tr";
		$language = "Turkish";
	} else if (preg_match('/Cantonese/i', $text)) {
		$code = "tw";
		$language = "Cantonese";
	} else if (preg_match('/Vietnamese/i', $text)) {
		$code = "vn";
		$language = "Vietnamese";
	}

	if ($code !== "" && $page == "browse") {
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
