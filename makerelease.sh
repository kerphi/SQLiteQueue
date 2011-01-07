RELEASE=$1
DATE="`date +%Y-%m-%d`"

if [ "$RELEASE" == "" ]
then
    echo "Missing parameter: release number is expected." >&2
    echo "Usage:   `basename $0` [release_number]" >&2
    echo "Example: `basename $0` 1.0.1" >&2
    exit 1
fi

RELEASE_OK=`echo $RELEASE | sed 's/[0-9]\+\.[0-9]\+\.[0-9]\+//g'`
if [ "$RELEASE_OK" != "" ]
then
    echo "$RELEASE is a bad release number format. It should be X.Y.Z where X, Y and Z are numbers." >&2
    exit 1
fi

# modify the release date
sed -i "s/<date>.*<\/date>/<date>$DATE<\/date>/g" package.xml

# modify the release version
sed -i "s/<release>[0-9]\+\.[0-9]\+\.[0-9]\+<\/release>/<release>$RELEASE<\/release>/g" package.xml

# create the pear package (tgz)
pear package package.xml
if [ "$?" != "0" ]
then
    echo "PEAR package build has failed." >&2
    exit 1
fi

# tag the version
git tag -a -m "Version $RELEASE"  $RELEASE
