<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/default_dune_plugin.php';
require_once 'lib/utils.php';

require_once 'lib/tv/tv_channel_list_screen.php';
require_once 'lib/tv/tv_group_list_screen.php';
require_once 'lib/tv/tv_favorites_screen.php';

require_once 'ztv_config.php';

require_once 'ztv_tv.php';
require_once 'ztv_api.php';
require_once 'ztv_terminal.php';
require_once 'ztv_setup_screen.php';

require_once 'swrn_api.php';

///////////////////////////////////////////////////////////////////////////

class ZTVPlugin extends DefaultDunePlugin
{
	public function __construct()
	{
		$this->tv = new ZTVTv();

		$this->add_screen(new TvGroupListScreen($this->tv,
				ZTVConfig::GET_TV_GROUP_LIST_FOLDER_VIEWS()));
		$this->add_screen(new TvChannelListScreen($this->tv,
				ZTVConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));
		$this->add_screen(new TvFavoritesScreen($this->tv,
				ZTVConfig::GET_TV_CHANNEL_LIST_FOLDER_VIEWS()));

		$this->entry_handler = new ZTVSetupScreen();
		$this->add_screen($this->entry_handler);
		UserInputHandlerRegistry::get_instance()->register_handler($this->entry_handler);
	}
}

///////////////////////////////////////////////////////////////////////////
?>