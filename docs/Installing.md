Follow a guide for whatever Operating System you are using. You do NOT need to install Git, unless 
you you will be developing the code yourself.

* After you have installed PHP you will should [install Composer]
(https://getcomposer.org/doc/00-intro.md#downloading-the-composer-executable). We recommend the 
global method, as we do not provide the composer.phar file in our repo.

* At the point that your chosen guide, instructs you to clone the nZEDb repository, you should
 	 ONLY do so if you intend to work on the code yourself.

 	Either:
	1) If you will not be working on the code (most people), you should run this command instead 
	of cloning the repository (This will install the latest version of the stable code.):
	
	composer create-project nZEDb/nZEDb
	
 	or: 2) Clone the repository as instructed, then switch to the nZEDb directory and run:
	
	git checkout dev-branch
	composer install
	
	
	

