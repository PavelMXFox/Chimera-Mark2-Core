#!/bin/bash
XPREFIX=/var/www/html

if [ ! -e /var/log/fox ]
then
    mkdir /var/log/fox
else
    echo exists
fi

chmod a+rw /var/log/fox


cd ${XPREFIX}/fox-start.d
echo "Fox init started."
echo "Initialize core."
find * -maxdepth 1 -type f -exec bash -c "echo Run {} && ./{}" \;

cd ${XPREFIX}/modules
mods=`find * -maxdepth 1 -type d -name fox-start.d | sort`

for mod in ${mods}
do
	echo Module `dirname ${mod}`
	cd ${mod}
	find * -maxdepth 1 -type f -exec bash -c "echo Run {} && ./{}" \;
	cd -  > /dev/null
done