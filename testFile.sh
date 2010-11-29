#!/bin/bang
if [ ! -e "$1" ]
then
    errmsg=${1}":File Not Found"
    echo $errmsg;
    exit
fi
if [ -e "$1.bak" ]
then
    mv "$1.bak" "$1"
    exit
fi
php ~/src/phpCleanup/cleanFile.php "$1";
if [ -e "$1.bak" ]
then
    diff "$1.bak" "$1";
fi