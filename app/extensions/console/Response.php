<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation,
		either version 3 of the License,
		or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not,
		see:
 *
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2014 nZEDb
 */
namespace app\extensions\console;


class Response extends \lithium\console\Response
{
	public $coloursBackground = [
		'black'		=> '40',
		'blue'		=> '44',
		'cyan'		=> '46',
		'green'		=> '42',
		'purple'	=> '45',
		'red'		=> '41',
		'white'		=> '47',
		'yellow'	=> '43',
	];

	public $coloursForeground = [
		'black'		=> '30',
		'blue'		=> '34',
		'cyan'		=> '36',
		'gray'		=> '37',
		'green'		=> '32',
		'grey'		=> '37',
		'purple'	=> '35',
		'red'		=> '31',
		'white'		=> '1;37',
		'yellow'	=> '33',
	];

	public $colours256 = [
		'Aquamarine'		=> '086',
		'Aquamarine1'		=> '122',
		'Aquamarine2'		=> '079',
		'Blue'				=> '012',
		'Blue1'				=> '021',
		'Blue2'				=> '019',
		'Blue3'				=> '020',
		'BlueViolet'		=> '057',
		'CadetBlue'			=> '072',
		'CadetBlue1'		=> '073',
		'Chartreuse'		=> '118',
		'Chartreuse1'		=> '082',
		'Chartreuse2'		=> '112',
		'Chartreuse3'		=> '070',
		'Chartreuse4'		=> '076',
		'Chartreuse5'		=> '064',
		'CornflowerBlue'	=> '069',
		'Cornsilk'			=> '230',
		'Cyan'				=> '014',
		'Cyan1'				=> '051',
		'Cyan2'				=> '050',
		'Cyan3'				=> '043',
		'DarkBlue'  		=> '018',
		'DarkCyan'			=> '036',
		'DarkGoldenrod'		=> '136',
		'DarkGreen'			=> '022',
		'DarkKhaki'			=> '143',
		'DarkMagenta' 		=> '090',
		'DarkMagenta1'		=> '091',
		'DarkOliveGreen'	=> '191',
		'DarkOliveGreen1'	=> '192',
		'DarkOliveGreen2'	=> '155',
		'DarkOliveGreen3'	=> '107',
		'DarkOliveGreen4'	=> '113',
		'DarkOliveGreen5'	=> '149',
		'DeepPink'			=> '198',
		'DeepPink1'			=> '199',
		'DeepPink2'			=> '197',
		'DarkOrange'		=> '208',
		'DarkOrange1'		=> '130',
		'DarkOrange2'		=> '166',
		'DeepPink3'			=> '161',
		'DeepPink4'			=> '162',
		'DeepPink7'			=> '125',
		'DarkRed'			=> '052',
		'DarkSeaGreen'		=> '108',
		'DarkSeaGreen1'		=> '158',
		'DarkSeaGreen2'		=> '193',
		'DarkSeaGreen3'		=> '151',
		'DarkSeaGreen4'		=> '157',
		'DarkSeaGreen5'		=> '115',
		'DarkSeaGreen6'		=> '150',
		'DarkSeaGreen7'		=> '065',
		'DarkSeaGreen8'		=> '071',
		'DarkSlateGray'		=> '123',
		'DarkTurquoise'		=> '044',
		'DarkRed1'			=> '088',
		'DarkSlateGray1'	=> '087',
		'DarkSlateGray2'	=> '116',
		'DarkViolet'		=> '092',
		'DarkViolet1'		=> '128',
		'DeepPink5'			=> '053',
		'DeepPink6'			=> '089',
		'DeepSkyBlue'		=> '039',
		'DeepSkyBlue1'		=> '038',
		'DeepSkyBlue2'		=> '031',
		'DeepSkyBlue3'		=> '032',
		'DeepSkyBlue5'		=> '023',
		'DeepSkyBlue6'		=> '024',
		'DeepSkyBlue7'		=> '025',
		'DodgerBlue'		=> '033',
		'DodgerBlue1'		=> '027',
		'DodgerBlue2'		=> '026',
		'Gold'				=> '220',
		'Gold1'				=> '142',
		'Gold2'				=> '178',
		'Gray'          	=> '008',
		'Green'				=> '010',
		'Green1'			=> '046',
		'Green2'			=> '034',
		'Green3'			=> '040',
		'Green4'			=> '028',
		'GreenYellow'		=> '154',
		'Grey1'				=> '016',
		'Grey2'				=> '059',
		'Grey3'				=> '102',
		'Grey4'				=> '139',
		'Grey5'				=> '145',
		'Grey6'				=> '188',
		'Grey7'				=> '231',
		'Grey8'				=> '232',
		'Grey9'				=> '233',
		'Grey10'			=> '234',
		'Grey11'			=> '235',
		'Grey12'			=> '236',
		'Grey13'			=> '237',
		'Grey14'			=> '238',
		'Grey15'			=> '239',
		'Grey16'			=> '240',
		'Grey17'			=> '241',
		'Grey18'			=> '242',
		'Grey19'			=> '243',
		'Grey20'			=> '244',
		'Grey21'			=> '245',
		'Grey22'			=> '246',
		'Grey23'			=> '247',
		'Grey24'			=> '248',
		'Grey25'			=> '249',
		'Grey26'			=> '250',
		'Grey27'			=> '251',
		'Grey28'			=> '252',
		'Grey29'			=> '253',
		'Grey30'			=> '254',
		'Grey31'			=> '255',
		'Honeydew'			=> '194',
		'HotPink'			=> '205',
		'HotPink1'			=> '206',
		'HotPink2'			=> '169',
		'HotPink3'			=> '132',
		'HotPink4'			=> '168',
		'IndianRed'			=> '131',
		'IndianRed1'		=> '167',
		'IndianRed2'		=> '203',
		'IndianRed3'		=> '204',
		'Khaki'				=> '228',
		'Khaki1'			=> '185',
		'LightCoral'		=> '210',
		'LightCyan1'		=> '195',
		'LightCyan2'		=> '152',
		'LightGoldenrod'	=> '227',
		'LightGoldenrod1'	=> '186',
		'LightGoldenrod2'	=> '221',
		'LightGoldenrod3'	=> '222',
		'LightGoldenrod4'	=> '179',
		'LightGreen'		=> '119',
		'LightGreen1'		=> '120',
		'LightPink'			=> '217',
		'LightPink1'		=> '174',
		'LightPink2'		=> '095',
		'LightSalmon'		=> '216',
		'LightSalmon1'		=> '137',
		'LightSalmon2'		=> '173',
		'LightSeaGreen'		=> '037',
		'LightSkyBlue'		=> '153',
		'LightSkyBlue1'		=> '109',
		'LightSkyBlue2'		=> '110',
		'LightSlateBlue'	=> '105',
		'LightSteelBlue'	=> '147',
		'LightSteelBlue1'	=> '189',
		'LightSteelBlue2'	=> '146',
		'LightSlateGrey'	=> '103',
		'LightYellow'		=> '187',
		'Magenta1'			=> '201',
		'Magenta2'			=> '165',
		'Magenta3'			=> '200',
		'Magenta4'			=> '127',
		'Magenta5'			=> '163',
		'Magenta6'			=> '164',
		'MediumOrchid'		=> '134',
		'MediumOrchid1'		=> '171',
		'MediumOrchid2'		=> '207',
		'MediumOrchid3'		=> '133',
		'MediumPurple'		=> '104',
		'MediumPurple1'		=> '141',
		'MediumPurple2'		=> '135',
		'MediumPurple3'		=> '140',
		'MediumPurple4'		=> '097',
		'MediumPurple5'		=> '098',
		'MediumPurple6'		=> '060',
		'MediumSpringGreen'	=> '049',
		'MediumTurquoise'	=> '080',
		'MediumVioletRed'	=> '126',
		'MistyRose'			=> '224',
		'MistyRose1'		=> '181',
		'NavajoWhite'		=> '223',
		'NavajoWhite1'		=> '144',
		'NavyBlue'			=> '017',
		'Orange'			=> '214',
		'Orange1'			=> '172',
		'Orange2'			=> '058',
		'Orange3'			=> '094',
		'OrangeRed'			=> '202',
		'Orchid'			=> '170',
		'Orchid1'			=> '213',
		'Orchid2'			=> '212',
		'PaleGreen'			=> '121',
		'PaleGreen1'		=> '156',
		'PaleGreen2'		=> '077',
		'PaleGreen3'		=> '114',
		'PaleTurquoise'		=> '159',
		'PaleTurquoise1'	=> '066',
		'PaleVioletRed'		=> '211',
		'Pink'				=> '218',
		'Pink1'				=> '175',
		'Plum'				=> '219',
		'Plum1'				=> '183',
		'Plum2'				=> '176',
		'Plum3'				=> '096',
		'Purple'			=> '013',
		'Purple1'			=> '093',
		'Purple2'			=> '129',
		'Purple3'			=> '056',
		'Purple4'			=> '054',
		'Purple5'			=> '055',
		'Red'				=> '009',
		'Red1'				=> '196',
		'Red2'				=> '124',
		'Red3'				=> '160',
		'RoyalBlue1'		=> '063',
		'RosyBrown'			=> '138',
		'Salmon'    		=> '209',
		'SandyBrown'		=> '215',
		'SeaGreen'			=> '084',
		'SeaGreen1'			=> '085',
		'SeaGreen2'			=> '083',
		'SeaGreen3'			=> '078',
		'SkyBlue'			=> '117',
		'SkyBlue1'			=> '111',
		'SkyBlue2'			=> '074',
		'SlateBlue'			=> '099',
		'SlateBlue1'		=> '061',
		'SlateBlue2'		=> '062',
		'SpringGreen'		=> '048',
		'SpringGreen1'		=> '042',
		'SpringGreen2'		=> '047',
		'SpringGreen3'		=> '035',
		'SpringGreen4'		=> '041',
		'SpringGreen5'		=> '029',
		'SteelBlue'			=> '067',
		'SteelBlue1'		=> '075',
		'SteelBlue2'		=> '081',
		'SteelBlue3'		=> '068',
		'Tan'				=> '180',
		'Thistle'			=> '225',
		'Thistle1'			=> '182',
		'Turquoise'			=> '045',
		'Turquoise1'		=> '030',
		'Violet'			=> '177',
		'Wheat'				=> '229',
		'Wheat1'			=> '101',
		'White'				=> '015',
		'Yellow'			=> '011',
		'Yellow1'			=> '226',
		'Yellow2'			=> '190',
		'Yellow3'			=> '148',
		'Yellow4'			=> '184',
		'Yellow5'			=> '100',
		'Yellow6'			=> '106',
	];

