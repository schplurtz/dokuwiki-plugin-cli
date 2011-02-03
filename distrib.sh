#!/bin/bash

# make distributable version of plugin
DOKUWIKI=~/Sites/dokuwiki

echo "Creating distribution"
git clone ~/dev/plugin-cli /tmp/plugin-cli
cd /tmp
mv plugin-cli cli

tar cvfz cli.tar.gz ./cli
zip -r cli.zip ./cli

echo "Installing files to $DOKUWIKI"
cp /tmp/cli.{tar.gz,zip} $DOKUWIKI/lib/plugins
cp /tmp/cli/cli-plugin.txt $DOKUWIKI/data/pages/plugins/cli.txt
cp /tmp/cli/cli-examples.txt $DOKUWIKI/data/pages/test/cli.txt
dos2unix $DOKUWIKI/data/pages/{test/cli.txt,plugins/cli.txt}

echo "Cleaning up"
rm /tmp/cli.{tar.gz,zip}
rm -rf /tmp/cli



