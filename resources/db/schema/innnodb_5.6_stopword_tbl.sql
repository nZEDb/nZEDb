/* This file is for users that currently use InnoDB with MySQL 5.6+ and want to use InnoDB for their replicated releasesearch table.  While this isn't a requirement to import, the team
has decided to code the Fulltext searching within the site for MyISAM due to its larger compatibility and ease of future troubleshooting.  One major difference between MyISAM's and InnoDB's
full text search implementation is the stop word table.  MyISAM's (seen below) consists of 543 entries while InnoDB's consists of only 36.  This leads to greatly disparate results when
searching the same data between the two engines.  If you prefer your results using InnoDB's default stop word table, then ignore this file.  If you wish to keep your search in-line with the
way it was coded simply import this file into MySQL.  Using this import constitutes (in general) a major change to the entire MySQL instance as the change is Global to use the new stop word
table.  Because of this, it is recommended you import this table into the mysql database, NOT nzedb.  Do this with the following command (using an account that has access to the mysql database):

mysql -uroot mysql < /var/www/nZEDb/resources/db/schema/innodb_5.6_stopword_tbl.sql

Once imported, it is recommended to set:

innodb_ft_server_stopword_table = mysql/INNODB_FT_MYISAM_STOPWORD

In your my.cnf and restart MySQL services. Using this file will fail in any instance where the MySQL version is not 5.6+ or the InnoDB plugin is not enabled.  This will have no effect if you did
not run an ALTER TABLE releasesearch ENGINE=InnoDB command.  The DEFAULT CHARSET not being UTF-8 is not an error.  It must be latin1 or MySQL will error when applying it as the stopword table.*/

/* BEGIN STOPWORD IMPORT */

DROP TABLE IF EXISTS INNODB_FT_MYISAM_STOPWORD;

CREATE TABLE INNODB_FT_MYISAM_STOPWORD (value VARCHAR(18) NOT NULL DEFAULT '') ENGINE=InnoDB ROW_FORMAT=DYNAMIC DEFAULT CHARSET=latin1;

