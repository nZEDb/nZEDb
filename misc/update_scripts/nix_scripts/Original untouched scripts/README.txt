Inlcuded in this folder are init.d scripts for unix users. These are NOT to be cronned. 
They must be ran by init.d or in a screen.

You MUST set the paths to where you have installed newznab.

The recommended way to run script is via screen. You should copy newznab_screen.sh 
to newznab_local.sh so that when you svn update (or export), your changes are not lost.

Detailed instructions...

cp newznab_screen.sh newznab_local.sh
edit newznab_local.sh to specify paths to your installation
chmod +x newznab_local.sh
screen bash
./newznab_local.sh
ctrl-ad to detach screen



This will update binaries and releases in a continuous cycle.