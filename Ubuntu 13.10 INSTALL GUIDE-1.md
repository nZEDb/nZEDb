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
                If after using easy_install, it still shows error, this link was current at the time this was posted: http://initd.org/psycopg/install/
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
                
       # Ubuntu 13.10 (ondrej as not added saucy yet, so you will have to use raring)
                
                
                sudo nano /etc/apt/sources.list.d/ondrej-php5-saucy.list  
                        
                        
       # Change the words called saucy to raring (at the end left of main)  
                        
                        
                sudo apt-get update  
                

       # Install PHP and the required extensions:    
        
        
                sudo apt-get install -y php5 php5-dev php5-json php-pear php5-gd php5-mysql php5-curl 
                




