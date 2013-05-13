<?php

	//
	//	Cleans names for collections/releases/imports/namefixer.
	//
	class nameCleaning
	{
		//
		//	Cleans usenet subject before inserting, used for collectionhash.
		//
		public function collectionsCleaner($subject)
		{
			//Parts/files
			$cleansubject = preg_replace('/(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|\-)\d{1,4}(\)|\]|\s)|\(\d{1,3}\|\d{1,3}\)|\-\d{1,3}\-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s/i', '', $subject);
			//Anything between the quotes. Too much variance within the quotes, so remove it completely.
			$cleansubject = preg_replace('/\".+\"/i', '', $cleansubject);
			//File extensions - If it was not quotes.
			$cleansubject = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|idx|jpg|mp4|nfo|nzb|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+par2|par\s?2|zip|z{2})"?|\d{2,3}\s\-\s.+\.mp3|(\s|(\d{2,3})?\-)\d{2,3}\.mp3|\d{2,3}\.pdf|\.part\d{1,4}\./i', '', $cleansubject);
			//File Sizes - Non unique ones.
			$cleansubject = preg_replace('/\d{1,3}(,|\.|\/)\d{1,3}\s(k|m|g)b|(\])?\s\d{1,}KB\s(yENC)?|"?\s\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-?(\s?yenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s/i', '', $cleansubject);
			//Random stuff.
			$cleansubject = utf8_encode(trim(preg_replace('/AutoRarPar\d{1,5}/i', '', $cleansubject)));
			
			return $cleansubject;
		}
		
		//
		//	Cleans a usenet subject before inserting, used for searchname. Also used for imports.
		//
		public function releaseCleaner($subject)
		{
			//File and part count.
			$cleanerName = preg_replace('/(\(|\[|\s)\d{1,4}(\/|(\s|_)of(\s|_)|\-)\d{1,4}(\)|\]|\s)|\(\d{1,3}\|\d{1,3}\)|\-\d{1,3}\-\d{1,3}\.|\s\d{1,3}\sof\s\d{1,3}\.|\s\d{1,3}\/\d{1,3}|\d{1,3}of\d{1,3}\.|^\d{1,3}\/\d{1,3}\s/i', '', $subject);
			//Size.
			$cleanerName = preg_replace('/\d{1,3}(\.|,)\d{1,3}\s(K|M|G)B|\d{1,}(K|M|G)B|\d{1,}\sbytes|(\-\s)?\d{1,}(\.|,)?\d{1,}\s(g|k|m)?B\s\-(\syenc)?|\s\(d{1,3},\d{1,3}\s{K,M,G}B\)\s/i', '', $cleanerName);
			//Extensions.
			$cleanerName = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|epub|idx|iso|jpg|m4a|mds|mkv|mobi|mp4|nfo|nzb|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|par(\s?2|")|zip|z{2})"?|(\s|(\d{2,3})?\-)\d{2,3}\.mp3|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', '', $cleanerName);
			//Books + Music.
			$cleanerName = preg_replace('/\[ATTN:.+?\]|ATTN: [a-z]{3,13} |ATTN:(macserv 100|Test)|ATTN: .+? - ("|:)|ATTN .+?:|[\.\-_ ]NMR \d{2,3}|by [a-z0-9]{3,15}$|\(Day\d{1,}\/\?\)|enjoy!|\*enjoy\*|^ePub |New Ebooks \d{1,2} [a-z]{3,9} (19|20)\d\d|New Scans(,| )| NMR |Re: |Req: |!<-- REQ:|^Request/i', '', $cleanerName);
			//Unwanted stuff.
			if (preg_match('/>> presents/i', $cleanerName))
				$presentsName = $cleanerName;
			$cleanerName = preg_replace('/sample("| )?$|"sample|\(\?\?\?\?\)|\[AoU\]|AsianDVDClub\.org|AutoRarPar\d{1,5}|brothers\-of\-usenet\.(info|net)(\/\.net)?|~bY ([a-z]{3,15}|c-w)|By request|DVD-Freak|Ew-Free-Usenet-\d{1,5}|for\.usenet4ever\.info|ghost-of-usenet.org<<|GOU<<|(http:\/\/www\.)?friends-4u\.nl|\[\d+\]-\[abgxEFNET\]-|\[[a-z\d]+\]\-\[[a-z\d]+\]-\[FULL\]-|\[\d{3,}\]-\[FULL\]-\[(a\.b| abgx).+?\]|\[\d{1,}\]|\-\[FULL\].+?#a\.b[\w.#!@$%^&*\(\){}\|\\:"\';<>,?~` ]+\]|Lords-of-Usenet(\] <> presents)?|nzbcave\.co\.uk( VIP)?|(Partner (of|von) )?SSL\-News\.info>> presents|\/ post: |powere?d by (4ux(\.n\)?l)?|the usenet)|ssl-news(\.info)?|SSL - News\.Info|usenet-piraten\.info|\-\s\[.+?\]\s<>\spresents|<.+?https:\/\/secretusenet\.com>|SECTIONED brings you|team-hush\.org\/| TiMnZb |<TOWN>|www\.binnfo\.in|www\.dreameplace\.biz|wwwworld\.me|www\.town\.ag|(Draak48|Egbert47|jipenjans|Taima) post voor u op|Dik Trom post voor|Sponsored\.by\.SecretUsenet\.com|(::::)?UR-powered by SecretUsenet.com(::::)?|usenet4ever\.info|(www\.)?usenet-4all\.info|www\.torentz\.3xforum\.ro|usenet\-space\-cowboys\.info|> USC <|SecretUsenet\.com|Thanks to OP|\] und \[|www\.[a-z0-9]+\.(com|info|net|org)|zoekt nog posters\/spotters|>> presents/i', '', $cleanerName);
			if (!empty($presentsName))
			{
				echo $presentsName."\n";
				echo $cleanerName."\n";
			}
			//Change [pw] to passworded.
			$cleanerName = str_replace(array('[pw]', '[PW]', ' PW ', '(Password)'), ' PASSWORDED ', $cleanerName);
			//Replaces some characters with 1 space.
			$cleanerName = str_replace(array(".", "_", '-', "|", "<", ">", '"', "=", '[', "]", "(", ")", "{", "}", "*", ";"), " ", $cleanerName);
			//Replace multiple spaces with 1 space
			$cleanerName = trim(preg_replace('/\s\s+/i', ' ', $cleanerName));
			//Remove the double name.
			$cleanerName = implode(' ', array_intersect_key(explode(' ', $cleanerName), array_unique(array_map('strtolower', explode(' ', $cleanerName)))));
			
			if (empty($cleanerName)) {return $subject;}
			else {return $cleanerName;}
		}
		
		//
		//	Cleans release name for the namefixer class.
		//
		public function fixerCleaner($name)
		{
			//Extensions.
			$cleanerName = preg_replace('/(\.part(\d{1,5})?)?\.(7z|\d{3}(?=(\s|"))|avi|epub|exe|idx|jpg|mobi|mp4|nfo|nzb|par\s?2|pdf|rar|rev|r\d\d|sfv|srs|srr|sub|txt|vol.+(par2)|zip|z{2})"?|\d{2,3}\.pdf|yEnc|\.part\d{1,4}\./i', '', $name);
			//Replaces some characters with 1 space.
			$cleanerName = str_replace(array(".", "_", '-', "|", "<", ">", '"', "=", '[', "]", "(", ")", "{", "}", "*", ";"), " ", $cleanerName);
			//Replace multiple spaces with 1 space
			$cleanerName = preg_replace('/\s\s+/i', ' ', $cleanerName);
			//Remove invalid characters.
			$cleanerName = trim(utf8_encode(preg_replace('/[^(\x20-\x7F)]*/','', $cleanerName)));
			
			return $cleanerName;
		}
	}
?>
