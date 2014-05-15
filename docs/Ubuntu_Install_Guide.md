## Most of this guide is done from the command line (terminal).

1. Misc.

       # For those using an older version of ubuntu, php 5.4 is the minimum required version.

       # Apparmor interferes with some of our files, THIS IS MANDATORY.

                You can disable it and remove it:

                sudo /etc/init.d/apparmor stop
                sudo /etc/init.d/apparmor teardown
                sudo update-rc.d -f apparmor remove

                Or, you can make it ignore mysql:

                sudo apt-get install apparmor-utils
                sudo aa-complain /usr/sbin/mysqld

            Note: You must reboot after doing this to take effect.


       # For the threaded scripts you will require the Python cymysql module for mysql:

       # Note: For Ubuntu 13.10, python3 uses pip3, not pip3.2

       # Python 2.*

                 sudo apt-get install python-setuptools python-pip
                 sudo python -m easy_install pip
                 sudo easy_install cymysql

       # Python 3.* - If Python 3 is installed, the module also must be installed

                 sudo apt-get install python3-setuptools
                 sudo python3 -m easy_install pip
                 sudo pip3 install cymysql


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

       # (OPTIONAL) Add a repository to get (most recent versions of) apache 2.4 and php 5.5

                sudo apt-get install software-properties-common
                sudo add-apt-repository ppa:ondrej/php5
                sudo apt-get update


       # Install PHP and the required extensions:

                sudo apt-get install php5 php5-dev php5-json php-pear php5-gd php5-mysqlnd php5-curl

4. Install a MySQL client/server, pick from one of these 3.

           Note: MySQL is a relational database system, developed by Oracle.

                sudo apt-get install mysql-server mysql-client libmysqlclient-dev

           Note: MariaDB is a fork of MySQL, it is regarded as having more performance than MySQL.
                 Read more : https://mariadb.com/kb/en/mariadb-versus-mysql-compatibility/

                sudo apt-get install mariadb-server mariadb-client libmysqlclient-dev

           Note: Percona is also a fork of MySQL, regarded also as having more performance than MySQL.
                 Read more : http://www.percona.com/software/percona-server/feature-comparison

                sudo apt-key adv --keyserver keys.gnupg.net --recv-keys 1C4CBDCDCD2EFD2A
                sudo nano /etc/apt/sources.list.d/percona.list

                Note: Paste the deb and deb-src lines, replacing VERSION with the name of your ubuntu: (12.04: precise,
                      12.10: quantal, 13.04: raring, 13.10: saucy, 14.04: trusty, 14.10: utopic).

                deb http://repo.percona.com/apt VERSION main
                deb-src http://repo.percona.com/apt VERSION main

                Note: Exit and save nano (press control+x, then type y and press Enter).

                sudo apt-get update
                sudo apt-get install percona-server-server-5.5 percona-server-client-5.5 libmysqlclient-dev


       # Change my.cnf using the nano text editor.

           Note: To search in nano, use control+w

                sudo nano /etc/mysql/my.cnf

           Note: Change or add (if they are missing) the following values to the [mysqld] section:

                max_allowed_packet = 16M
                group_concat_max_len = 8192

           Note: To save in nano, press control+x then type y and press enter.

       # File permissions:

           Note: You must log in to MySQL to give your user file permissions (even if it is the root user).
                 YourMySQLUsername is the MySQL user you will use for nZEDb (you can use root, or if you created a user, use that one).
                 YourMySQLServerIPAddress is the address to the MySQL server (if you are local, localhost or 127.0.0.1 will work).

                sudo mysql -p
                GRANT FILE ON *.* TO 'YourMySQLUsername'@'YourMySQLServerIPAddress';

           Note: Type \q and press enter to exit the MySQL command line.

5. Install and configure Apache.

       # Install apache:

                sudo apt-get install apache2

       # Configure PHP CLI ini file:

                sudo nano /etc/php5/cli/php.ini

       # Change the following settings:

                max_execution_time = 120

           Note: You can set 1024M to -1 if you have RAM to spare.

                memory_limit = 1024M

           Note: Change Europe/London to your local timezone, see here for a list: http://php.net/manual/en/timezones.php

           Note: remove the ; if there is one preceding date.timezone

                date.timezone = YourLocalTimezone

       # Configure the PHP apache2 ini file (use the above settings):

                sudo nano /etc/php5/apache2/php.ini

       # APACHE 2.4:
       # Note: IF YOU ARE USING APACHE 2.2 SCROLL DOWN.

       # Create the site config:

                sudo nano /etc/apache2/sites-available/nZEDb.conf

       # Paste the following (change to suit your needs):

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
                        Require all granted
                    </Directory>
                    Alias /covers /var/www/nZEDb/resources/covers
                </VirtualHost>

       # Save and exit nano.

       # Edit the apache2 config file to allow all overrides on the /var/www directory:

                 sudo nano /etc/apache2/apache2.conf

                 Under <Directory /var/www/>, change AllowOverride None to AllowOverride All

       # Disable the default site, enable nZEDb, enable rewrite, restart apache:

                 sudo a2dissite 00-default
                 sudo a2dissite 000-default
                 sudo a2ensite nZEDb.conf
                 sudo a2enmod rewrite
                 sudo service apache2 restart

       # APACHE 2.2:

       # Create the site config:

                 sudo nano /etc/apache2/sites-available/nZEDb

       # Paste the following (change to suit your needs):

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
                     Alias /covers /var/www/nZEDb/resources/covers
                 </VirtualHost>

       # Disable the default site, enable nZEDb, enable rewrite, restart apache:

                 sudo a2dissite default
                 sudo a2ensite nZEDb
                 sudo a2enmod rewrite
                 sudo service apache2 restart

