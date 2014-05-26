# This script will check the number of connections to your USP, and display it on the powerline bar in tmux.

if [ -e "nZEDbBase.php" ]
then
    export NZEDB_ROOT="$(pwd)"
elif [ -e "../../../nZEDbBase.php" ]
then
    export NZEDB_ROOT="$(php ../../../nZEDbBase.php)"
elif [ -e "../../../../nZEDbBase.php" ]
then
    export NZEDB_ROOT="$(php ../../../../nZEDbBase.php)"
else
    export NZEDB_ROOT="$(php ../../../../../nZEDbBase.php)"
fi

run_segment() {
    # get USP settings from config.php
    uspsetting=( $(cat ${NZEDB_ROOT}/www/config.php | awk '/NNTP/ && /SERVER|PORT/ {print $2}' | sed 's/);//' | sed "s/'//g") )

    # Get info about primary NNTP connections.
    mainusp=`( dig ${uspsetting} A +short | tail -n1; \
	dig ${uspsetting} AAAA +short | tail -n1 ) \
	| head -n1`
	grepusp=$(echo "${mainusp[@]}" | sed 's/ /|/g')
	maincount=$(ss -n | awk '/ESTAB/ {printf"%s %s\n",$1,$6}' | egrep "$grepusp" | grep -c ${uspsetting[1]})
	tmaincount=$(ss -n | awk '{printf"%s %s\n",$1,$6}' | egrep "$grepusp" | grep -c ${uspsetting[1]})

    # Check to see if have an alt USP set, if so get those connections too.
    if [[ -n "${uspsetting[3]}" ]]; then
		altusp=`( dig ${uspsetting[2]} A +short | tail -n1; \
		dig ${uspsetting[2]} AAAA +short | tail -n1 ) \
		| head -n1`
        grepausp=$(echo "${altusp[@]}" | sed 's/ /|/g')
        altcount=$(ss -n | awk '/ESTAB/ {printf"%s %s\n",$1,$6}' | egrep "$grepausp" | grep -c ${uspsetting[3]})
        taltcount=$(ss -n | awk '{printf"%s %s\n",$1,$6}' | egrep "$grepausp" | grep -c ${uspsetting[3]})

    # Print results to powerline.
        echo "MainUSP: A${maincount} T${tmaincount}, AltUSP: A${altcount} T${taltcount}"
    else
        echo "MainUSP: A${maincount} T${tmaincount}"
    fi

}
