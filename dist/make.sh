#!/bin/bash
set -e

version="2.3.1"
mode="installable"
filename="zaius_engage-magento2-$version.tgz"

while [[ $# -gt 0 ]]
do
    key="$1"
    case $key in
        -f|--flat)
            mode="flat"
        ;;
        -i|--installable)
            mode="installable"
        ;;
        -h|--help)
            echo Usage: make.sh [OPTIONS] [FILENAME]
            echo [OPTIONS]:
            echo "     -f|--flat        Creates a flat archive"
            echo "     -i|--installable Creates an archive ready to be installed (default)"
            echo "     -h|--help        Displays this information"
            echo "[FILENAME]: The filename to place the archive in"
            exit
        ;;
        *)
            filename=$key
        ;;
    esac
    shift # past argument or value
done

if [ "$mode" = "flat" ]; then
    mkdir -p tmp
    cp -r ../src/* tmp
else
    mkdir -p tmp/app/code/Zaius/Engage
    cp -r ../src/* tmp/app/code/Zaius/Engage
fi

cd tmp
tar -zcvf ../$filename *
cd ..
rm -rf tmp
echo "Archive saved as $filename"
