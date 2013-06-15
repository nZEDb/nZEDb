<?php
require_once(WWW_DIR."/lib/framework/db.php");

class ReleaseExtra
{	
	public function makeCodecPretty($codec)
	{
		if(preg_match("/DX50|DIVX|DIV3/i",$codec))
			return "DivX";
		if(preg_match("/XVID/i",$codec))
			return "XviD";
		if(preg_match("/^27$/i",$codec))
			return "Blu-Ray";
		if(preg_match("/V_MPEG4\/ISO\/AVC/i",$codec))
			return "x264";
		if(preg_match("/wmv|WVC1/i",$codec))
			return "wmv";
		if(preg_match("/^2$/i",$codec))
			return "HD.ts";
		if(preg_match("/avc1/i",$codec))
			return "h.264";
		if(preg_match("/DX50|DIVX|DIV3/i",$codec))
			return "DivX";
		return $codec;
	}

	public function get($id)
	{
		// hopefully nothing will use this soon and it can be deleted
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from releasevideo where releaseID = %d", $id));	
	}
	public function getVideo($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from releasevideo where releaseID = %d", $id));	
	}
	public function getAudio($id)
	{
		$db = new DB();
		return $db->query(sprintf("select * from releaseaudio where releaseID = %d order by audioID ASC", $id));	
	}
	public function getSubs($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT group_concat(subslanguage SEPARATOR ', ') as subs FROM `releasesubs` WHERE `releaseID` = %d ORDER BY `subsID` ASC", $id));	
	}	
	public function getBriefByGuid($guid)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select containerformat,videocodec,videoduration,videoaspect, concat(releasevideo.videowidth,'x',releasevideo.videoheight,' @',format(videoframerate,0),'fps') as size,group_concat(distinct releaseaudio.audiolanguage SEPARATOR ', ') as audio,group_concat(distinct releasesubs.subslanguage SEPARATOR ', ') as subs from releasevideo left outer join releasesubs on releasevideo.releaseID = releasesubs.releaseID left outer join releaseaudio on releasevideo.releaseID = releaseaudio.releaseID inner join releases r on r.ID = releasevideo.releaseID where r.guid = %s group by r.ID", $db->escapeString($guid)));	
	}
	public function getByGuid($guid)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select releasevideo.* from releasevideo inner join releases r on r.ID = releasevideo.releaseID where r.guid = %s ", $db->escapeString($guid)));	
	}	
	
	public function delete($id)
	{
		$db = new DB();
		$db->query(sprintf("delete from releaseaudio where releaseID = %d", $id));
		$db->query(sprintf("delete from releasesubs where releaseID = %d", $id));
		return $db->query(sprintf("delete from releasevideo where releaseID = %d", $id));	
	}

	public function addFromXml($releaseID, $xml)
	{
		$xmlObj = @simplexml_load_string($xml);
		$arrXml = objectsIntoArray($xmlObj);
		$containerformat = ""; $overallbitrate = "";

		if (isset($arrXml["File"]) && isset($arrXml["File"]["track"]))
		{
			foreach ($arrXml["File"]["track"] as $track)
			{
				if (isset($track["@attributes"]) && isset($track["@attributes"]["type"]))
				{
					

					if ($track["@attributes"]["type"] == "General")
					{
						if (isset($track["Format"]))
							$containerformat = $track["Format"];
						if (isset($track["Overall_bit_rate"]))
							$overallbitrate = $track["Overall_bit_rate"];
						$gendata = $track;
					}
					elseif ($track["@attributes"]["type"] == "Video")
					{
						$videoduration = ""; $videoformat = ""; $videocodec = ""; $videowidth = ""; $videoheight = ""; $videoaspect = ""; $videoframerate = ""; $videolibrary =	"";	 		$gendata = "";  $viddata = "";  $audiodata = "";
						if (isset($track["Duration"]))
							$videoduration = $track["Duration"];
						if (isset($track["Format"]))
							$videoformat = $track["Format"];
						if (isset($track["Codec_ID"]))
							$videocodec = $track["Codec_ID"];
						if (isset($track["Width"]))
							$videowidth = preg_replace("/[^0-9]/", '', $track["Width"]);
						if (isset($track["Height"]))
							$videoheight = preg_replace("/[^0-9]/", '', $track["Height"]);
						if (isset($track["Display_aspect_ratio"]))
							$videoaspect = $track["Display_aspect_ratio"];
						if (isset($track["Frame_rate"]))
							$videoframerate = str_replace(" fps", "", $track["Frame_rate"]);
						if (isset($track["Writing_library"]))
							$videolibrary = $track["Writing_library"];
						$viddata = $track;
						$this->addVideo($releaseID, $containerformat, $overallbitrate, $videoduration,
											$videoformat, $videocodec, $videowidth,	$videoheight,
											$videoaspect, $videoframerate, 	$videolibrary);
					}
					elseif ($track["@attributes"]["type"] == "Audio")
					{
						$audioID = 1;$audioformat = ""; $audiomode =  ""; $audiobitratemode =  ""; $audiobitrate =  ""; $audiochannels =  ""; $audiosamplerate =  ""; $audiolibrary =  "";$audiolanguage = "";$audiotitle = "";
						if (isset($track["@attributes"]["streamid"]))
							$audioID = $track["@attributes"]["streamid"];
						if (isset($track["Format"]))
							$audioformat = $track["Format"];
						if (isset($track["Mode"]))
							$audiomode = $track["Mode"];
						if (isset($track["Bit_rate_mode"]))
							$audiobitratemode = $track["Bit_rate_mode"];
						if (isset($track["Bit_rate"]))
							$audiobitrate = $track["Bit_rate"];
						if (isset($track["Channel_s_"]))
							$audiochannels = $track["Channel_s_"];
						if (isset($track["Sampling_rate"]))
							$audiosamplerate = $track["Sampling_rate"];
						if (isset($track["Writing_library"]))
							$audiolibrary = $track["Writing_library"];
						if (isset($track["Language"]))
							$audiolanguage = $track["Language"];
						if (isset($track["Title"]))
							$audiotitle = $track["Title"];
						$audiodata = $track;
						$this->addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode, $audiobitrate, $audiochannels,$audiosamplerate, $audiolibrary, $audiolanguage,$audiotitle);
					}
					elseif ($track["@attributes"]["type"] == "Text")
					{
						$subsID = 1;$subslanguage = "Unknown";
						if (isset($track["@attributes"]["streamid"]))
							$subsID = $track["@attributes"]["streamid"];
						if (isset($track["Language"]))
							$subslanguage = $track["Language"];
						$this->addSubs($releaseID,$subsID,$subslanguage);
					}
				}
			}
		}
	}
	public function addVideo($releaseID, $containerformat, $overallbitrate, $videoduration,
						$videoformat, $videocodec, $videowidth,	$videoheight,
						$videoaspect, $videoframerate, 	$videolibrary)
	{
		$db = new DB();
		$sql = sprintf("insert into releasevideo
						(releaseID,		containerformat, overallbitrate,		videoduration,
						videoformat,		videocodec, videowidth,		videoheight,
						videoaspect,		videoframerate, 	videolibrary)
						values
						( %d, %s, %s, %s, %s, %s, %d, %d, %s, %d, %s )", 
							$releaseID, $db->escapeString($containerformat), $db->escapeString($overallbitrate),	$db->escapeString($videoduration),
							$db->escapeString($videoformat), $db->escapeString($videocodec), $videowidth,	$videoheight,
							$db->escapeString($videoaspect), $videoframerate, 	$db->escapeString($videolibrary));
		return $db->queryInsert($sql);
	}
	public function addAudio($releaseID, $audioID, $audioformat, $audiomode, $audiobitratemode,
							$audiobitrate, $audiochannels,$audiosamplerate, $audiolibrary, $audiolanguage,$audiotitle)
	{
		$db = new DB();
		$sql = sprintf("insert into releaseaudio
						(releaseID,	audioID,audioformat,audiomode, audiobitratemode, audiobitrate, 
						audiochannels,audiosamplerate,audiolibrary,audiolanguage,audiotitle)
						values
						( %d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s )", 
							$releaseID, $audioID,$db->escapeString($audioformat),$db->escapeString($audiomode), $db->escapeString($audiobitratemode), 	$db->escapeString($audiobitrate), $db->escapeString($audiochannels),$db->escapeString($audiosamplerate), $db->escapeString($audiolibrary),$db->escapeString($audiolanguage),$db->escapeString($audiotitle));
		return $db->queryInsert($sql);
	}
	
	public function addSubs($releaseID, $subsID, $subslanguage)
	{
		$db = new DB();
		$sql = sprintf("insert into releasesubs
						(releaseID,	subsID, subslanguage)
						values ( %d, %d, %s)", 
							$releaseID,$subsID,$db->escapeString($subslanguage));
		return $db->queryInsert($sql);
	}

	public function getFull($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("select * from releaseextrafull where releaseID = %d", $id));	
	}
	
	public function deleteFull($id)
	{
		$db = new DB();
		return $db->query(sprintf("delete from releaseextrafull where releaseID = %d", $id));	
	}
	
	public function addFull($id, $xml)
	{
		$db = new DB();
		return $db->queryInsert(sprintf("insert into releaseextrafull (releaseID, mediainfo) values (%d, %s)", $id, $db->escapeString($xml)));	
	}
}