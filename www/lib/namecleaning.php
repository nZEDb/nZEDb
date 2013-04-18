<?php

	//
	//	Cleans names for collections/releases/imports.
	//
	class nameCleaning
	{
		//
		//	Cleans usenet subject before inserting, used for collectionhash.
		//
		public function collectionsCleaner($subject)
		{
			//Parts/files
			$cleansubject = preg_replace('/\[\d+(\/|(\s|_)of(\s|_)|\-)\d+\]|\(\d+(\/|\sof\s|\-)\d+\)|File\s\d+\sof\s\d{1,4}|\-\s\d{1,3}\/\d{1,3}\s\-|\d{1,3}\/\d{1,3}\]\s\-|\s\d{2,3}(\\|\/)\d{2,3}|^\[\d{1,3}\/\d{1,3}\s\s|\(\d{1,3}\|\d{1,3}\)/i', '', $subject);
			//Anything between the quotes. Too much variance within the quotes, so remove it completely.
			$cleansubject = preg_replace('/\".+\"/i', '', $cleansubject);
			//File extensions - If it was not quotes.
			$cleansubject = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|idx|jpg|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip|z{2})"?|\d{2,3}\.pdf|\d{2,3}\s\-\s.+\.mp3|\.part\d{1,4}\./i', '', $cleansubject);
			//File Sizes - Non unique ones.
			$cleansubject = preg_replace('/\-\s\d{1,3}\.\d{1,3}\s(M|K)B\s(?=\-\s\d{1,3}\.\d{1,3}\s(G|M)B\s)|><\s\d{1,3}\/\d{1,3}\s\(.+><\s\d{1,3},\d{1,3}\s(G|M)B\s>|(\])?\s\d{1,}KB\s(yENC)?|"?\s\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s/i', '', $cleansubject);
			//Random stuff.
			$cleansubject = preg_replace('/AutoRarPar\d{1,5}/i', '', $cleansubject);
			$cleansubject = utf8_encode(trim($cleansubject));
			
			return $cleansubject;
		}
		
		//
		//	Cleans a usenet subject before inserting, used for searchname.
		//
		public function releaseCleaner($subject)
		{
			//File and part count.
			$cleanerName = preg_replace('/\[\d+(\/|(\s|_)of(\s|_)|\-)\d+\]|\(\d+(\/|\sof\s|\-)\d+\)|File\s\d+\sof\s\d{1,4}|\-\s\d{1,3}\/\d{1,3}\s\-|\d{1,3}\/\d{1,3}\]\s\-|\s\d{2,3}(\\|\/)\d{2,3}\s|^\[\d{1,3}\/\d{1,3}\s|\(\d{1,3}\|\d{1,3}\)/i', '', $subject);
			//Size.
			$cleanerName = preg_replace('/\d{1,3}(\.|,)\d{1,3}\s(K|M|G)B|\d{1,}(K|M|G)B|\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-(\syenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s/i', '', $cleanerName);
			//Extensions.
			$cleanerName = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|epub|idx|jpg|mobi|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip|z{2})"?|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', '', $cleanerName);
			//Unwanted stuff.
			$cleanerName = preg_replace('/SECTIONED brings you|usenet\-space\-cowboys\.info|<.+https:\/\/secretusenet\.com>|> USC <|\[\d{1,}\]\-\[FULL\].+#a\.b[\w.#!@$%^&*\(\){}\|\\:"\';<>,?~` ]+\]|brothers\-of\-usenet\.info(\/\.net)?|Partner von SSL\-News\.info|AutoRarPar\d{1,5}/i', '', $cleanerName);
			//Removes some characters.
			$cleanerName = preg_replace('/<|>|"|=|\[|\]|\(|\)|\{|\}/i', '', $cleanerName);
			//Replaces some characters with 1 space.
			$cleanerName = preg_replace('/\.|\_|\-|\|/i', ' ', $cleanerName);
			//Replace multiple spaces with 1 space
			$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);
			$cleanerName = trim($cleanerName);
			
			return $cleanerName;
		}
		
		//
		//	Cleans release name for the namefixer class.
		//
		public function fixerCleaner($name)
		{
			//Extensions.
			$cleanerName = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|epub|exe|idx|jpg|mobi|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip|z{2})"?|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', '', $name);
			//Removes some characters.
			$cleanerName = preg_replace('/<|>|"|=|\[|\]|\(|\)|\{|\}/i', '', $cleanerName);
			//Replaces some characters with 1 space.
			$cleanerName = preg_replace('/\.|\_|\-|\|/i', ' ', $cleanerName);
			//Replace multiple spaces with 1 space
			$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);
			$cleanerName = trim($cleanerName);
			
			return $cleanerName;
		}
	}
