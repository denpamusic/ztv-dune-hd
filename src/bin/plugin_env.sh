# this script should be sourced from another shell script
# the caller script should provide plugin_install_dir variable
plugin_name=`basename "$plugin_install_dir"`
plugin_tmp_dir="/tmp/plugins/$plugin_name"
plugin_persistfs_data_dir="/persistfs/plugins_data/$plugin_name"
plugin_flashdata_data_dir="/flashdata/plugins_data/$plugin_name"
plugin_config_data_dir="/config/plugins_data/$plugin_name"
if [ -d "$plugin_persistfs_data_dir" ]; then              
    plugin_data_dir="$plugin_persistfs_data_dir"
elif [ -d "$plugin_flashdata_data_dir" ]; then            
    plugin_data_dir="$plugin_flashdata_data_dir"
else                          
    plugin_data_dir="$plugin_config_data_dir"
fi
# Ensure plugin subsystem is initialized and wait for it if needed.
while [ ! -e "$plugin_tmp_dir" -o ! -e "$plugin_data_dir" ]; do sleep 1; done
