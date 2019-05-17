<?php

use Cake\Datasource\ConnectionManager;
use Migrations\AbstractMigration;
use zed\Nzedb;
use zed\db\DB;


class Initial extends AbstractMigration
{

    public $autoId = false;

	/**
	 * @return void
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function up()
    {
		if ($this->hasTable('anidb_episodes')) {
			return;
		}

        $this->table('anidb_episodes')
            ->addColumn('anidbid', 'integer', [
                'comment' => 'ID of title from AniDB',
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('episodeid', 'integer', [
                'comment' => 'anidb id for this episode',
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['anidbid', 'episodeid'])
            ->addColumn('episode_no', 'integer', [
                'comment' => 'Numeric version of episode (leave 0 for combined episodes).',
                'default' => null,
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('episode_title', 'string', [
                'comment' => 'Title of the episode (en, x-jat)',
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('airdate', 'date', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('anidb_info')
            ->addColumn('anidbid', 'integer', [
                'comment' => 'ID of title from AniDB',
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['anidbid'])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 32,
                'null' => true,
            ])
            ->addColumn('startdate', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('enddate', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updated', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('related', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => true,
            ])
            ->addColumn('similar', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => true,
            ])
            ->addColumn('creators', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('rating', 'string', [
                'default' => null,
                'limit' => 5,
                'null' => true,
            ])
            ->addColumn('picture', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('categories', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => true,
            ])
            ->addColumn('characters', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => true,
            ])
            ->addIndex(
                [
                    'startdate',
                    'enddate',
                    'updated',
                ]
            )
            ->create();

        $this->table('anidb_titles')
            ->addColumn('anidbid', 'integer', [
                'comment' => 'ID of title from AniDB',
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('type', 'string', [
                'comment' => 'type of title.',
                'default' => null,
                'limit' => 25,
                'null' => false,
            ])
            ->addColumn('lang', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addPrimaryKey(['anidbid', 'type', 'lang', 'title'])
            ->create();

        $this->table('audio_data')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('audioid', 'integer', [
                'default' => null,
                'limit' => 2,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('audioformat', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('audiomode', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('audiobitratemode', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('audiobitrate', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('audiochannels', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('audiosamplerate', 'string', [
                'default' => null,
                'limit' => 25,
                'null' => true,
            ])
            ->addColumn('audiolibrary', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('audiolanguage', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('audiotitle', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addIndex(
                [
                    'releases_id',
                    'audioid',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('binaries')
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('collections_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('filenumber', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('totalparts', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('currentparts', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('binaryhash', 'binary', [
                'default' => '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('partcheck', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('partsize', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addIndex(
                [
                    'binaryhash',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'partcheck',
                ]
            )
            ->addIndex(
                [
                    'collections_id',
                ]
            )
            ->create();

        $this->table('binaryblacklist')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('groupname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('regex', 'string', [
                'default' => null,
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('msgcol', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('optype', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('status', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addColumn('last_activity', 'date', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'groupname',
                ]
            )
            ->addIndex(
                [
                    'status',
                ]
            )
            ->create();

        $this->table('bookinfo')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('author', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('asin', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('isbn', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('ean', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addColumn('salesrank', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('publisher', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('publishdate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('pages', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('overview', 'string', [
                'default' => null,
                'limit' => 3000,
                'null' => true,
            ])
            ->addColumn('genre', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('cover', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'asin',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'author',
                    'title',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('parentid', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('status', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('disablepreview', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('minsize', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addIndex(
                [
                    'status',
                ]
            )
            ->addIndex(
                [
                    'parentid',
                ]
            )
            ->create();

        $this->table('category_regexes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('group_regex', 'string', [
                'comment' => 'This is a regex to match against usenet groups',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('regex', 'string', [
                'comment' => 'Regex used to match a release name to categorize it',
                'default' => '',
                'limit' => 5000,
                'null' => false,
            ])
            ->addColumn('status', 'boolean', [
                'comment' => '1=ON 0=OFF',
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'comment' => 'Optional extra details on this regex',
                'default' => '',
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('ordinal', 'integer', [
                'comment' => 'Order to run the regex in',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('categories_id', 'integer', [
                'comment' => 'Which categories id to put the release in',
                'default' => '10',
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL,
                'null' => false,
                'signed' => false,
            ])
            ->addIndex(
                [
                    'group_regex',
                ]
            )
            ->addIndex(
                [
                    'status',
                ]
            )
            ->addIndex(
                [
                    'ordinal',
                ]
            )
            ->addIndex(
                [
                    'categories_id',
                ]
            )
            ->create();

        $this->table('collection_regexes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('group_regex', 'string', [
                'comment' => 'This is a regex to match against usenet groups',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('regex', 'string', [
                'comment' => 'Regex used for collection grouping',
                'default' => '',
                'limit' => 5000,
                'null' => false,
            ])
            ->addColumn('status', 'boolean', [
                'comment' => '1=ON 0=OFF',
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'comment' => 'Optional extra details on this regex',
                'default' => null,
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('ordinal', 'integer', [
                'comment' => 'Order to run the regex in',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'group_regex',
                ]
            )
            ->addIndex(
                [
                    'status',
                ]
            )
            ->addIndex(
                [
                    'ordinal',
                ]
            )
            ->create();

        $this->table('collections')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('subject', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('fromname', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('date', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('xref', 'string', [
                'default' => '',
                'limit' => 510,
                'null' => false,
            ])
            ->addColumn('totalfiles', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('collectionhash', 'string', [
                'default' => '0',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('collection_regexes_id', 'integer', [
                'comment' => 'FK to collection_regexes.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('dateadded', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('added', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('filecheck', 'integer', [
                'default' => '0',
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('filesize', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('releases_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('noise', 'string', [
                'default' => '',
                'limit' => 32,
                'null' => false,
            ])
            ->addIndex(
                [
                    'collectionhash',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'fromname',
                ]
            )
            ->addIndex(
                [
                    'date',
                ]
            )
            ->addIndex(
                [
                    'groups_id',
                ]
            )
            ->addIndex(
                [
                    'filecheck',
                ]
            )
            ->addIndex(
                [
                    'dateadded',
                ]
            )
            ->addIndex(
                [
                    'releases_id',
                ]
            )
            ->create();

        $this->table('consoleinfo')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('asin', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addColumn('salesrank', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('platform', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('publisher', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('genre_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('esrb', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('releasedate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('review', 'string', [
                'default' => null,
                'limit' => 3000,
                'null' => true,
            ])
            ->addColumn('cover', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'asin',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'title',
                    'platform',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('countries')
            ->addColumn('id', 'string', [
                'comment' => '2 character code.',
                'default' => null,
                'limit' => 2,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('iso3', 'string', [
                'comment' => '3 character code.',
                'default' => null,
                'limit' => 3,
                'null' => false,
            ])
            ->addColumn('country', 'string', [
                'comment' => 'Name of the country.',
                'default' => null,
                'limit' => 180,
                'null' => false,
            ])
            ->addIndex(
                [
                    'country',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'iso3',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('dnzb_failures')
            ->addColumn('release_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('userid', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['release_id', 'userid'])
            ->addColumn('failed', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->create();

        $this->table('forum_posts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('forumid', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('parentid', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('subject', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('message', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('locked', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('sticky', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('replies', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'parentid',
                ]
            )
            ->addIndex(
                [
                    'user_id',
                ]
            )
            ->addIndex(
                [
                    'createddate',
                ]
            )
            ->addIndex(
                [
                    'updateddate',
                ]
            )
            ->create();

        $this->table('gamesinfo')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('asin', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addColumn('publisher', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('genre_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('esrb', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('releasedate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('review', 'string', [
                'default' => null,
                'limit' => 3000,
                'null' => true,
            ])
            ->addColumn('cover', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('backdrop', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('trailer', 'string', [
                'default' => '',
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('classused', 'string', [
                'default' => 'steam',
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'asin',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'title',
                ]
            )
            ->addIndex(
                [
                    'title',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('genres')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('type', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('disabled', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('groups')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('backfill_target', 'integer', [
                'default' => '1',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('first_record', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('first_record_postdate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('last_record', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('last_record_postdate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('last_updated', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('minfilestoformrelease', 'integer', [
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('minsizetoformrelease', 'biginteger', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('active', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('backfill', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => true,
            ])
            ->addIndex(
                [
                    'name',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'active',
                ]
            )
            ->create();

        $this->table('imdb_titles')
            ->addColumn('id', 'string', [
                'default' => 'tt0000000',
                'limit' => 9,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('type', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('primary_title', 'string', [
                'comment' => 'Common name of the title.',
                'default' => null,
                'limit' => 180,
                'null' => false,
            ])
            ->addColumn('original_title', 'string', [
                'comment' => 'Original name, in the original language, of the title.',
                'default' => null,
                'limit' => 180,
                'null' => false,
            ])
            ->addColumn('adult', 'boolean', [
                'comment' => 'Is the title Adult: 0 - not adult, 1 - adult.',
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('started', 'string', [
                'comment' => 'Release year of a title. In the case of TV Series, it is the series\' start year.',
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('ended', 'string', [
                'comment' => 'TV Series end year. NULL for all other types.',
                'default' => null,
                'limit' => 4,
                'null' => true,
            ])
            ->addColumn('runtime', 'integer', [
                'comment' => 'Main runtime of the title, in minutes.',
                'default' => null,
                'limit' => 7,
                'null' => true,
            ])
            ->addColumn('genres', 'string', [
                'comment' => 'Up to three genres associated with the title.',
                'default' => null,
                'limit' => 180,
                'null' => true,
            ])
            ->create();

        $this->table('invitations')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('guid', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('logging')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('username', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('host', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->create();

        $this->table('menu_items')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('href', 'string', [
                'default' => '',
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => '',
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('newwindow', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tooltip', 'string', [
                'default' => '',
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('role', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('ordinal', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('menueval', 'string', [
                'default' => '',
                'limit' => 2000,
                'null' => false,
            ])
            ->addIndex(
                [
                    'role',
                    'ordinal',
                ]
            )
            ->create();

        $this->table('missed_parts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('numberid', 'biginteger', [
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('attempts', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'numberid',
                    'groups_id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'attempts',
                ]
            )
            ->addIndex(
                [
                    'groups_id',
                    'attempts',
                ]
            )
            ->addIndex(
                [
                    'numberid',
                    'groups_id',
                    'attempts',
                ]
            )
            ->create();

        $this->table('movieinfo')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('imdbid', 'integer', [
                'default' => null,
                'limit' => 7,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tmdbid', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('tagline', 'string', [
                'default' => '',
                'limit' => 1024,
                'null' => false,
            ])
            ->addColumn('rating', 'string', [
                'default' => '',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('plot', 'string', [
                'default' => '',
                'limit' => 1024,
                'null' => false,
            ])
            ->addColumn('year', 'string', [
                'default' => '',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('genre', 'string', [
                'default' => '',
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('type', 'string', [
                'default' => '',
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('director', 'string', [
                'default' => '',
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('actors', 'string', [
                'default' => '',
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('language', 'string', [
                'default' => '',
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('cover', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('backdrop', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('trailer', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addIndex(
                [
                    'imdbid',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'title',
                ]
            )
            ->create();

        $this->table('multigroup_binaries')
            ->addColumn('id', 'biginteger', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('collections_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('filenumber', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('totalparts', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('currentparts', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('binaryhash', 'binary', [
                'default' => '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('partcheck', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('partsize', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addIndex(
                [
                    'binaryhash',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'partcheck',
                ]
            )
            ->addIndex(
                [
                    'collections_id',
                ]
            )
            ->create();

        $this->table('multigroup_collections')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'comment' => 'Primary key',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('subject', 'string', [
                'comment' => 'Collection subject',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('fromname', 'string', [
                'comment' => 'Collection poster',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('date', 'datetime', [
                'comment' => 'Collection post date',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('xref', 'string', [
                'comment' => 'Groups collection is posted in',
                'default' => '',
                'limit' => 510,
                'null' => false,
            ])
            ->addColumn('totalfiles', 'integer', [
                'comment' => 'Total number of files',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('collectionhash', 'string', [
                'comment' => 'MD5 hash of the collection',
                'default' => '0',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('collection_regexes_id', 'integer', [
                'comment' => 'FK to collection_regexes.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('dateadded', 'datetime', [
                'comment' => 'Date collection is added',
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('added', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('filecheck', 'integer', [
                'comment' => 'Status of the collection',
                'default' => '0',
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('filesize', 'biginteger', [
                'comment' => 'Total calculated size of the collection',
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('noise', 'string', [
                'default' => '',
                'limit' => 32,
                'null' => false,
            ])
            ->addIndex(
                [
                    'collectionhash',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'fromname',
                ]
            )
            ->addIndex(
                [
                    'date',
                ]
            )
            ->addIndex(
                [
                    'groups_id',
                ]
            )
            ->addIndex(
                [
                    'filecheck',
                ]
            )
            ->addIndex(
                [
                    'dateadded',
                ]
            )
            ->addIndex(
                [
                    'releases_id',
                ]
            )
            ->create();

        $this->table('multigroup_missed_parts')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('numberid', 'biginteger', [
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('attempts', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'numberid',
                    'groups_id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'attempts',
                ]
            )
            ->addIndex(
                [
                    'groups_id',
                    'attempts',
                ]
            )
            ->addIndex(
                [
                    'numberid',
                    'groups_id',
                    'attempts',
                ]
            )
            ->create();

        $this->table('multigroup_parts')
            ->addColumn('binaries_id', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('number', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['binaries_id', 'number'])
            ->addColumn('messageid', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('partnumber', 'integer', [
                'default' => '0',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('size', 'integer', [
                'default' => '0',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->create();

        $this->table('multigroup_posters')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'comment' => 'Primary key',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('poster', 'string', [
                'comment' => 'Name of the poster to track',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addIndex(
                [
                    'poster',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('musicinfo')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('asin', 'string', [
                'default' => null,
                'limit' => 128,
                'null' => true,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addColumn('salesrank', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('artist', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('publisher', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('releasedate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('review', 'string', [
                'default' => null,
                'limit' => 3000,
                'null' => true,
            ])
            ->addColumn('year', 'string', [
                'default' => null,
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('genre_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('tracks', 'string', [
                'default' => null,
                'limit' => 3000,
                'null' => true,
            ])
            ->addColumn('cover', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'asin',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'artist',
                    'title',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('page_contents')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('url', 'string', [
                'default' => null,
                'limit' => 2000,
                'null' => true,
            ])
            ->addColumn('body', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('metadescription', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('metakeywords', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('contenttype', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('showinmenu', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('status', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('ordinal', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('role', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'showinmenu',
                    'status',
                    'contenttype',
                    'role',
                ]
            )
            ->create();

        $this->table('parts')
            ->addColumn('binaries_id', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('number', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['binaries_id', 'number'])
            ->addColumn('messageid', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('partnumber', 'integer', [
                'default' => '0',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('size', 'integer', [
                'default' => '0',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->create();

        $this->table('predb')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'comment' => 'Primary key',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('nfo', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('size', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('category', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'comment' => 'Unix time of when the pre was created, or first noted by the system',
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updated', 'timestamp', [
                'comment' => 'Unix time of when the
  entry was last updated',
                'default' => '0000-00-00 00:00:00',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('source', 'string', [
                'default' => '',
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('requestid', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups',
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('nuked', 'boolean', [
                'comment' => 'Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked',
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('nukereason', 'string', [
                'comment' => 'If this pre is nuked, what is the reason?',
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('files', 'string', [
                'comment' => 'How many files does this pre have ?',
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('filename', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('searched', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'title',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'nfo',
                ]
            )
            ->addIndex(
                [
                    'created',
                ]
            )
            ->addIndex(
                [
                    'source',
                ]
            )
            ->addIndex(
                [
                    'requestid',
                    'groups_id',
                ]
            )
            ->addIndex(
                [
                    'filename',
                ]
            )
            ->addIndex(
                [
                    'searched',
                ]
            )
            ->addIndex(
                [
                    'filename',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('predb_hashes')
            ->addColumn('hash', 'binary', [
                'default' => '',
                'limit' => 20,
                'null' => false,
            ])
            ->addPrimaryKey(['hash'])
            ->addColumn('predb_id', 'integer', [
                'comment' => 'id, of the predb entry, this hash belongs to',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->create();

        $this->table('predb_imports')
            ->addColumn('title', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('nfo', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('size', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('category', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updated', 'datetime', [
                'default' => '0000-00-00 00:00:00',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('source', 'string', [
                'default' => '',
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('requestid', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups',
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('nuked', 'boolean', [
                'comment' => 'Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked',
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('nukereason', 'string', [
                'comment' => 'If this pre is nuked, what is the reason?',
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('files', 'string', [
                'comment' => 'How many files does this pre have ?',
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('filename', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('searched', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('groupname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->create();

        $this->table('release_comments')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('text', 'string', [
                'default' => '',
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('text_hash', 'string', [
                'default' => '',
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('username', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('host', 'string', [
                'default' => null,
                'limit' => 15,
                'null' => true,
            ])
            ->addColumn('shared', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('shareid', 'string', [
                'default' => '',
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('siteid', 'string', [
                'default' => '',
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('nzb_guid', 'binary', [
                'default' => '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'text_hash',
                    'releases_id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'releases_id',
                ]
            )
            ->addIndex(
                [
                    'user_id',
                ]
            )
            ->create();

        $this->table('release_files')
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addPrimaryKey(['releases_id', 'name'])
            ->addColumn('size', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('ishashed', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('passworded', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'ishashed',
                ]
            )
            ->create();

        $this->table('release_naming_regexes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('group_regex', 'string', [
                'comment' => 'This is a regex to match against usenet groups',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('regex', 'string', [
                'comment' => 'Regex used for extracting name from subject',
                'default' => '',
                'limit' => 5000,
                'null' => false,
            ])
            ->addColumn('status', 'boolean', [
                'comment' => '1=ON 0=OFF',
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'comment' => 'Optional extra details on this regex',
                'default' => '',
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('ordinal', 'integer', [
                'comment' => 'Order to run the regex in',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addIndex(
                [
                    'group_regex',
                ]
            )
            ->addIndex(
                [
                    'status',
                ]
            )
            ->addIndex(
                [
                    'ordinal',
                ]
            )
            ->create();

        $this->table('release_nfos')
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['releases_id'])
            ->addColumn('nfo', 'binary', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('release_search_data')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('guid', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('searchname', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('fromname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addIndex(
                [
                    'releases_id',
                ]
            )
            ->addIndex(
                [
                    'guid',
                ]
            )
            ->addIndex(
                [
                    'name',
                ],
                ['type' => 'fulltext']
            )
            ->addIndex(
                [
                    'searchname',
                ],
                ['type' => 'fulltext']
            )
            ->addIndex(
                [
                    'fromname',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('release_subtitles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('subsid', 'integer', [
                'default' => null,
                'limit' => 2,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('subslanguage', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addIndex(
                [
                    'releases_id',
                    'subsid',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('release_unique')
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id.',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('uniqueid', 'binary', [
                'comment' => 'Unique_ID from mediainfo.',
                'default' => '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
                'limit' => null,
                'null' => false,
            ])
            ->addPrimaryKey(['releases_id', 'uniqueid'])
            ->create();

        $this->table('releaseextrafull')
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['releases_id'])
            ->addColumn('mediainfo', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();

        $this->table('releases')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('categories_id', 'integer', [
                'default' => '10',
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id', 'categories_id'])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('searchname', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('totalpart', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('size', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('postdate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('adddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updatetime', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('guid', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('leftguid', 'string', [
                'comment' => 'The first letter of the release guid',
                'default' => null,
                'limit' => 1,
                'null' => false,
            ])
            ->addColumn('fromname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('completion', 'float', [
                'default' => '0',
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('videos_id', 'integer', [
                'comment' => 'FK to videos.id of the parent series.',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tv_episodes_id', 'integer', [
                'comment' => 'FK to tv_episodes.id for the episode.',
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('imdbid', 'integer', [
                'default' => null,
                'limit' => 7,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('xxxinfo_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('musicinfo_id', 'integer', [
                'comment' => 'FK to musicinfo.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('consoleinfo_id', 'integer', [
                'comment' => 'FK to consoleinfo.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('gamesinfo_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('bookinfo_id', 'integer', [
                'comment' => 'FK to bookinfo.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('anidbid', 'integer', [
                'comment' => 'FK to anidb_titles.anidbid',
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('predb_id', 'integer', [
                'comment' => 'FK to predb.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('grabs', 'integer', [
                'default' => '0',
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('comments', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('passwordstatus', 'integer', [
                'default' => '0',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('rarinnerfilecount', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('haspreview', 'integer', [
                'default' => '0',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('nfostatus', 'integer', [
                'default' => '0',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('jpgstatus', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('videostatus', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('audiostatus', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('dehashstatus', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('reqidstatus', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('nzb_guid', 'binary', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('nzbstatus', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('iscategorized', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('isrenamed', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('ishashed', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('isrequestid', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('proc_pp', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('proc_sorter', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('proc_par2', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('proc_nfo', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('proc_files', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('proc_uid', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'name',
                ]
            )
            ->addIndex(
                [
                    'groups_id',
                    'passwordstatus',
                ]
            )
            ->addIndex(
                [
                    'postdate',
                    'searchname',
                ]
            )
            ->addIndex(
                [
                    'guid',
                ]
            )
            ->addIndex(
                [
                    'leftguid',
                    'predb_id',
                ]
            )
            ->addIndex(
                [
                    'nzb_guid',
                ]
            )
            ->addIndex(
                [
                    'videos_id',
                ]
            )
            ->addIndex(
                [
                    'tv_episodes_id',
                ]
            )
            ->addIndex(
                [
                    'imdbid',
                ]
            )
            ->addIndex(
                [
                    'xxxinfo_id',
                ]
            )
            ->addIndex(
                [
                    'musicinfo_id',
                    'passwordstatus',
                ]
            )
            ->addIndex(
                [
                    'consoleinfo_id',
                ]
            )
            ->addIndex(
                [
                    'gamesinfo_id',
                ]
            )
            ->addIndex(
                [
                    'bookinfo_id',
                ]
            )
            ->addIndex(
                [
                    'anidbid',
                ]
            )
            ->addIndex(
                [
                    'predb_id',
                    'searchname',
                ]
            )
            ->addIndex(
                [
                    'haspreview',
                    'passwordstatus',
                ]
            )
            ->addIndex(
                [
                    'passwordstatus',
                ]
            )
            ->addIndex(
                [
                    'nfostatus',
                    'size',
                ]
            )
            ->addIndex(
                [
                    'dehashstatus',
                    'ishashed',
                ]
            )
            ->addIndex(
                [
                    'adddate',
                    'reqidstatus',
                    'isrequestid',
                ]
            )
            ->create();

        $this->table('releases_groups')
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('groups_id', 'integer', [
                'comment' => 'FK to groups.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['releases_id', 'groups_id'])
            ->create();

        $this->table('releases_se')
            ->addColumn('id', 'biginteger', [
                'default' => null,
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('weight', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('query', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('searchname', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('fromname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('filename', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addIndex(
                [
                    'query',
                ]
            )
            ->create();

        $this->table('role_excluded_categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('role', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('categories_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'role',
                    'categories_id',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('settings')
            ->addColumn('section', 'string', [
                'default' => '',
                'limit' => 25,
                'null' => false,
            ])
            ->addColumn('subsection', 'string', [
                'default' => '',
                'limit' => 25,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 25,
                'null' => false,
            ])
            ->addPrimaryKey(['section', 'subsection', 'name'])
            ->addColumn('value', 'string', [
                'default' => '',
                'limit' => 1000,
                'null' => false,
            ])
            ->addColumn('hint', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('setting', 'string', [
                'default' => '',
                'limit' => 64,
                'null' => false,
            ])
            ->addIndex(
                [
                    'setting',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('sharing')
            ->addColumn('site_guid', 'string', [
                'default' => '',
                'limit' => 40,
                'null' => false,
            ])
            ->addPrimaryKey(['site_guid'])
            ->addColumn('site_name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('username', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('posting', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('fetching', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('auto_enable', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('start_position', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('hide_users', 'boolean', [
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('last_article', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('max_push', 'integer', [
                'default' => '40',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('max_download', 'integer', [
                'default' => '150',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('max_pull', 'integer', [
                'default' => '20000',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->create();

        $this->table('sharing_sites')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('site_name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('site_guid', 'string', [
                'default' => '',
                'limit' => 40,
                'null' => false,
            ])
            ->addColumn('last_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('first_time', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('enabled', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('comments', 'integer', [
                'default' => '0',
                'limit' => 8,
                'null' => false,
                'signed' => false,
            ])
            ->create();

        $this->table('short_groups')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('first_record', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('last_record', 'biginteger', [
                'default' => '0',
                'limit' => 20,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('updated', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(
                [
                    'name',
                ]
            )
            ->create();

        $this->table('steam_apps')
            ->addColumn('name', 'string', [
                'comment' => 'Steam application name',
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('appid', 'integer', [
                'comment' => 'Steam application id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['name', 'appid'])
            ->addIndex(
                [
                    'name',
                ],
                ['type' => 'fulltext']
            )
            ->create();

        $this->table('tmux')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('setting', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 1000,
                'null' => true,
            ])
            ->addColumn('updateddate', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'setting',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('tv_episodes')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('videos_id', 'integer', [
                'comment' => 'FK to videos.id of the parent series.',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('series', 'integer', [
                'comment' => 'Number of series/season.',
                'default' => '0',
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('episode', 'integer', [
                'comment' => 'Number of episode within series',
                'default' => '0',
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_SMALL,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('se_complete', 'string', [
                'comment' => 'String version of Series/Episode as taken from release subject (i.e. S02E21+22).',
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'comment' => 'Title of the episode.',
                'default' => null,
                'limit' => 180,
                'null' => false,
            ])
            ->addColumn('firstaired', 'date', [
                'comment' => 'Date of original airing/release.',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('summary', 'text', [
                'comment' => 'Description/summary of the episode.',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'videos_id',
                    'series',
                    'episode',
                    'firstaired',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('tv_info')
            ->addColumn('videos_id', 'integer', [
                'comment' => 'FK to video.id',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['videos_id'])
            ->addColumn('summary', 'text', [
                'comment' => 'Description/summary of the show.',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('publisher', 'string', [
                'comment' => 'The channel/network of production/release (ABC, BBC, Showtime, etc.).',
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('localzone', 'string', [
                'comment' => 'The linux tz style identifier',
                'default' => '',
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('image', 'boolean', [
                'comment' => 'Does the video have a cover image?',
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'image',
                ]
            )
            ->create();

        $this->table('upcoming_releases')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('source', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('typeid', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('info', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('updateddate', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'source',
                    'typeid',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('user_downloads')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('timestamp', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'user_id',
                ]
            )
            ->addIndex(
                [
                    'timestamp',
                ]
            )
            ->create();

        $this->table('user_excluded_categories')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('categories_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'user_id',
                    'categories_id',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('user_movies')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('imdbid', 'integer', [
                'default' => null,
                'limit' => 7,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('categories', 'string', [
                'comment' => 'List of categories for user movies',
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'user_id',
                    'imdbid',
                ]
            )
            ->create();

        $this->table('user_requests')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('request', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('timestamp', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'user_id',
                ]
            )
            ->addIndex(
                [
                    'timestamp',
                ]
            )
            ->create();

        $this->table('user_roles')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('apirequests', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('downloadrequests', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('defaultinvites', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('isdefault', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('canpreview', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('user_series')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('videos_id', 'integer', [
                'comment' => 'FK to videos.id',
                'default' => null,
                'limit' => 16,
                'null' => false,
            ])
            ->addColumn('categories', 'string', [
                'comment' => 'List of categories for user tv shows',
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'user_id',
                    'videos_id',
                ]
            )
            ->create();

        $this->table('users')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('username', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('firstname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('lastname', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('email', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('password', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('role', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('host', 'string', [
                'default' => null,
                'limit' => 40,
                'null' => true,
            ])
            ->addColumn('grabs', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('rsstoken', 'string', [
                'default' => null,
                'limit' => 32,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('resetguid', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('lastlogin', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('apiaccess', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('invites', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('invitedby', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
            ])
            ->addColumn('movieview', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('xxxview', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('musicview', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('consoleview', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('bookview', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('gameview', 'integer', [
                'default' => '1',
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('saburl', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('sabapikey', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('sabapikeytype', 'boolean', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('sabpriority', 'boolean', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('queuetype', 'boolean', [
                'comment' => 'Type of queue, Sab or NZBGet',
                'default' => true,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('nzbgeturl', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('nzbgetusername', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('nzbgetpassword', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('userseed', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('cp_url', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('cp_api', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('style', 'string', [
            	'comment' => 'User\'s chosen style/theme',
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addIndex(
                [
                    'rsstoken',
                    'role',
                ]
            )
            ->addIndex(
                [
                    'role',
                ]
            )
            ->create();

        $this->table('users_releases')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 16,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'user_id',
                    'releases_id',
                ],
                ['unique' => true]
            )
            ->create();

        $this->table('video_data')
            ->addColumn('releases_id', 'integer', [
                'comment' => 'FK to releases.id',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['releases_id'])
            ->addColumn('containerformat', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('overallbitrate', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('videoduration', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => true,
            ])
            ->addColumn('videoformat', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('videocodec', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('videowidth', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('videoheight', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('videoaspect', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('videoframerate', 'float', [
                'default' => null,
                'null' => true,
                'precision' => 7,
                'scale' => 4,
            ])
            ->addColumn('videolibrary', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->create();

        $this->table('video_types')
            ->addColumn('id', 'integer', [
                'comment' => 'Value to use in other tables.',
                'default' => null,
                'limit' => \Phinx\Db\Adapter\MysqlAdapter::INT_TINY,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('type', 'string', [
                'comment' => 'Type of video.',
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addIndex(
                [
                    'type',
                ]
            )
            ->create();

        $this->table('videos')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'comment' => 'ID to be used in other tables as reference.',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('type', 'boolean', [
                'comment' => '0 = Unknown, 1 = TV, 2 = Film, 3 = Made for TV Film',
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'comment' => 'Name of the video.',
                'default' => null,
                'limit' => 180,
                'null' => false,
            ])
            ->addColumn('country_id', 'string', [
                'comment' => 'Two character country code (FK to countries table).',
                'default' => '',
                'limit' => 2,
                'null' => false,
            ])
            ->addColumn('started', 'datetime', [
                'comment' => 'Date (UTC) of production\'s first airing',
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('anidb', 'integer', [
                'comment' => 'ID number for anidb site',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('imdb', 'integer', [
                'comment' => 'ID number for IMDB site (without the \'tt\' prefix).',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tmdb', 'integer', [
                'comment' => 'ID number for TMDB site.',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('trakt', 'integer', [
                'comment' => 'ID number for TraktTV site.',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tvdb', 'integer', [
                'comment' => 'ID number for TVDB site',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tvmaze', 'integer', [
                'comment' => 'ID number for TVMaze site.',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('tvrage', 'integer', [
                'comment' => 'ID number for TVRage site.',
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('source', 'boolean', [
                'comment' => 'Which site did we use for info?',
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'title',
                    'type',
                    'started',
                    'country_id',
                ],
                ['unique' => true]
            )
            ->addIndex(
                [
                    'imdb',
                ]
            )
            ->addIndex(
                [
                    'tmdb',
                ]
            )
            ->addIndex(
                [
                    'trakt',
                ]
            )
            ->addIndex(
                [
                    'tvdb',
                ]
            )
            ->addIndex(
                [
                    'tvmaze',
                ]
            )
            ->addIndex(
                [
                    'tvrage',
                ]
            )
            ->addIndex(
                [
                    'type',
                    'source',
                ]
            )
            ->create();

        $this->table('videos_aliases')
            ->addColumn('videos_id', 'integer', [
                'comment' => 'FK to videos.id of the parent title.',
                'default' => null,
                'limit' => 11,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('title', 'string', [
                'comment' => 'AKA of the video.',
                'default' => null,
                'limit' => 180,
                'null' => false,
            ])
            ->addPrimaryKey(['videos_id', 'title'])
            ->create();

        $this->table('xxxinfo')
            ->addColumn('id', 'integer', [
                'autoIncrement' => true,
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addPrimaryKey(['id'])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('tagline', 'string', [
                'default' => null,
                'limit' => 1024,
                'null' => false,
            ])
            ->addColumn('plot', 'binary', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('genre', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('director', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => true,
            ])
            ->addColumn('actors', 'string', [
                'default' => null,
                'limit' => 2500,
                'null' => false,
            ])
            ->addColumn('extras', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('productinfo', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('trailers', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('directurl', 'string', [
                'default' => null,
                'limit' => 2000,
                'null' => false,
            ])
            ->addColumn('classused', 'string', [
                'default' => 'ade',
                'limit' => 4,
                'null' => false,
            ])
            ->addColumn('cover', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('backdrop', 'boolean', [
                'default' => false,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('createddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('updateddate', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addIndex(
                [
                    'title',
                ],
                ['unique' => true]
            )
            ->create();

		$connection = ConnectionManager::get('default');
		$db = new DB();
		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-binaryblacklist.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-categories.tsv'
		]);
		$connection->insert('categories', [
			'id'	=> 1000000,
			'title'	=> 'Other',
		]);
		$connection->update('categories', ['id' => 0], ['id' => 1000000]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-category_regexes.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-countries.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-forum_posts.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-genres.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-groups.tsv'
		]);

		$db->loadDataInfile([
			'enclosedby' => "'",
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-menu_items.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-page_contents.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-release_naming_regexes.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-settings.tsv'
		]);

		$db->loadDataInfile([
			'filepath' => Nzedb::RESOURCES . 'db' . DS . 'schema' . DS . 'data' . DS . '10-tmux.tsv'
		]);

		$connection->insert('user_roles',
			[
				'id'               => 1,
				'name'             => 'Guest',
				'apirequests'      => 0,
				'downloadrequests' => 0,
				'defaultinvites'   => 0,
				'isdefault'        => 0,
				'canpreview'       => 0
			]);
		$connection->insert('user_roles',
			[
				'id'               => 2,
				'name'             => 'User',
				'apirequests'      => 10,
				'downloadrequests' => 10,
				'defaultinvites'   => 1,
				'isdefault'        => 1,
				'canpreview'       => 0
			]);
		$connection->insert('user_roles',
			[
				'id'               => 3,
				'name'             => 'Admin',
				'apirequests'      => 1000,
				'downloadrequests' => 1000,
				'defaultinvites'   => 1000,
				'isdefault'        => 0,
				'canpreview'       => 1
			]);
		$connection->insert('user_roles',
			[
				'id'               => 4,
				'name'             => 'Disabled',
				'apirequests'      => 0,
				'downloadrequests' => 0,
				'defaultinvites'   => 0,
				'isdefault'        => 0,
				'canpreview'       => 0
			]);
		$connection->insert('user_roles',
			[
				'id'               => 5,
				'name'             => 'Moderator',
				'apirequests'      => 1000,
				'downloadrequests' => 1000,
				'defaultinvites'   => 1000,
				'isdefault'        => 0,
				'canpreview'       => 1
			]);
		$connection->insert('user_roles',
			[
				'id'               => 6,
				'name'             => 'Friend',
				'apirequests'      => 100,
				'downloadrequests' => 100,
				'defaultinvites'   => 5,
				'isdefault'        => 0,
				'canpreview'       => 1
			]);
		$connection->execute('UPDATE user_roles SET id = id - 1');

	}

    public function down()
    {
        $this->table('anidb_episodes')->drop()->save();
        $this->table('anidb_info')->drop()->save();
        $this->table('anidb_titles')->drop()->save();
        $this->table('audio_data')->drop()->save();
        $this->table('binaries')->drop()->save();
        $this->table('binaryblacklist')->drop()->save();
        $this->table('bookinfo')->drop()->save();
        $this->table('categories')->drop()->save();
        $this->table('category_regexes')->drop()->save();
        $this->table('collection_regexes')->drop()->save();
        $this->table('collections')->drop()->save();
        $this->table('consoleinfo')->drop()->save();
        $this->table('countries')->drop()->save();
        $this->table('dnzb_failures')->drop()->save();
        $this->table('forum_posts')->drop()->save();
        $this->table('gamesinfo')->drop()->save();
        $this->table('genres')->drop()->save();
        $this->table('groups')->drop()->save();
        $this->table('imdb_titles')->drop()->save();
        $this->table('invitations')->drop()->save();
        $this->table('logging')->drop()->save();
        $this->table('menu_items')->drop()->save();
        $this->table('missed_parts')->drop()->save();
        $this->table('movieinfo')->drop()->save();
        $this->table('multigroup_binaries')->drop()->save();
        $this->table('multigroup_collections')->drop()->save();
        $this->table('multigroup_missed_parts')->drop()->save();
        $this->table('multigroup_parts')->drop()->save();
        $this->table('multigroup_posters')->drop()->save();
        $this->table('musicinfo')->drop()->save();
        $this->table('page_contents')->drop()->save();
        $this->table('parts')->drop()->save();
        $this->table('predb')->drop()->save();
        $this->table('predb_hashes')->drop()->save();
        $this->table('predb_imports')->drop()->save();
        $this->table('release_comments')->drop()->save();
        $this->table('release_files')->drop()->save();
        $this->table('release_naming_regexes')->drop()->save();
        $this->table('release_nfos')->drop()->save();
        $this->table('release_search_data')->drop()->save();
        $this->table('release_subtitles')->drop()->save();
        $this->table('release_unique')->drop()->save();
        $this->table('releaseextrafull')->drop()->save();
        $this->table('releases')->drop()->save();
        $this->table('releases_groups')->drop()->save();
        $this->table('releases_se')->drop()->save();
        $this->table('role_excluded_categories')->drop()->save();
        $this->table('settings')->drop()->save();
        $this->table('sharing')->drop()->save();
        $this->table('sharing_sites')->drop()->save();
        $this->table('short_groups')->drop()->save();
        $this->table('steam_apps')->drop()->save();
        $this->table('tmux')->drop()->save();
        $this->table('tv_episodes')->drop()->save();
        $this->table('tv_info')->drop()->save();
        $this->table('upcoming_releases')->drop()->save();
        $this->table('user_downloads')->drop()->save();
        $this->table('user_excluded_categories')->drop()->save();
        $this->table('user_movies')->drop()->save();
        $this->table('user_requests')->drop()->save();
        $this->table('user_roles')->drop()->save();
        $this->table('user_series')->drop()->save();
        $this->table('users')->drop()->save();
        $this->table('users_releases')->drop()->save();
        $this->table('video_data')->drop()->save();
        $this->table('video_types')->drop()->save();
        $this->table('videos')->drop()->save();
        $this->table('videos_aliases')->drop()->save();
        $this->table('xxxinfo')->drop()->save();
    }
}
