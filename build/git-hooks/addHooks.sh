#!/usr/bin/env bash

NZEDB=`pwd`
HOOKS=/build/git-hooks
GIT=/.git/hooks
PC=/pre-commit

NZEDB=${NZEDB%${HOOKS}}

echo "${NZEDB}${GIT}"
if [ -x "${NZEDB}${GIT}${PC}" ]
then
	rm "${NZEDB}${GIT}${PC}"
	echo .
fi

ln -s ${NZEDB}${HOOKS}${PC} ${NZEDB}${GIT}${PC}
