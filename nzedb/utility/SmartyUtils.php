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
 * @param $x
 * @param $t
 *
 * @return bool|string
 */
function release_flag ($x, $t)
{
	$y = $d = "";

	if (preg_match('/\bCzech\b/i', $x)) {
		$y = "cz";
		$d = "Czech";
	} else if (preg_match('/Chinese|Mandarin|\bc[hn]\b/i', $x)) {
		$y = "cn";
		$d = "Chinese";
	} else if (preg_match('/German(bed)?|\bger\b/i', $x)) {
		$y = "de";
		$d = "German";
	} else if (preg_match('/Danish/i', $x)) {
		$y = "dk";
		$d = "Danish";
	} else if (preg_match('/English|\beng?\b/i', $x)) {
		$y = "en";
		$d = "English";
	} else if (preg_match('/Spanish/i', $x)) {
		$y = "es";
		$d = "Spanish";
	} else if (preg_match('/Finnish/i', $x)) {
		$y = "fi";
		$d = "Finnish";
	} else if (preg_match('/French|Vostfr/i', $x)) {
		$y = "fr";
		$d = "French";
	} else if (preg_match('/\bGreek\b/i', $x)) {
		$y = "gr";
		$d = "Greek";
	} else if (preg_match('/Hungarian|\bhun\b/i', $x)) {
		$y = "hu";
		$d = "Hungarian";
	} else if (preg_match('/Hebrew|Yiddish/i', $x)) {
		$y = "il";
		$d = "Hebrew";
	} else if (preg_match('/\bHindi\b/i', $x)) {
		$y = "in";
		$d = "Hindi";
	} else if (preg_match('/Italian|\bita\b/i', $x)) {
		$y = "it";
		$d = "Italian";
	} else if (preg_match('/Japanese|\bjp\b/i', $x)) {
		$y = "jp";
		$d = "Japanese";
	} else if (preg_match('/Korean|\bkr\b/i', $x)) {
		$y = "kr";
		$d = "Korean";
	} else if (preg_match('/Flemish|\b(Dutch|nl)\b|NlSub/i', $x)) {
		$y = "nl";
		$d = "Dutch";
	} else if (preg_match('/Norwegian/i', $x)) {
		$y = "no";
		$d = "Norwegian";
	} else if (preg_match('/Tagalog|Filipino/i', $x)) {
		$y = "ph";
		$d = "Tagalog|Filipino";
	} else if (preg_match('/Arabic/i', $x)) {
		$y = "pk";
		$d = "Arabic";
	} else if (preg_match('/Polish/i', $x)) {
		$y = "pl";
		$d = "Polish";
	} else if (preg_match('/Portugese/i', $x)) {
		$y = "pt";
		$d = "Portugese";
	} else if (preg_match('/Romanian/i', $x)) {
		$y = "ro";
		$d = "Romanian";
	} else if (preg_match('/Russian/i', $x)) {
		$y = "ru";
		$d = "Russian";
	} else if (preg_match('/Swe(dish|sub)/i', $x)) {
		$y = "se";
		$d = "Swedish";
	} else if (preg_match('/\bThai\b/i', $x)) {
		$y = "th";
		$d = "Thai";
	} else if (preg_match('/Turkish/i', $x)) {
		$y = "tr";
		$d = "Turkish";
	} else if (preg_match('/Cantonese/i', $x)) {
		$y = "tw";
		$d = "Cantonese";
	} else if (preg_match('/Vietnamese/i', $x)) {
		$y = "vn";
		$d = "Vietnamese";
	}
	if ($y !== "" && $t == "browse") {
		$www = WWW_TOP;
		if (!in_array(substr($www, -1), ['\\', '/'])) {
			$www .= DS;
		}

		return
			'<img title=' . $d . ' src="' . $www . 'themes_shared/images/flags/' . $y . '.png" />';
	} else if ($t == "search") {
		if ($y == "") {
			return false;
		} else {
			return $y;
		}
	}
	return '';
}

?>
