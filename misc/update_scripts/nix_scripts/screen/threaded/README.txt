start.sh runs 3 gnu screens, 1 for updating usenet articles and optimizing_db, 1 for post processing, 1 for updating releases.

Edit the paths to your nZEDb location in start.sh

Run like this -> screen sh start.sh

Detach from the screen : control+a  d

Attach to another screen, screen -x POSTP (postprocessing), screen -x RELEASES (update releases).

To reatach to the first screen type screen -x , you will get a list of the 3 screens, you will see numbers, screen -x number  (change number for one of the numbers in the list).
