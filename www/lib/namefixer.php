<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/categorizer.php");

class Namefixer
{
	//
	//Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($type)
	{
		//Fix releases using the release name, added in the past 24 hours.
		if ($type == 1)
		{
			
		}
		
		//Fix all releases using the release name.
		if ($type == 2)
		{
			
		}
		
		if ($type > 2 || $type == 0 || !is_numeric($type))
		{
			exit("ERROR: Wrong argument");
		}
	}
	
	//
	//Attempts to fix release names using the NFO. - Placeholder.
	//
	public function fixNamesWithNfo($type)
	{
		//fix releases using the NFO, added in the past 24 hours.
		if ($type == 1)
		{
			
		}
		//Fix all releases using the NFO.
		if ($type == 2)
		{
			
		}
		
		if ($type > 2 || $type == 0 || !is_numeric($type))
		{
			exit("ERROR: Wrong argument");
		}
	}
	
	//
	//Attempts to fix release names using the File name. - Placeholder.
	//
	public function fixNamesWithFiles($type)
	{
		//fix releases using the release file, added in the past 24 hours.
		if ($type == 1)
		{
			
		}
		//Fix all releases using the file name.
		if ($type == 2)
		{
			
		}
		
		if ($type > 2 || $type == 0 || !is_numeric($type))
		{
			exit("ERROR: Wrong argument");
		}
	}	
}
