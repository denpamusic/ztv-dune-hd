<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/jsonrpc.php';

///////////////////////////////////////////////////////////////////////////

class ZTVApi extends JsonRpc
{
	///////////////////////////////////////////////////////////////////////////

	const API_URL = 'http://watch-tv.zet/jsonrpc/';
	const API_DATETIME_FORMAT = 'Y-m-d H:i:sO';

	///////////////////////////////////////////////////////////////////////////

	public static function set_terminal_params(stdClass $profile)
	{
		hd_print("API: method 'set_terminal_params'");
		return HD::make_json_rpc_request('set_terminal_params',
			array(
				'macaddr' => ZTVApi::get_mac_addr(),
				'profile_id' => $profile->id
			)
		);
	}

	///////////////////////////////////////////////////////////////////////////

	public static function get_terminal_params()
	{
		hd_print("API: method 'get_terminal_params'");
		return HD::make_json_rpc_request('get_terminal_params',
			array(
				'macaddr' => ZTVApi::get_mac_addr()
			)
		);
	}

	///////////////////////////////////////////////////////////////////////////

	public static function get_profiles()
	{
		hd_print("API: method 'get_profiles'");
		return HD::make_json_rpc_request('get_profiles',
			array( 'macaddr' => ZTVApi::get_mac_addr() )
		);
	}

	///////////////////////////////////////////////////////////////////////////

	public static function get_playlists()
	{
		hd_print("API: method 'get_playlists'");
		return HD::make_json_rpc_request('get_playlists',
			array(
				'macaddr' => ZTVApi::get_mac_addr(),
				'profile' => true
			)
		);
	}

	///////////////////////////////////////////////////////////////////////////

	public static function get_epg($media_id, $limit, $start, $stop)
	{
		hd_print("API: method 'get_epg'");
		return HD::make_json_rpc_request('get_epg',
			array(
					'media_id' => $media_id,
					'limit' => $limit,
					'start' => ZTVApi::format_timestamp($start),
					'stop' => ZTVApi::format_timestamp($stop)
			)
		);
	}

	///////////////////////////////////////////////////////////////////////////

	public static function set_profile(stdClass $profile, $profile_password = null)
	{
		hd_print("API: method 'set_profile'");
		$query = array(
			ZTVApi::set_terminal_params($profile),
			ZTVApi::get_terminal_params()
		);

		if( $profile->require_password && !is_null($profile_password) ) {
			$query[] = ZTVApi::update_profile($profile, $profile_password);
		}

		return $query;
	}

	///////////////////////////////////////////////////////////////////////////

	public static function update_profile(stdClass $profile, $profile_password)
	{
		hd_print("API: method 'update_profile'");
		return HD::make_json_rpc_request('update_profile',
			array(
				'macaddr' => ZTVApi::get_mac_addr(),
				'profile_id' => $profile->id,
				'profile_password' => $profile_password
			)
		);
	}
}

///////////////////////////////////////////////////////////////////////////
?>