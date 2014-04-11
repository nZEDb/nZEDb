## Most of this guide is done from the command line (terminal).

1. Misc.

       # For those using an older version of ubuntu, php 5.4 is required. (Ubuntu 12.04 requires backports for php 5.4 a user reported.)


       # Apparmor interferes with some of our files, here is how to disable it:

                sudo /etc/init.d/apparmor stop
                sudo /etc/init.d/apparmor teardown
                sudo update-rc.d -f apparmor remove


       # For the threaded scripts you will require the Python cymysql module for mysql:

       # Python 2.*

                 sudo apt-get install python-setuptools python-pip
                 sudo python -m easy_install
                 sudo easy_install cymysql
                 pip list

       # Python 3.* - If Python 3 is installed, the module also must be installed

                 sudo apt-get install python3-setuptools python3-pip
                 sudo python3 -m easy_install pip
                 sudo pip-3.2 install cymysql
                 pip-3.2 list
       # -or-

                 sudo pip-3.3 install cymysql
                 pip-3.3 list

       #For Ubuntu 13.10, python3 uses pip3, not pip3.2

       # Or the Python psycopg module for PostgreSQL(this is not currently supported)

                sudo apt-get install postgresql postgresql-server-dev-all php5-pgsql python-dev python3-dev make

       # Python 2.*

                sudo apt-get install python-setuptools python-pip
                sudo easy_install psycopg2
                pip list

       # Python 3.* - If Python 3 is installed, the module also must be installed

                sudo apt-get install python3-setuptools python3-pip
                sudo easy_install3 psycopg2
                pip-3.2 list
                -or-
                pip-3.3 list

       # If after using easy_install, it still shows error, this link was current at the time this was posted: http://initd.org/psycopg/install/

                wget http://initd.org/psycopg/tarballs/PSYCOPG-2-5/psycopg2-2.5.1.tar.gz
                tar xfvz psycopg2-2.5.1.tar.gz
                cd psycopg2-2.5.1/
                sudo python setup.py install
                sudo python3 setup.py install
                pip-3.2 list
                -or-
                pip-3.3 list

       #For Ubuntu 13.10, python3 uses pip3, not pip3.2


2. Update and upgrade the operating system.

       # Update the sources.

                sudo apt-get update

       # Upgrade the applications.

                sudo apt-get upgrade

       # (OPTIONAL) Optionally run sudo apt-get dist-upgrade to upgrade the kernel.

                sudo apt-get dist-upgrade

       # Reboot.

                sudo reboot

3. Install PHP and extensions.

       # (OPTIONAL) Add a repository to get apache 2.4 and php 5.5

                sudo add-apt-repository ppa:ondrej/php5
                sudo apt-get update


       # Install PHP and the required extensions:


                sudo apt-get install php5 php5-dev php5-json php-pear php5-gd php5-mysqlnd php5-curl

4. Install MySQL OR PostgreSQL.

       # MySQL:

                sudo apt-get install mysql-server mysql-client libmysqlclient-dev

                If you are running MySQL not as root user, you will need to run this in MySQL shell (with the single quotes):
                GRANT FILE ON *.* TO 'YourMySQLUsername'@'YourMySQLServerIPAddress';

                my.cnf requires these changes:
                max_allowed_packet=12582912
                group_concat_max_len=8192

                Set your timezone :
                Use the TZ from here : https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
                default_time_zone=Africa/Abidjan

       # Or PostgreSQL(currently WIP, use MySQL for now.) Version 9.3 or higher is required:

                sudo add-apt-repository ppa:chris-lea/postgresql-9.3
                sudo apt-get update
                sudo apt-get install postgresql-9.3 php5-pgsql

       # ONLY PROCEED WITH SECTION IF YOU INSTALLED POSTGRESQL!

       # Login to PostgreSQL root user:

                sudo -i -u postgres

       # Enter the PostgreSQL CLI interface:

                psql

       # Create a user account (change the username):

                CREATE USER EnterYourUserNameHere;

       # Create a database for the user account (change the database name or leave it):

                CREATE DATABASE nzedb OWNER EnterYourUserNameHere;

       # Create a password for the user (the single quotes around the password are required):

                ALTER USER EnterYourUserNameHere WITH ENCRYPTED PASSWORD 'EnterYourPasswordHere';

       # Detach from pgsql and login to your linux user account:

                control+d
                su EnterYourLinuxUsernameHere

