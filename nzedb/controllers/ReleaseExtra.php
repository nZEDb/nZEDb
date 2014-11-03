<?php

use nzedb\db\Settings;
use nzedb\utility;

class ReleaseExtra
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @param nzedb\db\Settings $settings
	 */
	public function __construct($settings = null)
	{
		$this->pdo = ($settings instanceof Settings ? $settings : new Settings());
	}

	public function makeCodecPretty($codec)
	{
		if (preg_match('/DX50|DIVX|DIV3/i', $codec)) {
			return 'DivX';
		}
		if (preg_match('/XVID/i', $codec)) {
			return 'XviD';
		}
		if (preg_match('/^27$/i', $codec)) {
			return 'Blu-Ray';
		}
		if (preg_match('/V_MPEG4\/ISO\/AVC/i', $codec)) {
			return 'x264';
		}
		if (preg_match('/wmv|WVC1/i', $codec)) {
			return 'wmv';
		}
		if (preg_match('/^2$/i', $codec)) {
			return 'HD.ts';
		}
		if (preg_match('/avc1/i', $codec)) {
			return 'h.264';
		}
		if (preg_match('/DX50|DIVX|DIV3/i', $codec)) {
			return 'DivX';
		}
		return $codec;
	}

	public function get($id)
	{
		// hopefully nothing will use this soon and it can be deleted
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM video_data WHERE releaseid = %d', $id));
	}

	public function getVideo($id)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * from video_data WHERE releaseid = %d', $id));
	}

	public function getAudio($id)
	{
		return $this->pdo->query(sprintf('SELECT * from audio_data WHERE releaseid = %d ORDER BY audioid ASC', $id));
	}

	public function getSubs($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT GROUP_CONCAT(subslanguage SEPARATOR ', ') AS subs FROM release_subtitles WHERE releaseid = %d ORDER BY subsid ASC", $id));
	}

	public function getBriefByGuid($guid)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT containerformat, videocodec, videoduration, videoaspect, CONCAT(video_data.videowidth,'x',video_data.videoheight,' @',format(videoframerate,0),'fps') AS size, GROUP_CONCAT(DISTINCT audio_data.audiolanguage SEPARATOR ', ') AS audio, GROUP_CONCAT(DISTINCT audio_data.audioformat,' (',SUBSTRING(audio_data.audiochannels,1,1),' ch)' SEPARATOR ', ') AS audioformat, GROUP_CONCAT(DISTINCT audio_data.audioformat,' (',SUBSTRING(audio_data.audiochannels,1,1),' ch)' SEPARATOR ', ') AS audioformat, GROUP_CONCAT(DISTINCT release_subtitles.subslanguage SEPARATOR ', ') AS subs FROM video_data LEFT OUTER JOIN release_subtitles ON video_data.releaseid = release_subtitles.releaseid LEFT OUTER JOIN audio_data ON video_data.releaseid = audio_data.releaseid INNER JOIN releases r ON r.id = video_data.releaseid WHERE r.guid = %s GROUP BY r.id", $this->pdo->escapeString($guid)));
	}

	public function getByGuid($guid)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT video_data.* FROM video_data INNER JOIN releases r ON r.id = video_data.releaseid WHERE r.guid = %s', $this->pdo->escapeString($guid)));
	}

	public function delete($id)
	{
		$this->pdo->queryExec(sprintf('DELETE FROM audio_data WHERE releaseid = %d', $id));
		$this->pdo->queryExec(sprintf('DELETE FROM release_subtitles WHERE releaseid = %d', $id));
		return $this->pdo->queryExec(sprintf('DELETE FROM video_data WHERE releaseid = %d', $id));
	}

	public function addFromXml($releaseID, $xml)
	{
		$xmlObj = @simplexml_load_string($xml);
		$arrXml = nzedb\utility\Utility::objectsIntoArray($xmlObj);
		$containerformat = '';
		$overallbitrate = '';

		if (isset($arrXml['File']) && isset($arrXml['File']['track'])) {
			foreach ($arrXml['File']['track'] as $track) {
				if (isset($track['@attributes']) && isset($track['@attributes']['type'])) {


					if ($track['@attributes']['type'] == 'General') {
						if (isset($track['Format'])) {
							$containerformat = $track['Format'];
						}
						if (isset($track['Overall_bit_rate'])) {
							$overallbitrate = $track['Overall_bit_rate'];
						}
					} else if ($track['@attributes']['type'] == 'Video') {
						$videoduration = $videoformat = $videocodec = $videowidth = $videoheight = $videoaspect = $videoframerate = $videolibrary = '';
						if (isset($track['Duration'])) {
							$videoduration = $track['Duration'];
						}
						if (isset($track['Format'])) {
							$videoformat = $track['Format'];
						}
						if (isset($track['Codec_ID'])) {
							$videocodec = $track['Codec_ID'];
						}
						if (isset($track['Width'])) {
							$videowidth = preg_replace('/[^0-9]/', '', $track['Width']);
						}
						if (isset($track['Height'])) {
							$videoheight = preg_replace('/[^0-9]/', '', $track['Height']);
						}
						if (isset($track['Display_aspect_ratio'])) {
							$videoaspect = $track['Display_aspect_ratio'];
						}
						if (isset($track['Frame_rate'])) {
							$videoframerate = str_replace(' fps', '', $track['Frame_rate']);
						}
						if (isset($track['Writing_library'])) {
							$videolibrary = $track['Writing_library'];
						}
						$this->addVideo($releaseID, $containerformat, $overallbitrate, $videoduration, $videoformat, $videocodec, $videowidth, $videoheight, $videoaspect, $videoframerate, $videolibrary);
					} else if ($track['@attributes']['type'] == 'Audio') {
						$audioID = 1;
						$audioformat = $audiomode = $audiobitratemode = $audiobitrate = $audiochannels = $audiosamplerate = $audiolibrary = $audiolanguage = $audiotitle = '';
						if (isset($track['@attributes']['streamid'])) {
							$audioID = $track['@attributes']['streamid'];
						}
						if (isset($track['Format'])) {
							$audioformat = $track['Format'];
						}
						if (isset($track['Mode'])) {
							$audiomode = $track['Mode'];
						}
						if (isset($track['Bit_rate_mode'])) {
							$audiobitratemode = $track['Bit_rate_mode'];
						}
						if (isset($track['Bit_rate'])) {
							$audiobitrate = $track['Bit_rate'];
						}
						if (isset($track['Channel_s_'])) {
							$audiochannels = $track['Channel_s_'];
						}
						if (isset($track['Sampling_rate'])) {
							$audiosamplerate = $track['Sampling_rate'];
						}
						if (isset($track['Writing_library'])) {
							$audiolibrary = $track['Writing_library'];
						}
						if (isset($track['Language'])) {
							$audiolanguage = $track['Language'];
						}
						if (isset($track['Title'])) {
							$audiotitle = $track['Title'];
						}
						$this->addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode, $audiobitrate, $audiochannels, $audiosamplerate, $audiolibrary, $audiolanguage, $audiotitle);
					} else if ($track['@attributes']['type'] == 'Text') {
						$subsID = 1;
						$subslanguage = 'Unknown';
						if (isset($track['@attributes']['streamid'])) {
							$subsID = $track['@attributes']['streamid'];
						}
						if (isset($track['Language'])) {
							$subslanguage = $track['Language'];
						}
						$this->addSubs($releaseID, $subsID, $subslanguage);
					}
				}
			}
		}
	}

	public function addVideo($releaseID, $containerformat, $overallbitrate, $videoduration, $videoformat, $videocodec, $videowidth, $videoheight, $videoaspect, $videoframerate, $videolibrary)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releaseid FROM video_data WHERE releaseid = %s', $releaseID));
		if (!isset($ckid['releaseid'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO video_data (releaseid, containerformat, overallbitrate, videoduration, videoformat, videocodec, videowidth, videoheight, videoaspect, videoframerate, videolibrary) VALUES (%d, %s, %s, %s, %s, %s, %d, %d, %s, %d, %s)', $releaseID, $this->pdo->escapeString($containerformat), $this->pdo->escapeString($overallbitrate), $this->pdo->escapeString($videoduration), $this->pdo->escapeString($videoformat), $this->pdo->escapeString($videocodec), $videowidth, $videoheight, $this->pdo->escapeString($videoaspect), $videoframerate, $this->pdo->escapeString(substr($videolibrary, 0, 50))));
		}
	}

	public function addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode, $audiobitrate, $audiochannels, $audiosamplerate, $audiolibrary, $audiolanguage, $audiotitle)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releaseid FROM audio_data WHERE releaseid = %s', $releaseID));
		if (!isset($ckid['releaseid'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO audio_data (releaseid, audioid, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary ,audiolanguage, audiotitle) VALUES (%d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s)', $releaseID, $audioID, $this->pdo->escapeString($audioformat), $this->pdo->escapeString($audiomode), $this->pdo->escapeString($audiobitratemode), $this->pdo->escapeString(substr($audiobitrate, 0, 10)), $this->pdo->escapeString($audiochannels), $this->pdo->escapeString(substr($audiosamplerate, 0, 25)), $this->pdo->escapeString(substr($audiolibrary, 0, 50)), $this->pdo->escapeString($audiolanguage), $this->pdo->escapeString(substr($audiotitle, 0, 50))));
		}
	}

	public function addSubs($releaseID, $subsID, $subslanguage)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releaseid FROM release_subtitles WHERE releaseid = %s', $releaseID));
		if (!isset($ckid['releaseid'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO release_subtitles (releaseid, subsid, subslanguage) VALUES (%d, %d, %s)', $releaseID, $subsID, $this->pdo->escapeString($subslanguage)));
		}
	}

	public function getFull($id)
	{
		return $this->pdo->queryOneRow(sprintf('SELECT * FROM releaseextrafull WHERE releaseid = %d', $id));
	}

	public function deleteFull($id)
	{
		return $this->pdo->queryExec(sprintf('DELETE FROM releaseextrafull WHERE releaseid = %d', $id));
	}

	public function addFull($id, $xml)
	{
		$ckid = $this->pdo->queryOneRow(sprintf('SELECT releaseid FROM releaseextrafull WHERE releaseid = %s', $id));
		if (!isset($ckid['releaseid'])) {
			return $this->pdo->queryExec(sprintf('INSERT INTO releaseextrafull (releaseid, mediainfo) VALUES (%d, %s)', $id, $this->pdo->escapeString($xml)));
		}
	}
}