6.  Install Extras

          Note: These 2 applications might be needed to add repositories.

                 sudo apt-get install software-properties-common
                 sudo apt-get install python-software-properties

      # Unrar

          sudo apt-get install unrar

          Note: The unrar installed above is old (version 4), you can install the newest manually.
                You can check your current version of unrar by typing unrar and looking at the first line of the output.
                Head to http://www.rarlab.com/download.htm
                Look for the latest unrar for linux (currently RAR 5.10 beta 4 for Linux x64), right click on
                it and click copy link. You can replace the link with the one I have below.

          Note: Replace the old unrar with the new one:

                 sudo mv /usr/bin/unrar /usr/bin/unrar4
                 mkdir -p ~/new_unrar
                 cd ~/new_unrar
                 wget http://www.rarlab.com/rar/rarlinux-x64-5.1.b4.tar.gz
                 tar -xzf rarlinux*.tar.gz
                 sudo mv rar/unrar /usr/bin/unrar
                 sudo chmod 755 /usr/bin/unrar
                 cd ~/
                 rm -rf ~/new_unrar

      # Mediainfo

          Note: Ubuntu 14.04 comes with a recent version of MediaInfo (0.7.67 as of this writing) : sudo apt-get install mediainfo

          Note: On older versions of Ubuntu, you can manually install it, download the deb files from here :

                http://mediaarea.net/en/MediaInfo/Download/Ubuntu
                You need libmediainfo, libzen0 and mediainfo (CLI).
                Once you have downloaded them (wget http://link_to_file), use sudo dpkg -i name_of_the_file.deb to install it.

      # Lame

          sudo apt-get install lame

      # FFmpeg or Avconv:

          Note: On newer versions of ubuntu you can install avconv:

              sudo apt-get install libav-tools

              Note: Type which avconv to get the path (should be /usr/bin/avconv), you can use this in site edit later on.

          Note: On older versions of ubuntu you can manually compile ffmpeg:

              (manual compilation) https://trac.ffmpeg.org/wiki/CompilationGuide/Ubuntu

              (automated compilation, possibly unmaintained) https://github.com/jonnyboy/installers/blob/master/compile_ffmpeg.sh

      # Memcache:

              sudo apt-get install memcached php5-memcache

          Note: AFTER git cloning and seting up the indexer (step 7 & 8), edit config.php and change MEMCACHE_ENABLED to true.

              sudo nano /var/www/nZEDb/www/config.php

      # yEnc:

          Note: You have 3 choices,
                you can install simple_php_yenc_decode which offers the best performance,
                installing yydecode, which offers good performance,
                or using PHP (no install required) which is very slow.
                You can change these at any time if you have issues with any of the 3.

          simple_php_yenc_decode:

               sudo apt-get install git
               cd ~/
               git clone https://github.com/kevinlekiller/simple_php_yenc_decode
               cd simple_php_yenc_decode/
               sh ubuntu.sh
               cd ~/
               rm -rf simple_php_yenc_decode/

          yydecode

               cd ~/
               mkdir -p yydecode
               cd yydecode/
               wget http://colocrossing.dl.sourceforge.net/project/yydecode/yydecode/0.2.10/yydecode-0.2.10.tar.gz
               tar -xzf yydecode-0.2.10.tar.gz
               cd yydecode-0.2.10/
               ./configure
               make
               sudo make install
               make clean
               cd ~/
               rm -rf yydecode/

          Note: After installing you can change the yEnc setting in site edit accordingly.

7. Git clone the nZEDb source.

      # Install git.

              sudo apt-get install git

      # Clone the git.

              mkdir -p /var/www/
              cd /var/www/
              sudo git clone https://github.com/nZEDb/nZEDb.git

      # Set the permissions.

          Note: During the install (step 8 of this guide) you can set perms to 777 to make things easier:

              sudo chmod -R 777 /var/www/nZEDb
              cd nZEDb

          Note: After install (step 8 of this guide) you can properly set your permissions:
          Note: YourUnixUserName is the user you use in CLI. You can find this by typing : echo $USER

              sudo chown -R YourUnixUserName:www-data /var/www/nZEDb
              sudo usermod -a -G www-data YourUnixUserName
              sudo chmod -R 774 /var/www/nZEDb

8. Run the installer from an internet browser.

         Note: Change localhost for the server's IP if you are browsing on another computer.
         Note: You can find your server's IP by typing ifconfig and looking for inet addr:192.168.x.x under the wlan0 or eth0 sections.

               http://localhost/install

         Note: If you have issues with stage 2, make sure you have set the right permissions on the tsv files,
               mysql has FILE access for the user and apparmor is disabled and you have rebooted after disabling it.

9. Configure the site.

      Enable some groups in view groups.

      Change settings in edit site (set api keys, set paths to unrar etc..)

10. Start indexing groups.

      Use scripts in misc/update (update_binaries to get article headers, update_releases to create releases).

      Use scripts in misc/update/nix to automate it.