5. Install and configure Apache.

       # Install apache:

                sudo apt-get install apache2

       # Configure PHP CLI ini file using the nano text editor:

                sudo nano /etc/php5/cli/php.ini

       ## To search in nano, use control+w
       # Change the following settings:

                max_execution_time = 120

       # You can set 1024M to -1 if you have RAM to spare.

                memory_limit = 1024M

       # Change Europe/London to your local timezone, see here for a list: http://php.net/manual/en/timezones.php

       # remove the ; if there is one preceding date.timezone

                date.timezone = YourLocalTimezone

       # Press control+x when you are done to save and exit.

       # Configure the PHP apache2 ini file (use the above settings):

                sudo nano /etc/php5/apache2/php.ini

       # Use the following settings if using Apache 2.2 as your webserver:

       # Create the site config:

                sudo nano /etc/apache2/sites-available/nZEDb

       # Paste the following (This is your VirtualHost):

                <VirtualHost *:80>
                        ServerAdmin webmaster@localhost
                        ServerName localhost

                        DocumentRoot "/var/www/nZEDb/www"
                        LogLevel warn
                        ServerSignature Off
                        ErrorLog /var/log/apache2/error.log

                   <Directory "/var/www/nZEDb/www">
                          Options FollowSymLinks
                          AllowOverride All
                          Order allow,deny
                          allow from all
                 </Directory>

                </VirtualHost>

       # Save and exit nano.

       # Disable the default site, enable nZEDb, enable rewrite, restart apache:

                 sudo a2dissite default
                 sudo a2ensite nZEDb
                 sudo a2enmod rewrite
                 sudo service apache2 restart

       # Use the following settings if using Apache 2.4 as your webserver:

       # You must do the following change to /etc/apache2/apache2.conf:

                 sudo nano /etc/apache2/apache2.conf

                 Under <Directory /var/www/>, change AllowOverride None to AllowOverride All

       # Create the site config:

                 sudo nano /etc/apache2/sites-available/nZEDb.conf

       # Paste the same VirtualHost as above.

       # Disable the default site, enable nZEDb, enable rewrite, restart apache:

                 sudo a2dissite 00-default
                 sudo a2ensite nZEDb.conf
                 sudo a2enmod rewrite
                 sudo service apache2 restart

6.  Install Unrar/FFmpeg/Mediainfo/Lame

                 sudo apt-get install software-properties-common
                 sudo apt-get install unrar python-software-properties

      # Mediainfo

                 wget http://mediaarea.net/download/binary/libzen0/0.4.29/libzen0_0.4.29-1_amd64.xUbuntu_13.10.deb
                 wget http://mediaarea.net/download/binary/libmediainfo0/0.7.67/libmediainfo0_0.7.67-1_amd64.xUbuntu_13.10.deb
                 wget http://mediaarea.net/download/binary/mediainfo/0.7.67/mediainfo_0.7.67-1_amd64.Debian_7.0.deb
                 dpkg -i libzen0_0.4.29-1_amd64.xUbuntu_13.10.deb
                 dpkg -i libmediainfo0_0.7.67-1_amd64.xUbuntu_13.10.deb
                 dpkg -i mediainfo_0.7.67-1_amd64.Debian_7.0.deb

      # FFmpeg/Lame  Run the script located here.  https://github.com/jonnyboy/installers/blob/master/compile_ffmpeg.sh


7. Install memcache / apc.

      # APC:

                 sudo apt-get install php-apc
                 sudo service apache2 restart
                 sudo cp /usr/share/doc/php-apc/apc.php /var/www/nZEDb/www/admin

      # In the future you can go to localhost/admin/apc.php in your browser to view apc stats.

     # Memcache:

                 sudo apt-get install memcached php5-memcache

      # Edit php.ini, add   extension=memcache.so   in the dynamic extensions section (if you get warnings on apache start you can remove this).

                 sudo nano /etc/php5/apache2/php.ini
                 sudo service apache2 restart

      # AFTER git cloning and seting up the indexer (step 8 & 9), edit config.php and change MEMCACHE_ENABLED to true.

                 sudo nano /var/www/nZEDb/www/config.php

8. Git clone the nZEDb source.

      # If /var/www/ does not exist, create it.

                 mkdir /var/www/
                 cd /var/www/
                 sudo chmod 777 .

      # Install git.

                 sudo apt-get install git

      # Clone the git.

                 git clone https://github.com/nZEDb/nZEDb.git

      # Set the permissions.

                 sudo chmod 777 nZEDb
                 cd nZEDb
                 sudo chmod -R 755 .
                 sudo chmod 777 /var/www/nZEDb/smarty/templates_c
                 sudo chmod -R 777 /var/www/nZEDb/www/covers
                 sudo chmod 777 /var/www/nZEDb/www
                 sudo chmod 777 /var/www/nZEDb/www/install
                 sudo chmod -R 777 /var/www/nZEDb/nzbfiles

9. Run the installer.

      Change localhost for the server's IP if you are browsing on another computer.
            http://localhost/install

10. Configure the site.

      Enable some groups in view groups.

      Change settings in edit site (set api keys, set paths to unrar etc..)

11. Start indexing groups.

      Use scripts in misc/update (update_binaries to get article headers, update_releases to create releases).

      Use scripts in misc/update/nix to automate it.