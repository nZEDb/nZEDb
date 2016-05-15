Follow a guide for whatever Operating System you are using.

* After you have installed PHP you should [install Composer]
(https://getcomposer.org/doc/00-intro.md#downloading-the-composer-executable). We recommend the
global method, as we do not provide the composer.phar file in our repo.

* At the point that your chosen guide, instructs you to git clone the nZEDb repository,
	do ONE of the following:
 	**NOTE** Commands below assume you followed the packagist instructions and renamed
 	composer.phar to composer. Do NOT git clone.

Either:
	1) If you will not be working on the code (most people), run this command:

	composer create-project --no-dev --keep-vcs nzedb/nzedb

This will install the latest stable version of the code.

or:
	2) Run:

	composer create-project --stability dev --keep-vcs --prefer-source nzedb/nzedb

This clones the development branch at the latest commit.
