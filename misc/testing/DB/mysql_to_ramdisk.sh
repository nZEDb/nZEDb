#!/usr/bin/env bash

#if you truncate, repair, alter, the tables may revert to real location, you will need to monitor
#edit this to allow script to run, be sure of the paths
#the ramdisk needs to be twice the size of the parts table max size, it needs room to copy itself during optimise
#i take no responsibility if this fails and you lose you db
#remove the next 2 lines when you have edited this file properlly
echo "Please edit this script, very carefully!"
exit

# Make sure only root can run our script
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root"
	exit 1
fi

export USER="your username"
export PATH_RAMDISK="/var/ramdisk"
export MYSQL_PATH="/var/lib/mysql/nzedb"
export SQL_BACKUP="/home/$USER/sql_backup"

#get userid for mysql
USERID=`id -u mysql`
GROUPID=`id -g mysql`

ARG_1=$1
if [ $# == 0 ]; then
	echo "To move mysql tables to ramdisk, type: ./mysql_to_ramdisk.sh create"
	echo "To move mysql tables back from ramdisk, type: ./mysql_to_ramdisk.sh delete"
	exit
elif [ $ARG_1 == "create" ]; then
	/etc/init.d/mysql stop

	#create the folder for ramdisk
	if [ ! -d "$PATH_RAMDISK" ]; then
		mkdir -p $PATH_RAMDISK
	fi

	#add automount to fstab
	if ! grep -q '#RAMDISK' "/etc/fstab" ; then
	  echo "" | sudo tee -a /etc/fstab
	  echo "#RAMDISK" | sudo tee -a /etc/fstab
	  echo "tmpfs $PATH_RAMDISK tmpfs rw,uid=$USERID,gid=$GROUPID,nodiratime,size=5G,nr_inodes=10k,mode=0700 0 0" | sudo tee -a /etc/fstab
	fi

	#determine if ramdisk is in fstab
	if [[ ! `mount | grep "$PATH_RAMDISK"` ]]; then
		mount "$PATH_RAMDISK"
	fi

	#create backup folder for original
	if [ ! -d "$SQL_BACKUP" ]; then
		mkdir -p $SQL_BACKUP
	fi

	if [ ! -h "$MYSQL_PATH/parts.frm" ] && [ -f $MYSQL_PATH/parts.frm ]; then
		cp $MYSQL_PATH/parts.frm $SQL_BACKUP/
		mv $MYSQL_PATH/parts.frm $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/parts.frm $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/parts.MYD" ] && [ -f $MYSQL_PATH/parts.MYD ]; then
		cp $MYSQL_PATH/parts.MYD $SQL_BACKUP/
		mv $MYSQL_PATH/parts.MYD $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/parts.MYD $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/parts.MYI" ] && [ -f $MYSQL_PATH/parts.MYI ]; then
		cp $MYSQL_PATH/parts.MYI $SQL_BACKUP/
		mv $MYSQL_PATH/parts.MYI $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/parts.MYI $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/parts.ibd" ] && [ -f $MYSQL_PATH/parts.ibd ]; then
		cp $MYSQL_PATH/parts.ibd $SQL_BACKUP/
		mv $MYSQL_PATH/parts.ibd $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/parts.ibd $MYSQL_PATH/
	fi


	if [ ! -h "$MYSQL_PATH/partrepair.frm" ] && [ -f $MYSQL_PATH/partrepair.frm ]; then
		cp $MYSQL_PATH/partrepair.frm $SQL_BACKUP/
		mv $MYSQL_PATH/partrepair.frm $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/partrepair.frm $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/partrepair.MYD" ] && [ -f $MYSQL_PATH/partrepair.MYD ]; then
		cp $MYSQL_PATH/partrepair.MYD $SQL_BACKUP/
		mv $MYSQL_PATH/partrepair.MYD $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/partrepair.MYD $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/partrepair.MYI" ] && [ -f $MYSQL_PATH/partrepair.MYI ]; then
		cp $MYSQL_PATH/partrepair.MYI $SQL_BACKUP/
		mv $MYSQL_PATH/partrepair.MYI $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/partrepair.MYI $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/partrepair.ibd" ] && [ -f $MYSQL_PATH/partrepair.ibd ]; then
		cp $MYSQL_PATH/partrepair.ibd $SQL_BACKUP/
		mv $MYSQL_PATH/partrepair.ibd $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/partrepair.ibd $MYSQL_PATH/
	fi


	if [ ! -h "$MYSQL_PATH/binaries.frm" ] && [ -f $MYSQL_PATH/binaries.frm ]; then
		cp $MYSQL_PATH/binaries.frm $SQL_BACKUP/
		mv $MYSQL_PATH/binaries.frm $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/binaries.frm $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/binaries.MYD" ] && [ -f $MYSQL_PATH/binaries.MYD ]; then
		cp $MYSQL_PATH/binaries.MYD $SQL_BACKUP/
		mv $MYSQL_PATH/binaries.MYD $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/binaries.MYD $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/binaries.MYI" ] && [ -f $MYSQL_PATH/binaries.MYI ]; then
		cp $MYSQL_PATH/binaries.MYI $SQL_BACKUP/
		mv $MYSQL_PATH/binaries.MYI $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/binaries.MYI $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/binaries.ibd" ] && [ -f $MYSQL_PATH/binaries.ibd ]; then
		cp $MYSQL_PATH/binaries.ibd $SQL_BACKUP/
		mv $MYSQL_PATH/binaries.ibd $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/binaries.ibd $MYSQL_PATH/
	fi

	if [ ! -h "$MYSQL_PATH/collections.frm" ] && [ -f $MYSQL_PATH/collections.frm ]; then
		cp $MYSQL_PATH/collections.frm $SQL_BACKUP/
		mv $MYSQL_PATH/collections.frm $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/collections.frm $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/collections.MYD" ] && [ -f $MYSQL_PATH/collections.MYD ]; then
		cp $MYSQL_PATH/collections.MYD $SQL_BACKUP/
		mv $MYSQL_PATH/collections.MYD $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/collections.MYD $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/collections.MYI" ] && [ -f $MYSQL_PATH/collections.MYI ]; then
		cp $MYSQL_PATH/collections.MYI $SQL_BACKUP/
		mv $MYSQL_PATH/collections.MYI $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/collections.MYI $MYSQL_PATH/
	fi
	if [ ! -h "$MYSQL_PATH/collections.ibd" ] && [ -f $MYSQL_PATH/collections.ibd ]; then
		cp $MYSQL_PATH/collections.ibd $SQL_BACKUP/
		mv $MYSQL_PATH/collections.ibd $PATH_RAMDISK/
		ln -s $PATH_RAMDISK/collections.ibd $MYSQL_PATH/
	fi

	chown -R $USER:$USER $SQL_BACKUP/
	chown -R mysql:mysql $PATH_RAMDISK
	chown -R mysql:mysql $MYSQL_PATH

	/etc/init.d/mysql start
elif [ $ARG_1 == "delete" ]; then
	/etc/init.d/mysql stop

	if [ -h "$MYSQL_PATH/parts.frm" ] && [ -f $PATH_RAMDISK/parts.frm ]; then
		rm $MYSQL_PATH/parts.frm
		mv $PATH_RAMDISK/parts.frm $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/parts.MYD" ] && [ -f $PATH_RAMDISK/parts.MYD ]; then
		rm $MYSQL_PATH/parts.MYD
		mv $PATH_RAMDISK/parts.MYD $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/parts.MYI" ] && [ -f $PATH_RAMDISK/parts.MYI ]; then
		rm $MYSQL_PATH/parts.MYI
		mv $PATH_RAMDISK/parts.MYI $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/parts.ibd" ] && [ -f $PATH_RAMDISK/parts.ibd ]; then
		rm $MYSQL_PATH/parts.ibd
		mv $PATH_RAMDISK/parts.ibd $MYSQL_PATH/
	fi


	if [ -h "$MYSQL_PATH/partrepair.frm" ] && [ -f $PATH_RAMDISK/partrepair.frm ]; then
		rm $MYSQL_PATH/partrepair.frm
		mv $PATH_RAMDISK/partrepair.frm $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/partrepair.MYD" ] && [ -f $PATH_RAMDISK/partrepair.MYD ]; then
		rm $MYSQL_PATH/partrepair.MYD
		mv $PATH_RAMDISK/partrepair.MYD $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/partrepair.MYI" ] && [ -f $PATH_RAMDISK/partrepair.MYI ]; then
		rm $MYSQL_PATH/partrepair.MYI
		mv $PATH_RAMDISK/partrepair.MYI $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/partrepair.ibd" ] && [ -f $PATH_RAMDISK/partrepair.ibd ]; then
		rm $MYSQL_PATH/partrepair.ibd
		mv $PATH_RAMDISK/partrepair.ibd $MYSQL_PATH/
	fi


	if [ -h "$MYSQL_PATH/binaries.frm" ] && [ -f $PATH_RAMDISK/binaries.frm ]; then
		rm $MYSQL_PATH/binaries.frm
		mv $PATH_RAMDISK/binaries.frm $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/binaries.MYD" ] && [ -f $PATH_RAMDISK/binaries.MYD ]; then
		rm $MYSQL_PATH/binaries.MYD
		mv $PATH_RAMDISK/binaries.MYD $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/binaries.MYI" ] && [ -f $PATH_RAMDISK/binaries.MYI ]; then
		rm $MYSQL_PATH/binaries.MYI
		mv $PATH_RAMDISK/binaries.MYI $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/binaries.ibd" ] && [ -f $PATH_RAMDISK/binaries.ibd ]; then
		rm $MYSQL_PATH/binaries.ibd
		mv $PATH_RAMDISK/binaries.ibd $MYSQL_PATH/
	fi


	if [ -h "$MYSQL_PATH/collections.frm" ] && [ -f $PATH_RAMDISK/collections.frm ]; then
		rm $MYSQL_PATH/collections.frm
		mv $PATH_RAMDISK/collections.frm $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/collections.MYD" ] && [ -f $PATH_RAMDISK/collections.MYD ]; then
		rm $MYSQL_PATH/collections.MYD
		mv $PATH_RAMDISK/collections.MYD $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/collections.MYI" ] && [ -f $PATH_RAMDISK/collections.MYI ]; then
		rm $MYSQL_PATH/collections.MYI
		mv $PATH_RAMDISK/collections.MYI $MYSQL_PATH/
	fi
	if [ -h "$MYSQL_PATH/collections.ibd" ] && [ -f $PATH_RAMDISK/collections.ibd ]; then
		rm $MYSQL_PATH/collections.ibd
		mv $PATH_RAMDISK/collections.ibd $MYSQL_PATH/
	fi

	chown -R mysql:mysql $MYSQL_PATH

	#determine if ramdisk is in fstab
	if [[ `mount | grep "$PATH_RAMDISK"` ]]; then
		umount "$PATH_RAMDISK"
	fi

	/etc/init.d/mysql start
	exit
else
	echo "To move mysql tables to ramdisk, type: ./mysql_to_ramdisk.sh create"
	exit
fi
