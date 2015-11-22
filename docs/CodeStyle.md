#nZEDb Code Style

nZEDb uses a code style based on Lithium's [LSR-0](http://li3.me/docs/specs/accepted/LSR-0-coding.md), with a few modifications for features that
have been releases since PHP 5.3, or that are not mentioned in it at all.


##Changes
* Namespace declarations should follow our license header/comments, if they exist.
* Protected fields/methods are NOT preceded by an underscore. - This makes refactoring easier.
* Functions/methods have the opening brace (curly bracket) on a new line.
* Functions/methods should have one blank line before any return statement.


##Additions
* Array short form syntax should be used.

### Class anatomy
1. Class head.
2. Constants, preferably in alphabetical order for easier finding of individual entries.
3. Fields (variables). Ordered, by visibility (public, protected, private), and then preferably in alphabetical order for easier finding of individual entries.
4. Methods (functions). Ordered as fields above.

### Database
* Table names should be pluralised nouns that reflect the row content.
  i.e. releases - each row is data for one release
* Tables with a one-to-many relationship should use the singulat 'one' table's name, with the pluralised 'many' tables name, separated by an underscore.
  i.e. video_aliases - each video (from videos) can have more than one alias
* Tables that are a join table for many-to-many relationships have both tables pluralised.
  i.e users_releases contains rows for users and their releases.
* Table aliases should be an initialised version of the table's name. It is preferabe to separate the alias from the table name with 'AS'. This reduces errors caused by missed commas and makes the intent explicit.
  i.e. release_naming_regexes AS rnr


* Fields referencing fields in other tables (usually indexed fields), should use the table name followed by the field name separated by an underderscore.
  i.e. video_id is a reference to video.id (the id field in the videos table).
