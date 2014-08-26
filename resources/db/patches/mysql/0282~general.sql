UPDATE releases r INNER JOIN movieinfo m ON m.imdbid = r.imdbid SET r.imdbid = NULL where m.language = '';

DELETE FROM movieinfo where language = '';