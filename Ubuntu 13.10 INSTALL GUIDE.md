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
                
       # Or PostgreSQL:  
       
                sudo apt-get install postgresql php5-pgsql 
                
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
       
                date.timezone = Europe/London  
                
       # Press control+x when you are done to save and exit.  
       
       # Configure the PHP apache2 ini file (use the above settings):  
       
                sudo nano /etc/php5/apache2/php.ini  
                
       # Use the following setting if using Apache 2.2 as your webserver:  
       
       # Create the site config:  
       
                sudo nano /etc/apache2/sites-available/nZEDb
                
       # Paste the following:  
       
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
       
                 


            
                
                
                


                


                