	private $styles = [
		'blink'			=> "\033[5m",
		'bright'		=> "\033[1m",
		'command'		=> "\033[0;35m",
		'dim'			=> "\033[2m",
		'gray'			=> "\033[37m",
		'grey'			=> "\033[37m",
		'hidden'		=> "\033[8m",
		'info'			=> "\033[35m",	// replace with updated version of:
		// "\033[" . self::coloursForeground['purple'] . "mInfo: $text\033[0m"
		// when we switch to PHP 5.6
		'normal'		=> "\033[0m",
		'primary'		=> "\033[38;5;010m",
		'reset'			=> "\033[0m",
		'reverse'		=> "\033[7m",
		'strikethrough' => "\033[9m",
		'underscore'    => "\033[4m",
	];

	public function __construct(array $options = array())
	{
		parent::__construct($options);
	}

	public function __destruct()
	{
		parent::__destruct();
	}

	public function clearStyle($style)
	{
		if ($this->getStyle($style) !== null) {
			unset(self::$styles[$style]);
		}
		return !($this->getStyle($style) === null);
	}

	public function getColourCode($fgColour, $bgColour = null)
	{
		$bgColour = empty($bg) && isset(self::$coloursBackground[$bgColour]) ?
			";48;5;" . self::$colours256[$bgColour] : '';
		return "\033[38;5;" . self::$colours256[$fgColour] . $bgColour . "m";
	}

	public function getStyle($style)
	{
		return isset(self::$styles[$style]) ? self::$styles[$style] : null;
	}

	public function setStyle($style, $value)
	{
		self::$styles[$style] = $value;
		return ($this->getStyle($style) == $value);
	}

	public function styles($styles = [])
	{
		return parent::styles($styles + $this->styles);
	}

	protected function _encode($code)
	{
		return "\033[" . $code . "m";
	}
}
