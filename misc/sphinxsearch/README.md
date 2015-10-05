# nZEDb SPHINX SEARCH Readme
---------------------------

## _Installation_:

_Forewarning: Your MySQL server requires the SphinxSE plugin, this should be pre-installed on MariaDB._

_For MySQL or Percona, you might have to compile them. Please make sure you have this before continuing as this is required.  Please see the end of this file for instructions on compiling the Percona plugin._

_You can type SHOW ENGINES; in a MySQL client to verify that SPHINX is supported._

Head to http://sphinxsearch.com/downloads/release/, download the latest version for your operating system.

Assuming you are on ubuntu, you would:

`sudo add-apt-repository ppa:builds/sphinxsearch-rel21`

`sudo apt-get update`

`sudo apt-get install sphinxsearch`

## _Configuration_:
### Replace the default sphinx.conf file with our included sphinx.conf file:

If you are on linux, you can copy over our sphinx.conf, on other operating systems you will need to edit yours using ours as a guide.

Assuming you are on ubuntu:

`sudo mv /etc/sphinxsearch/sphinx.conf /etc/sphinxsearch/sphinx.conf.1`

`sudo cp /var/www/nZEDb/misc/sphinxsearch/sphinx.conf /etc/sphinxsearch/sphinx.conf`

### Edit the sphinx.conf file:
Everything should be good by default for linux, but you can get better performance by changing some settings. Specifically the rt_mem_limit and mem_limit settings, setting those to 2048M if you have the RAM will make a difference.

Read the sphinx manual for detailed information on various settings to get the best performance for your server: http://sphinxsearch.com/docs/current.html#conf-reference

Assuming you are on ubuntu:

`sudo nano /etc/sphinxsearch/sphinx.conf`

## _Create folders_:
Create the folders you specified in sphinx.conf

Assuming you are on ubuntu:

`sudo mkdir -p /var/lib/sphinxsearch/data/`

`sudo mkdir -p /var/log/sphinxsearch/`

## _Start the sphinx service_:
Assuming you are on ubuntu:
`sudo service sphinxsearch restart`

## _Test sphinxQL / Troubleshooting_:
At this point you should have a working sphinxQL server running on port 9306

You can test this by typing `mysql -P9306 -h0`

Now you should be logged in to sphinxQL, type `show tables` make sure you see the releases_rt Index

If you do not see the above, or could not log in to sphinxQL, look at the log file you specified in sphinx.conf or look at the /var/log/upstart/sphinxsearch.log file if you are on linux.

## _Setting up nZEDb for sphinx support_:

### MAKE SURE ALL RELEASE CREATION / IMPORT SCRIPTS ARE STOPPED.

### Edit the settings.php file:
Open up the nzedb/config/settings.php file with a text editor, if you do not have it copy the nzedb/config/settings.example.php file to nzedb/config/settings.php

Change the `nZEDb_RELEASE_SEARCH_TYPE` to `2`

If you changed the `listen` setting in sphinx.conf you will need to change the `nZEDb_SPHINXQL_HOST_NAME` / `nZEDb_SPHINXQL_PORT` / `nZEDb_SPHINXQL_SOCK_FILE` settings accordingly, otherwise it's fine.

### Create the SphinxSE table:
In this folder (.../misc/sphinxsearch) there is a create_se_tables.php file, run the file using your hostname / port to the sphinx server you set in sphinx.conf (by default this should be 0 and 9312).

### Populate the Sphinx RT index with data:
In this folder (.../misc/sphinxsearch) there is a populate_rt_indexes.php file, run the file and wait until it is complete.

You are now done with this guide, sphinx search should work. The index will populate itself from now on, you do not need to rerun this script unless you disable sphinx.

## _Misc_:

### Optimizing: You can optimize the rt index(es) by running optimize.php

Read about the benefits/drawbacks of optimizing here: http://sphinxsearch.com/docs/current.html#sphinxql-optimize-index

### Compiling SphinxSE Plugin for Percona:

Please STOP mysql during the build process.

First, pull down my building repository I forked from dragolabs and updated for Percona 5.6\Trusty.

`git clone https://github.com/ruhllatio/dpkg-sphinx-se.git`

`cd dpkg-sphinx-se`

_Note_:

If you are using Percona 5.6.19-67.0 and Sphinx 2.1.9 on Ubuntu 14.04 LTS 64-bit with all three most up-to-date from their repositories, you can likely install the Debian pkg in the trusty directory and forego the rest of this action by running:

`dpkg -i ./trusty/percona-5.6.19-67.0/sphinx-se-2.1.9_amd64.deb`

This action should install AND activate the Sphinx plugin in MySQL automatically.  If you get any errors with failed dependencies run:

`apt-get -f install`

If you insist on compiling yourself, read on.

First you need to install the bundler dependency.

`apt-get install bundler`

Once installed, run:

`bundle install`

From the project root.  This is the intricate part.  You must run the build script providing the following command line arguments as they exist in your environment.

`build.sh -s 2.1.9 -p 5.6.19-67.0 -d 5.6.19-67.0-618.trusty -o trusty`

The -s is your Sphinx version.  If you are unsure of the package version you installed, issue:

`dpkg -l | grep sphinxsearch`

You should see something along the lines of:

`ii  sphinxsearch 2.1.9-release-0ubuntu11~trusty  amd64 Fast standalone full-text SQL search engine`

In this case, 2.1.9 would be your sphinx version.

-p Is the version of percona-server you have installed on the machine and -d is the version of percona-server that exists in the repository you used to install it.  If you do not know these, issue:

`dpkg -l | grep percona-server-server-5.6` (or 5.5 if that's what you have installed)

You should see something similar to:

`ii percona-server-server-5.6  5.6.19-67.0-618.trusty amd64 Percona Server database server binaries`

In this case, 5.6.19-67.0 would be the installed version and 5.6.19-67.0-618.trusty would be the repository version.

The final argument for build.sh is optional.  It just marks the iteration of compilation for the plugin if you want multiple.

This script, once completed should leave a deb package in the _pkg directory.  You can install it with:

`dpkg -i file.deb`

This will copy the plugin to the correct directory and activate it in MySQL.  You can verify it is active by issuing:

`SHOW PLUGINS';` OR `SHOW ENGINES;` to see that Sphinx is installed and active.
