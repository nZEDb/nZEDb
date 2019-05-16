<?php

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class Copy3rdPartyAppSettingsIntoNewTables extends AbstractMigration
{
	public function change(): void
	{
		$connection = ConnectionManager::get('default');

		$connection->execute(
			'INSERT INTO nzedb_cake.couchpotato (user_id, url, api) SELECT id, cp_url, cp_api FROM nzedb_cake.users WHERE cp_url IS NOT NULL AND cp_api IS NOT NULL ORDER BY id;'
		);

		$connection->execute(
			'INSERT INTO nzedb_cake.nzbget (user_id, url, username, password) SELECT id, nzbgeturl, nzbgetusername, nzbgetpassword FROM nzedb_cake.users WHERE nzbgeturl IS  NOT NULL AND nzbgetusername IS  NOT NULL AND nzbgetpassword IS  NOT NULL ORDER BY id'
		);

		$connection->execute(
			'INSERT INTO nzedb_cake.sabnzb (user_id, url, api_key, api_key_type, priority) SELECT id, saburl, sabapikey, sabapikeytype, sabpriority FROM nzedb_cake.users WHERE saburl IS NOT NULL AND sabapikey IS NOT NULL ORDER BY id'
		);

	}
}
