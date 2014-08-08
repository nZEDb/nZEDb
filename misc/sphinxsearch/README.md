# nZEDb SPHINX SEARCH Readme
---------------------------

## _Installation_:

Forewarning: Your MySQL server requires the SphinxSE plugin, this should be pre-installed on MariaDB.

For MySQL or Percona, you might have to compile them. Please make sure you have this before continuing as this is required.

You can type SHOW ENGINES; in a MySQL client to verify that SPHINX is supported.

Head to http://sphinxsearch.com/downloads/release/, download the latest version for your operating system.

Assuming you are on ubuntu 14.04 x64, you would:

`wget http://sphinxsearch.com/files/sphinxsearch_2.1.9-release-0ubuntu11~trusty_amd64.deb`
and:

`sudo dpkg -i sphinxsearch_2.1.9-release-0ubuntu11~trusty_amd64.deb`

## _Configuration_:
#####Replace the default sphinx.conf file with our included sphinx.conf file:

If you are on linux, you can copy over our sphinx.conf, on other operating systems you will need to edit yours using ours as a guide.

Assuming you are on ubuntu:

`sudo mv /etc/sphinxsearch/sphinx.conf /etc/sphinxsearch/sphinx.conf.1`

`sudo cp sphinx.conf /etc/sphinxsearch/sphinx.conf`

##### Edit the sphinx.conf file:
Everything should be good by default for linux, but you can get better performance by changing some settings. Specifically the rt_mem_limit and mem_limit settings, setting those to 2048M if you have the RAM will make a difference.

Read the sphinx manual for detailed information on various settings to get the best performance for your server: http://sphinxsearch.com/docs/2.1.9/conf-reference.html

Assuming you are on ubuntu:

`sudo nano /etc/sphinxsearch/sphinx.conf`

## _Create folders_:
Create the folders you specified in sphinx.conf

Assuming you are on ubuntu:

`sudo mkdir -p /var/lib/sphinxsearch/`

`sudo mkdir -p /var/lib/sphinxsearch/data/`

`sudo mkdir -p /var/log/sphinxsearch/`

`sudo mkdir -p /var/run/sphinxsearch/`

## _Start the sphinx service_:
Assuming you are on ubuntu:
`sudo service sphinxsearch restart`

## _Test sphinxQL / Troubleshooting_:
At this point you should have a working sphinxQL server running on port 9306

You can test this by typing `mysql -P9306 -h0`

Now you should be logged in to sphinxQL, type `show tables` make sure you see the releases_rt Index

If you do not see the above, or could not log in to sphinxQL, look at the log file you specified in sphinx.conf or look at the /var/log/upstart/sphinxsearch.log file if you are on linux.

## _Setting up nZEDb for sphinx support_:

#### MAKE SURE ALL RELEASE CREATION / IMPORT SCRIPTS ARE STOPPED.

##### Edit the settings.php file:
Open up the www/settings.php file with a text editor, if you do not have it copy the www/settings.php.example file to www/settings.php

Change the `nZEDb_RELEASE_SEARCH_TYPE` to `2`

If you changed the `listen` setting in sphinx.conf you will need to change the `nZEDb_SPHINXQL_HOST_NAME` / `nZEDb_SPHINXQL_PORT` / `nZEDb_SPHINXQL_SOCK_FILE` settings accordingly, otherwise it's fine.

##### Create the SphinxSE table:
In this folder there is a create_releases_table.php file, run the file using your hostname / port to the sphinx server you set in sphinx.conf (by default this should be localhost and 9312).

##### Populate the Sphinx RT index with data:
In this folder there is a populate_release_index.php file, run the file and wait until it is complete.

You are now done with this guide, sphinx search should work. The index will populate itself from now on, you do not need to rerun this script unless you disable sphinx.

## _Misc_:

Optimizing: You can optimize the rt index(es) by running optimize.php

Read about the benefits/drawbacks of optimizing here: http://sphinxsearch.com/docs/current.html#sphinxql-optimize-index