INSERT INTO INNODB_FT_MYISAM_STOPWORD (value) VALUES ('a\'s'), ('able'), ('about'), ('above'), ('according'), ('accordingly'), ('across'), ('actually'), ('after'), ('afterwards'), ('again'),
('against'), ('ain\'t'), ('all'), ('allow'), ('allows'), ('almost'), ('alone'), ('along'), ('already'), ('also'), ('although'), ('always'), ('am'), ('among'), ('amongst'), ('an'), ('and'),
('another'), ('any'), ('anybody'), ('anyhow'), ('anyone'), ('anything'), ('anyway'), ('anyways'), ('anywhere'), ('apart'), ('appear'), ('appreciate'), ('appropriate'), ('are'), ('aren\'t'),
('around'), ('as'), ('aside'), ('ask'), ('asking'), ('associated'), ('at'), ('available'), ('away'), ('awfully'), ('be'), ('became'), ('because'), ('become'), ('becomes'), ('becoming'), ('been'),
('before'), ('beforehand'), ('behind'), ('being'), ('believe'), ('below'), ('beside'), ('besides'), ('best'), ('better'), ('between'), ('beyond'), ('both'), ('brief'), ('but'), ('by'), ('c\'mon'),
('c\'s'), ('came'), ('can'), ('can\'t'), ('cannot'), ('cant'), ('cause'), ('causes'), ('certain'), ('certainly'), ('changes'), ('clearly'), ('co'), ('com'), ('come'), ('comes'), ('concerning'),
('consequently'), ('consider'), ('considering'), ('contain'), ('containing'), ('contains'), ('corresponding'), ('could'), ('couldn\'t'), ('course'), ('currently'), ('definitely'), ('described'),
('despite'), ('did'), ('didn\'t'), ('different'), ('do'), ('does'), ('doesn\'t'), ('doing'), ('don\'t'), ('done'), ('down'), ('downwards'), ('during'), ('each'), ('edu'), ('eg'), ('eight'),
('either'), ('else'), ('elsewhere'), ('enough'), ('entirely'), ('especially'), ('et'), ('etc'), ('even'), ('ever'), ('every'), ('everybody'), ('everyone'), ('everything'), ('everywhere'), ('ex'),
('exactly'), ('example'), ('except'), ('far'), ('few'), ('fifth'), ('first'), ('five'), ('followed'), ('following'), ('follows'), ('for'), ('former'), ('formerly'), ('forth'), ('four'), ('from'),
('further'), ('furthermore'), ('get'), ('gets'), ('getting'), ('given'), ('gives'), ('go'), ('goes'), ('going'), ('gone'), ('got'), ('gotten'), ('greetings'), ('had'), ('hadn\'t'), ('happens'),
('hardly'), ('has'), ('hasn\'t'), ('have'), ('haven\'t'), ('having'), ('he'), ('he\'s'), ('hello'), ('help'), ('hence'), ('her'), ('here'), ('here\'s'), ('hereafter'), ('hereby'), ('herein'),
('hereupon'), ('hers'), ('herself'), ('hi'), ('him'), ('himself'), ('his'), ('hither'), ('hopefully'), ('how'), ('howbeit'), ('however'), ('i\'d'), ('i\'ll'), ('i\'m'), ('i\'ve'), ('ie'), ('if'),
('ignored'), ('immediate'), ('in'), ('inasmuch'), ('inc'), ('indeed'), ('indicate'), ('indicated'), ('indicates'), ('inner'), ('insofar'), ('instead'), ('into'), ('inward'), ('is'), ('isn\'t'), ('it'),
('it\'d'), ('it\'ll'), ('it\'s'), ('its'), ('itself'), ('just'), ('keep'), ('keeps'), ('kept'), ('know'), ('known'), ('knows'), ('last'), ('lately'), ('later'), ('latter'), ('latterly'), ('least'),
('less'), ('lest'), ('let'), ('let\'s'), ('like'), ('liked'), ('likely'), ('little'), ('look'), ('looking'), ('looks'), ('ltd'), ('mainly'), ('many'), ('may'), ('maybe'), ('me'), ('mean'), ('meanwhile'),
('merely'), ('might'), ('more'), ('moreover'), ('most'), ('mostly'), ('much'), ('must'), ('my'), ('myself'), ('name'), ('namely'), ('nd'), ('near'), ('nearly'), ('necessary'), ('need'), ('needs'),
('neither'), ('never'), ('nevertheless'), ('new'), ('next'), ('nine'), ('no'), ('nobody'), ('non'), ('none'), ('noone'), ('nor'), ('normally'), ('not'), ('nothing'), ('novel'), ('now'), ('nowhere'),
('obviously'), ('of'), ('off'), ('often'), ('oh'), ('ok'), ('okay'), ('old'), ('on'), ('once'), ('one'), ('ones'), ('only'), ('onto'), ('or'), ('other'), ('others'), ('otherwise'), ('ought'), ('our'),
('ours'), ('ourselves'), ('out'), ('outside'), ('over'), ('overall'), ('own'), ('particular'), ('particularly'), ('per'), ('perhaps'), ('placed'), ('please'), ('plus'), ('possible'), ('presumably'),
('probably'), ('provides'), ('que'), ('quite'), ('qv'), ('rather'), ('rd'), ('re'), ('really'), ('reasonably'), ('regarding'), ('regardless'), ('regards'), ('relatively'), ('respectively'), ('right'),
('said'), ('same'), ('saw'), ('say'), ('saying'), ('says'), ('second'), ('secondly'), ('see'), ('seeing'), ('seem'), ('seemed'), ('seeming'), ('seems'), ('seen'), ('self'), ('selves'), ('sensible'),
('sent'), ('serious'), ('seriously'), ('seven'), ('several'), ('shall'), ('she'), ('should'), ('shouldn\'t'), ('since'), ('six'), ('so'), ('some'), ('somebody'), ('somehow'), ('someone'), ('something'),
('sometime'), ('sometimes'), ('somewhat'), ('somewhere'), ('soon'), ('sorry'), ('specified'), ('specify'), ('specifying'), ('still'), ('sub'), ('such'), ('sup'), ('sure'), ('t\'s'), ('take'), ('taken'),
('tell'), ('tends'), ('th'), ('than'), ('thank'), ('thanks'), ('thanx'), ('that'), ('that\'s'), ('thats'), ('the'), ('their'), ('theirs'), ('them'), ('themselves'), ('then'), ('thence'), ('there'),
('there\'s'), ('thereafter'), ('thereby'), ('therefore'), ('therein'), ('theres'), ('thereupon'), ('these'), ('they'), ('they\'d'), ('they\'ll'), ('they\'re'), ('they\'ve'), ('think'), ('third'),
('this'), ('thorough'), ('thoroughly'), ('those'), ('though'), ('three'), ('through'), ('throughout'), ('thru'), ('thus'), ('to'), ('together'), ('too'), ('took'), ('toward'), ('towards'), ('tried'),
('tries'), ('truly'), ('try'), ('trying'), ('twice'), ('two'), ('un'), ('under'), ('unfortunately'), ('unless'), ('unlikely'), ('until'), ('unto'), ('up'), ('upon'), ('us'), ('use'), ('used'), ('useful'),
('uses'), ('using'), ('usually'), ('value'), ('various'), ('very'), ('via'), ('viz'), ('vs'), ('want'), ('wants'), ('was'), ('wasn\'t'), ('way'), ('we'), ('we\'d'), ('we\'ll'), ('we\'re'), ('we\'ve'),
('welcome'), ('well'), ('went'), ('were'), ('weren\'t'), ('what'), ('what\'s'), ('whatever'), ('when'), ('whence'), ('whenever'), ('where'), ('where\'s'), ('whereafter'), ('whereas'), ('whereby'), ('wherein'),
('whereupon'), ('wherever'), ('whether'), ('which'), ('while'), ('whither'), ('who'), ('who\'s'), ('whoever'), ('whole'), ('whom'), ('whose'), ('why'), ('will'), ('willing'), ('wish'), ('with'), ('within'),
('without'), ('won\'t'), ('wonder'), ('would'), ('wouldn\'t'), ('yes'), ('yet'), ('you'), ('you\'d'), ('you\'ll'), ('you\'re'), ('you\'ve'), ('your'), ('yours'), ('yourself'), ('yourselves'), ('zero');

/* END STOPWORD IMPORT */
