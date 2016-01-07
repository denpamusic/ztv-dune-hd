#!/bin/sh

app_name='Z-TV'
app_manifest=dune_plugin.xml
update_manifest=update_info.xml
source_dir=/cygdrive/d/Projects/Z-TV/src
target_dir=/cygdrive/d/Projects/Z-TV/update

PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
export PATH

if [ "$#" != 1 ]; then
    echo "usage: $0 <version-index>"
    exit 1
fi

cd $source_dir
cp $app_manifest.template $app_manifest

printf "Generating version info...\n\n"
version=`date +%y%m%d_%H%M`
version_index=$1

printf "Generating $app_manifest...\n\n"
version_line=$(awk '/%version%/{print NR}' $app_manifest)
version_index_line=$(awk '/%versionindex%/{print NR}' $app_manifest)
sed -i "$version_line s/%version%/$version/" $app_manifest
sed -i "$version_index_line s/%versionindex%/$version_index/" $app_manifest

printf "Creating update archive...\n\n"
tar -cvzf $version_index.tgz --exclude=*.template *  > /dev/null
7za a -tzip dune_plugin_ztv_$version.zip -x!*.template -x!*.tgz -y * > /dev/null

printf "Calculating md5 and byte size...\n\n"
md5=`md5sum $version_index.tgz | cut -d" " -f1`
bytesize=`wc -c $version_index.tgz | cut -d" " -f1`
printf "===================>Version Info<===================\n"
printf "name..............: $app_name\n"
printf "version_index.....: $version_index\n"
printf "version...........: $version\n"
printf "net_name..........: $version_index.tgz\n"
printf "net_md5...........: $md5\n"
printf "net_size..........: $bytesize bytes\n"
printf "standalone........: dune_plugin_ztv_$version.zip\n"
printf "====================================================\n\n"

printf "Moving update to $target_dir...\n\n"
cp -f $version_index.tgz $target_dir/$version_index.tgz
cp -f dune_plugin_ztv_$version.zip $target_dir/dune_plugin_ztv_$version.zip

printf "Cleaning up...\n\n"
rm $version_index.tgz
rm dune_plugin_ztv_$version.zip
rm $app_manifest

printf "Generating $update_manifest...\n\n"
cd $target_dir
printf "\t<plugin_version_descriptor>\n" > $update_manifest
printf "\t\t<version_index>$version_index</version_index>\n" >> $update_manifest
printf "\t\t<version>$version</version>\n" >> $update_manifest
printf "\t\t<beta>no</beta>\n" >> $update_manifest
printf "\t\t<critical>no</critical>\n" >> $update_manifest
printf "\t\t<url>http://dev.swrn.net/iptv/ztv/$version_index.tgz</url>\n" >> $update_manifest
printf "\t\t<md5>$md5</md5>\n" >> $update_manifest
printf "\t\t<size>$bytesize</size>\n" >> $update_manifest
printf "\t\t<caption>$app_name</caption>\n" >> $update_manifest
printf "\t</plugin_version_descriptor>" >> $update_manifest

printf "Done!\n"
