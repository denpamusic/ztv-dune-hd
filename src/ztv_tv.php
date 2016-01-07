<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/hashed_array.php';
require_once 'lib/tv/abstract_tv.php';
require_once 'lib/tv/default_epg_item.php';
require_once 'lib/utils.php';

require_once 'ztv_channel.php';

///////////////////////////////////////////////////////////////////////////

class ZTVTv extends AbstractTv
{
	///////////////////////////////////////////////////////////////////////

	public function __construct()
	{
		parent::__construct(
			AbstractTv::MODE_CHANNELS_N_TO_M,
			ZTVConfig::TV_FAVORITES_SUPPORTED,
			true);
	}

	///////////////////////////////////////////////////////////////////////

	public function get_fav_icon_url()
	{
		return ZTVConfig::FAV_CHANNEL_GROUP_ICON_PATH;
	}
	
	///////////////////////////////////////////////////////////////////////
	
	protected function group_items_filter($item)
	{
		return !is_null( $this->channels->get($item) );
	}

	///////////////////////////////////////////////////////////////////////

	public function unload_channels()
	{
		$this->channels = null;
		$this->groups = null;
	}

	///////////////////////////////////////////////////////////////////////

	protected function load_channels(&$plugin_cookies)
	{
		if( !ZTVTerminal::login() ) {
			hd_print('Error: login_terminal');
			return;
		}

		$json_reply = ZTVApi::call( ZTVApi::get_playlists() );
		if($json_reply === false) {
			hd_print('Error: get_playlists');
			return;
		}

		$this->channels = new HashedArray();
		$this->groups = new HashedArray();

		if( $this->is_favorites_supported() ) {
			$this->groups->put(
				new FavoritesGroup(
					$this,
					'__favorites',
					ZTVConfig::FAV_CHANNEL_GROUP_CAPTION,
					ZTVConfig::FAV_CHANNEL_GROUP_ICON_PATH
				)
			);
		}

		$this->groups->put(
			new AllChannelsGroup(
				$this,
				ZTVConfig::ALL_CHANNEL_GROUP_CAPTION,
				ZTVConfig::ALL_CHANNEL_GROUP_ICON_PATH
			)
		);

		$now = new DateTime('now');
		$use_proxy = isset($plugin_cookies->use_proxy) ? ($plugin_cookies->use_proxy == 'yes') : false;

		foreach($json_reply->result->medialist as $number => $media) {
			$mrl = ($use_proxy) ?
				str_replace(
					'udp://@',
					'http://' . $plugin_cookies->proxy_ip . ':' . $plugin_cookies->proxy_port . '/udp/',
					$media->mrl
				) : $media->mrl;

			$epg_start = ZTVApi::parse_datetime($media->epg_start);
			$epg_stop = ZTVApi::parse_datetime($media->epg_stop);

			if( is_null($epg_start) || is_null($epg_stop) ) {
				$past_epg_days = 0;
				$future_epg_days = 0;
			} else {
				$epg_start_diff = $now->diff($epg_start);
				$epg_stop_diff = $now->diff($epg_stop);
				$past_epg_days = $epg_start_diff->days;
				$future_epg_days = $epg_stop_diff->days;
			}

			$channel = new ZTVChannel(
				$media->id,
				$media->name,
				$media->logo == null ? 'plugin_file://icons/tv-missing.png' : $media->logo,
				$mrl,
				$number+1,
				$past_epg_days,
				$future_epg_days
			);

			$this->channels->put($channel);
		}

		foreach($json_reply->result->playlists as $category) {
			/* Remove any nonexistent channels from the group. */
			$items = array_filter( $category->items, array($this, 'group_items_filter') );
			if( empty($items) ) {
				continue;
			}

			$group = new DefaultGroup( $category->id, $category->name, 'plugin_file://icons/' . basename($category->logo) );

			foreach($items as $item_id) {
				$channel = $this->channels->get($item_id);
				$channel->add_group($group);
				$group->add_channel($channel);
			}

			$this->groups->put($group);
		}

		$this->channels->usort(function($a, $b) {
			return strnatcasecmp( $a->get_number(), $b->get_number() );
		});
	}

	///////////////////////////////////////////////////////////////////////

	public function get_day_epg_iterator($channel_id, $day_start_ts, &$plugin_cookies)
	{
		$day_stop_ts = $day_start_ts + 86400;

//		$json_reply = ZTVApi::call( ZTVApi::get_epg($channel_id, 999, $day_start_ts, $day_stop_ts) );
		$epg_provider = isset($plugin_cookies->epg_provider) ? $plugin_cookies->epg_provider : 'ZTVApi';
		$json_reply = $epg_provider::call( $epg_provider::get_epg($channel_id, 999, $day_start_ts, $day_stop_ts) );
		if( $json_reply === false || is_null($json_reply->result->programs) ) {
			hd_print("Warning: null EPG for channel(id: $channel_id)");
			return array();
		}

		$epg = array();
		foreach($json_reply->result->programs as $p) {
			// TODO. Fix dirty hack below. Somehow...
			$start_ts = ZTVApi::parse_datetime( str_replace('+0400', '+0300', $p->start) );
			$stop_ts = ZTVApi::parse_datetime( str_replace('+0400', '+0300', $p->stop) );
			if( is_null($start_ts) || is_null($stop_ts) )
				continue;
			$start_ts = $start_ts->getTimestamp();
			$stop_ts = $stop_ts->getTimestamp();
			if( $start_ts < $day_start_ts || $start_ts > $day_stop_ts )
				continue;
			$p->description = !is_null($p->description) ? $p->description : '';
			$epg[] = new DefaultEpgItem($p->title, $p->description, $start_ts, $stop_ts);
		}

		return new EpgIterator($epg, $day_start_ts, $day_stop_ts);
	}
}

///////////////////////////////////////////////////////////////////////////
?>