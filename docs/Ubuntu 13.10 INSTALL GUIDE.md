## Most of this guide is done from the command line (terminal).

1. Misc.

       # For those using an older version of ubuntu, php 5.4 is required. (Ubuntu 12.04 requires backports for php 5.4 a user reported.)


       # Apparmor interferes with some of our files, here is how to disable it:

                sudo /etc/init.d/apparmor stop
                sudo /etc/init.d/apparmor teardown
                sudo update-rc.d -f apparmor remove

                Note: You must reboot after doing this to take effect.


       # For the threaded scripts you will require the Python cymysql module for mysql:

       # Note: For Ubuntu 13.10, python3 uses pip3, not pip3.2

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

       # If after using easy_install, it still shows error, this link was current at the time this was posted: http://initd.org/psycopg/install/

                wget http://initd.org/psycopg/tarballs/PSYCOPG-2-5/psycopg2-2.5.1.tar.gz
                tar xfvz psycopg2-2.5.1.tar.gz
                cd psycopg2-2.5.1/
                sudo python setup.py install
                sudo python3 setup.py install
                pip-3.2 list
                -or-
                pip-3.3 list


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

4. Install MySQL.

       # MySQL:

           Note: You can also install mariadb instead of mysql : sudo apt-get install mariadb-server mariadb-client libmysqlclient-dev

                sudo apt-get install mysql-server mysql-client libmysqlclient-dev

                If you are running MySQL not as root user, you will need to run this in MySQL shell (with the single quotes):
                GRANT FILE ON *.* TO 'YourMySQLUsername'@'YourMySQLServerIPAddress';

           Note: my.cnf requires these changes:

                max_allowed_packet=12582912
                group_concat_max_len=8192

                Get your timezone from here : https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
                default_time_zone=Africa/Abidjan

5. Install and configure Apache.

       # Install apache:

                sudo apt-get install apache2

       # Configure PHP CLI ini file using the nano text editor:

                sudo nano /etc/php5/cli/php.ini

       # Change the following settings:

           Note: To search in nano, use control+w

                max_execution_time = 120

           Note: You can set 1024M to -1 if you have RAM to spare.

                memory_limit = 1024M

           Note: Change Europe/London to your local timezone, see here for a list: http://php.net/manual/en/timezones.php

           Note: remove the ; if there is one preceding date.timezone

                date.timezone = YourLocalTimezone

       # Press control+x when you are done to save and exit.

       # Configure the PHP apache2 ini file (use the above settings):

                sudo nano /etc/php5/apache2/php.ini

       # Use the following settings if using Apache 2.2 as your webserver (SEE LOWER FOR APACHE 2.4):

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

6.  Install Unrar/FFmpeg|Avconv/Mediainfo/Lame

                 sudo apt-get install software-properties-common
                 sudo apt-get install unrar python-software-properties

      # Mediainfo

          Note: Ubuntu 14.04 comes with a recent version of mediainfo : sudo apt-get install mediainfo

          Note: On older versions of ubuntu, you can manually install it (look at the URL on your browser for the latest version):

                 wget http://mediaarea.net/download/binary/libzen0/0.4.29/libzen0_0.4.29-1_amd64.xUbuntu_13.10.deb
                 wget http://mediaarea.net/download/binary/libmediainfo0/0.7.67/libmediainfo0_0.7.67-1_amd64.xUbuntu_13.10.deb
                 wget http://mediaarea.net/download/binary/mediainfo/0.7.67/mediainfo_0.7.67-1_amd64.Debian_7.0.deb
                 dpkg -i libzen0_0.4.29-1_amd64.xUbuntu_13.10.deb
                 dpkg -i libmediainfo0_0.7.67-1_amd64.xUbuntu_13.10.deb
                 dpkg -i mediainfo_0.7.67-1_amd64.Debian_7.0.deb


      $ Lame

          sudo apt-get install lame

      # FFmpeg or Avconv:

          Note: You can compile the latest ffmpeg using this script:

              https://github.com/jonnyboy/installers/blob/master/compile_ffmpeg.sh

          Note: You can alternatively install avconv:

              sudo apt-get install libav-tools

              Note: Type which abconv to get the path (should be /usr/bin/avconv)


7. Install memcache / apc.

      # APC:

              sudo apt-get install php-apc
              sudo service apache2 restart
              sudo cp /usr/share/doc/php5-apcu/apc.php /var/www/nZEDb/www/admin

          Note: In the future you can go to localhost/admin/apc.php in your browser to view apc stats.

     # Memcache:

              sudo apt-get install memcached php5-memcache

          Note: AFTER git cloning and seting up the indexer (step 8 & 9), edit config.php and change MEMCACHE_ENABLED to true.

              sudo nano /var/www/nZEDb/www/config.php

8. Git clone the nZEDb source.

      # If /var/www/ does not exist, create it.

              mkdir -p /var/www/
              cd /var/www/
              sudo chmod 777 .

      # Install git.

              sudo apt-get install git

      # Clone the git.

              git clone https://github.com/nZEDb/nZEDb.git

      # Set the permissions.

          Note: During the install (step 9 of this guide) you can set perms to 777 to make things easier:

              sudo chmod -R 777 /var/www/nZEDb
              cd nZEDb

          Note: After install (step 9 of this guide) you can properly set your permissions:
          Note: YourUnixUserName is the user you use in CLI. You can find this by typing : echo $USER

              sudo chown -R YourUnixUserName:www-data /var/www/nZEDb
              sudo usermod -a -G www-data YourUnixUserName
              sudo chmod -R 774 /var/www/nZEDb

9. Run the installer from an internet browser.

         Note: Change localhost for the server's IP if you are browsing on another computer.
         Note: You can find your server's IP by typing ifconfig and looking for inet addr:192.168.x.x under the wlan0 or eth0 sections.

               http://localhost/install

         Note: If you have issues with stage 2, make sure you have set the right permissions on the tsv files,
               mysql has FILE access for the user and apparmor is disabled and you have rebooted after disabling it.

10. Configure the site.

      Enable some groups in view groups.

      Change settings in edit site (set api keys, set paths to unrar etc..)

11. Start indexing groups.

      Use scripts in misc/update (update_binaries to get article headers, update_releases to create releases).

      Use scripts in misc/update/nix to automate it.