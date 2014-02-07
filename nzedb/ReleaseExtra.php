<?php

class ReleaseExtra
{
	function __construct()
	{
		$this->db = new DB();
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
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT * FROM releasevideo WHERE releaseid = %d', $id));
	}

	public function getVideo($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT * from releasevideo WHERE releaseid = %d', $id));
	}

	public function getAudio($id)
	{
		$db = $this->db;
		return $db->query(sprintf('SELECT * from releaseaudio WHERE releaseid = %d ORDER BY audioid ASC', $id));
	}

	public function getSubs($id)
	{
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			return $db->queryOneRow(sprintf("SELECT GROUP_CONCAT(subslanguage SEPARATOR ', ') AS subs FROM releasesubs WHERE releaseid = %d ORDER BY subsid ASC", $id));
		} else {
			return $db->queryOneRow(sprintf("SELECT STRING_AGG(subslanguage, ', ') AS subs FROM releasesubs WHERE releaseid = %d GROUP BY subsid ORDER BY subsid ASC", $id));
		}
	}

	public function getBriefByGuid($guid)
	{
		$db = $this->db;
		if ($db->dbSystem() == 'mysql') {
			return $db->queryOneRow(sprintf("SELECT containerformat, videocodec, videoduration, videoaspect, CONCAT(releasevideo.videowidth,'x',releasevideo.videoheight,' @',format(videoframerate,0),'fps') AS size, GROUP_CONCAT(DISTINCT releaseaudio.audiolanguage SEPARATOR ', ') AS audio, GROUP_CONCAT(DISTINCT releaseaudio.audioformat,' (',SUBSTRING(releaseaudio.audiochannels,1,1),' ch)' SEPARATOR ', ') AS audioformat, GROUP_CONCAT(DISTINCT releaseaudio.audioformat,' (',SUBSTRING(releaseaudio.audiochannels,1,1),' ch)' SEPARATOR ', ') AS audioformat, GROUP_CONCAT(DISTINCT releasesubs.subslanguage SEPARATOR ', ') AS subs FROM releasevideo LEFT OUTER JOIN releasesubs ON releasevideo.releaseid = releasesubs.releaseid LEFT OUTER JOIN releaseaudio ON releasevideo.releaseid = releaseaudio.releaseid INNER JOIN releases r ON r.id = releasevideo.releaseid WHERE r.guid = %s GROUP BY r.id", $db->escapeString($guid)));
		} else {
			return $db->queryOneRow(sprintf("SELECT containerformat, videocodec, videoduration, videoaspect, CONCAT(releasevideo.videowidth,'x',releasevideo.videoheight,' @',videoframerate::int,'fps') AS size, STRING_AGG(DISTINCT releaseaudio.audiolanguage, ', ') AS audio, ARRAY_TO_STRING(ARRAY_AGG(DISTINCT releaseaudio.audioformat::text), STRING_AGG(SUBSTRING(releaseaudio.audiochannels::text FROM 1 FOR 1), 'ch,'::text)::text) AS audioformat, STRING_AGG(DISTINCT releasesubs.subslanguage, ', ') AS subs FROM releasevideo LEFT OUTER JOIN releasesubs ON releasevideo.releaseid = releasesubs.releaseid LEFT OUTER JOIN releaseaudio ON releasevideo.releaseid = releaseaudio.releaseid INNER JOIN releases r ON r.id = releasevideo.releaseid WHERE r.guid = %s GROUP BY r.id, releasevideo.containerformat, releasevideo.videocodec, releasevideo.videoduration, releasevideo.videoaspect, releasevideo.videowidth, releasevideo.videoheight, releasevideo.videoframerate", $db->escapeString($guid)));
		}
	}

	public function getByGuid($guid)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT releasevideo.* FROM releasevideo INNER JOIN releases r ON r.id = releasevideo.releaseid WHERE r.guid = %s', $db->escapeString($guid)));
	}

	public function delete($id)
	{
		$db = $this->db;
		$db->queryExec(sprintf('DELETE FROM releaseaudio WHERE releaseid = %d', $id));
		$db->queryExec(sprintf('DELETE FROM releasesubs WHERE releaseid = %d', $id));
		return $db->queryExec(sprintf('DELETE FROM releasevideo WHERE releaseid = %d', $id));
	}

	public function addFromXml($releaseID, $xml)
	{
		$xmlObj = @simplexml_load_string($xml);
		$arrXml = objectsIntoArray($xmlObj);
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
						$gendata = $track;
					} else if ($track['@attributes']['type'] == 'Video') {
						$videoduration = $videoformat = $videocodec = $videowidth = $videoheight = $videoaspect = $videoframerate = $videolibrary = $gendata = $viddata = $audiodata = '';
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
						$viddata = $track;
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
						$audiodata = $track;
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
		$db = $this->db;
		$ckid = $db->queryOneRow(sprintf('SELECT releaseid FROM releasevideo WHERE releaseid = %s', $releaseID));
		if (!isset($ckid['releaseid'])) {
			return $db->queryExec(sprintf('INSERT INTO releasevideo (releaseid, containerformat, overallbitrate, videoduration, videoformat, videocodec, videowidth, videoheight, videoaspect, videoframerate, videolibrary) VALUES (%d, %s, %s, %s, %s, %s, %d, %d, %s, %d, %s)', $releaseID, $db->escapeString($containerformat), $db->escapeString($overallbitrate), $db->escapeString($videoduration), $db->escapeString($videoformat), $db->escapeString($videocodec), $videowidth, $videoheight, $db->escapeString($videoaspect), $videoframerate, $db->escapeString(substr($videolibrary, 0, 50))));
		}
	}

	public function addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode, $audiobitrate, $audiochannels, $audiosamplerate, $audiolibrary, $audiolanguage, $audiotitle)
	{
		$db = $this->db;
		$ckid = $db->queryOneRow(sprintf('SELECT releaseid FROM releaseaudio WHERE releaseid = %s', $releaseID));
		if (!isset($ckid['releaseid'])) {
			return $db->queryExec(sprintf('INSERT INTO releaseaudio (releaseid, audioid, audioformat, audiomode, audiobitratemode, audiobitrate, audiochannels, audiosamplerate, audiolibrary ,audiolanguage, audiotitle) VALUES (%d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s)', $releaseID, $audioID, $db->escapeString($audioformat), $db->escapeString($audiomode), $db->escapeString($audiobitratemode), $db->escapeString(substr($audiobitrate, 0, 10)), $db->escapeString($audiochannels), $db->escapeString(substr($audiosamplerate, 0, 25)), $db->escapeString(substr($audiolibrary, 0, 50)), $db->escapeString($audiolanguage), $db->escapeString(substr($audiotitle, 0, 50))));
		}
	}

	public function addSubs($releaseID, $subsID, $subslanguage)
	{
		$db = $this->db;
		$ckid = $db->queryOneRow(sprintf('SELECT releaseid FROM releasesubs WHERE releaseid = %s', $releaseID));
		if (!isset($ckid['releaseid'])) {
			return $db->queryExec(sprintf('INSERT INTO releasesubs (releaseid, subsid, subslanguage) VALUES (%d, %d, %s)', $releaseID, $subsID, $db->escapeString($subslanguage)));
		}
	}

	public function getFull($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf('SELECT * FROM releaseextrafull WHERE releaseid = %d', $id));
	}

	public function deleteFull($id)
	{
		$db = $this->db;
		return $db->queryExec(sprintf('DELETE FROM releaseextrafull WHERE releaseid = %d', $id));
	}

	public function addFull($id, $xml)
	{
		$db = $this->db;
		$ckid = $db->queryOneRow(sprintf('SELECT releaseid FROM releaseextrafull WHERE releaseid = %s', $id));
		if (!isset($ckid['releaseid'])) {
			return $db->queryExec(sprintf('INSERT INTO releaseextrafull (releaseid, mediainfo) VALUES (%d, %s)', $id, $db->escapeString($xml)));
		}
	}
}
?>
