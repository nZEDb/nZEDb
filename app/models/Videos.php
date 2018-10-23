<?php

namespace app\models;

class Videos extends \lithium\data\Model
{
	// Anime is one of the types below. _TV if serial, _FILM if cinema or OVA, etc.
	const TYPE_UNKNOWN = 0;
	const TYPE_TV = 1;		// TV programme, but not a film.
	const TYPE_FILM = 2;	// Film of any type, except if made for TV (i.e. TV Movie on IMDb)/
	const TYPE_TVFILM = 3;	// Made for TV Film

	public $validates = [];
}

?>
