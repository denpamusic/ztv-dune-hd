<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class ZTVSetupScreen extends AbstractControlsScreen
{
	const ID = 'setup';

	///////////////////////////////////////////////////////////////////////

	public function __construct()
	{
		parent::__construct(self::ID);
		$this->profile = null;
		$this->profiles = array();
	}

	///////////////////////////////////////////////////////////////////////

	public function do_get_control_defs(&$plugin_cookies)
	{
		$defs = array();

		$epg_provider = isset($plugin_cookies->epg_provider) ? $plugin_cookies->epg_provider : 'ZTVApi';
		$use_proxy = isset($plugin_cookies->use_proxy) ? $plugin_cookies->use_proxy : 'no';
		$proxy_ip = isset($plugin_cookies->proxy_ip) ? $plugin_cookies->proxy_ip : '192.168.0.1';
		$proxy_port = isset($plugin_cookies->proxy_port) ? $plugin_cookies->proxy_port : '8080';

		$this->add_label($defs, '%tr%caption_ztv', ZTVConfig::PLUGIN_VERSION);
		$this->add_button($defs, 'register_button', null, '%tr%caption_account', 400);

		$profiles = array();
		if($this->profiles && $this->profile) {
			$init_profile = $this->profile->id;
			foreach($this->profiles as $profile) {
				$profiles[$profile->id] = $profile->title;
			}
		} else {
			$init_profile = 1;
			$profiles[$init_profile] = '0+';
		}
		$this->add_combobox($defs, 'profile', '%tr%caption_profile', $init_profile, $profiles, 0, true);

		$show_ops = array(
			'ZTVApi' => 'ZTV',
			'SWRNApi' => 'SWRN'
		);
		$this->add_combobox($defs, 'epg_provider', '%tr%caption_epg_provider', $epg_provider, $show_ops, 0, true);

		$show_ops = array(
			'yes' => '%tr%caption_yes',
			'no' => '%tr%caption_no'
		);
		$this->add_combobox($defs, 'use_proxy', '%tr%caption_use_proxy', $use_proxy, $show_ops, 0, true);
		if($use_proxy == 'yes') {
			$this->add_text_field($defs, 'proxy_ip', '%tr%caption_proxy_ip', $proxy_ip,
									false, false, false, true, 500, false, true);
			$this->add_text_field($defs, 'proxy_port', '%tr%caption_proxy_port', $proxy_port,
									true, false, false,  true, null, false, true);
		}

		$this->add_vgap($defs, 30);
		$this->add_label($defs, '%tr%caption_daemon', ZTVDaemonController::status()->output);
		$this->add_button($defs, 'daemon_start', null, '%tr%caption_daemon_start', 400);
		$this->add_button($defs, 'daemon_restart', null, '%tr%caption_daemon_restart', 400);
		$this->add_button($defs, 'daemon_stop', null, '%tr%caption_daemon_stop', 400);
		return $defs;
	}

	///////////////////////////////////////////////////////////////////////

	private function do_registration_form_defs(&$plugin_cookies)
	{
		$defs = array();

		$username = isset($plugin_cookies->username) ? $plugin_cookies->username : '';
		$password = isset($plugin_cookies->password) ? $plugin_cookies->password : '';

		$this->add_text_field($defs, 'username', '%tr%caption_username', $username,
								true/*numeric*/, false, false, false, 500);
		$this->add_text_field($defs, 'password', '%tr%caption_pin', $password,
								true/*numeric*/, true, false, false, 500);
		$this->add_vgap($defs, 50);
		$this->add_button($defs, 'register_terminal', null, '%tr%caption_terminal_register', 400);
		if( ZTVTerminal::login() ) {
			$this->add_button($defs, 'deregister_terminal', null, '%tr%caption_terminal_deregister', 400);
		}
		$this->add_button($defs, 'register_cancel', null, '%tr%caption_cancel', 400);

		return $defs;
	}

	///////////////////////////////////////////////////////////////////////

	private function do_profile_form_defs(&$plugin_cookies, $form_action = 'check_profile_password')
	{
		$defs = array();

		$this->add_text_field($defs, 'profile_password', '%tr%caption_profile_password', '', false/*numeric*/, true, false, false, 500);
		switch($form_action) {
			case 'check_profile_password':
				break;
			case 'change_profile_password':
				$this->add_text_field($defs, 'profile_password_new', '%tr%caption_profile_new_password', '', false/*numeric*/, true, false, false, 500);
			case 'set_profile_password':
				$this->add_text_field($defs, 'profile_password_repeat', '%tr%caption_profile_password_confirmation', '', false/*numeric*/, true, false, false, 500);
		}

		$this->add_vgap($defs, 50);
		$this->add_button($defs, $form_action, null, '%tr%caption_ok', 400);
		$this->add_button($defs, 'profile_cancel', null, '%tr%caption_cancel', 400);

		return $defs;
	}

	///////////////////////////////////////////////////////////////////////

	public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
	{
		$this->load_profiles();
		return $this->do_get_control_defs($plugin_cookies);
	}

	///////////////////////////////////////////////////////////////////////

	public function load_profiles() {
		if( ZTVTerminal::login() ) {
			$json_reply = ZTVApi::call(
				array(
					ZTVApi::get_terminal_params(),
					ZTVApi::get_profiles()
				)
			);

			if($json_reply) {
				$profile_id = $json_reply[0]->result->profile_id;
				$profiles = $json_reply[1]->result;
				foreach($profiles as $profile) {
					$this->profiles[$profile->id] = $profile;
				}
				$this->profile = $this->profiles[$profile_id ? $profile_id : 1];
				return true;
			}
		}

		return false;
	}

	///////////////////////////////////////////////////////////////////////

	public function handle_user_input(&$user_input, &$plugin_cookies)
	{
		if( !isset($user_input->control_id) ) {
			if($plugin_cookies->require_restart) {
				ZTVDaemonController::restart(
					($plugin_cookies->use_proxy == 'yes') ? array(
						'-y' => $plugin_cookies->proxy_ip . ':' . $plugin_cookies->proxy_port
					) : array()
				);
				$plugin_cookies->require_restart = false;
			}

			if( !ZTVTerminal::login() ) {
				return ActionFactory::show_dialog('%tr%dialog_registration_form', $this->do_registration_form_defs($plugin_cookies), true);
			}

			if( $this->load_profiles() ) {
				return ($this->profile->require_password) ?
					ActionFactory::show_dialog('%ext%<key_local>dialog_profile_password<p>' . $this->profile->title . '</p></key_local>', $this->do_profile_form_defs($plugin_cookies), true)
					: ActionFactory::open_folder();
			}
			return;
		} else if($user_input->action_type == 'confirm' || $user_input->action_type == 'apply' ) {
			switch($user_input->control_id) {
				case 'epg_provider':
					$plugin_cookies->epg_provider = $user_input->epg_provider;
					break;
				case 'use_proxy':
					$plugin_cookies->use_proxy = $user_input->use_proxy;
					$plugin_cookies->proxy_ip = isset($plugin_cookies->proxy_ip) ? $plugin_cookies->proxy_ip : '192.168.0.1';
					$plugin_cookies->proxy_port = isset($plugin_cookies->proxy_port) ? $plugin_cookies->proxy_port : '8080';
					$plugin_cookies->require_restart = true;
					break;
				case 'proxy_ip':
					$plugin_cookies->proxy_ip = $user_input->proxy_ip;
					$plugin_cookies->require_restart = true;
					break;
				case 'proxy_port':
					$plugin_cookies->proxy_port = $user_input->proxy_port;
					$plugin_cookies->require_restart = true;
					break;
				case 'daemon_start':
					ZTVDaemonController::start(
						($plugin_cookies->use_proxy == 'yes') ? array(
							'-y' => $plugin_cookies->proxy_ip . ':' . $plugin_cookies->proxy_port
						) : array()
					);
					break;
				case 'daemon_restart':
					ZTVDaemonController::restart(
						($plugin_cookies->use_proxy == 'yes') ? array(
							'-y' => $plugin_cookies->proxy_ip . ':' . $plugin_cookies->proxy_port
						) : array()
					);
					break;
				case 'daemon_stop':
					ZTVDaemonController::stop();
					break;
				case 'register_button':
					return ActionFactory::show_dialog(
						'%tr%dialog_registration_form',
						 $this->do_registration_form_defs($plugin_cookies),
						 true
					);
					break;
				case 'register_terminal':
					if(is_null($user_input->username) || trim($user_input->username) == '') {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_username_empty') );
					} else if(is_null($user_input->password) || trim($user_input->password) == '') {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_pin_empty') );
					}

					if( !ZTVTerminal::register($user_input->username, $user_input->password) ) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_invalid_password_or_username') );
					}

					$plugin_cookies->username = $user_input->username;
					$plugin_cookies->password = $user_input->password;
					return ($user_input->selected_media_url == 'setup') ?
						ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('%tr%dialog_successful_login') )
						: ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					break;
				case 'deregister_terminal':
					if( !ZTVTerminal::deregister() ) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_cant_logout') );
					}

					return ($user_input->selected_media_url == 'setup') ?
						ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('%tr%dialog_successful_login') )
						: ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					break;
				case 'register_cancel':
					if(ZTVTerminal::login() && $user_input->selected_media_url != 'setup') {
						return ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					}
					return ActionFactory::reset_controls( $this->do_registration_form_defs($plugin_cookies) );
					break;
				case 'profile':
					$this->profile = $this->profiles[$user_input->profile];

					if($this->profile->require_password) {
						return ActionFactory::show_dialog(
							'%ext%<key_local>dialog_profile_password<p>' . $this->profile->title . '</p></key_local>',
							$this->do_profile_form_defs(
								$plugin_cookies,
								(!$this->profile->password) ?
									'set_profile_password' : 'change_profile_password'
							),
							true
						);
					}

					$json_reply = ZTVApi::call( ZTVApi::set_profile($this->profile) );
					if($json_reply === false) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_cant_select_profile') );
					}
					break;
				case 'check_profile_password':
					if($user_input->profile_password != $this->profile->password) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_invalid_password') );
					}

					if($user_input->selected_media_url != 'setup') {
						return ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					}

					$json_reply = ZTVApi::call(ZTVApi::set_profile($this->profile) );
					if($json_reply === false) {
						return ActionFactory::close_dialog_and_run( ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_cant_validate_password') ) );
					}
					break;
				case 'change_profile_password':
					if($user_input->profile_password != $this->profile->password) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_invalid_password') );
					}

					if(!$user_input->profile_password_new) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_new_password_empty') );
					}

					if($user_input->profile_password_new != $user_input->profile_password_repeat) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_passwords_mismatch') );
					}

					$json_reply = ZTVApi::call( ZTVApi::set_profile($this->profile, $user_input->profile_password) );
					return ($json_reply === false) ?
						ActionFactory::close_dialog_and_run( ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_cant_change_password') ) )
						: ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('%tr%dialog_successful_password_change') );
					break;
				case 'set_profile_password':
					if(!$user_input->profile_password) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_new_password_empty') );
					}

					if($user_input->profile_password != $user_input->profile_password_repeat) {
						return ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_passwords_mismatch.') );
					}

					$json_reply = ZTVApi::call( ZTVApi::set_profile($this->profile, $user_input->profile_password) );
					return ($json_reply === false) ?
						ActionFactory::close_dialog_and_run( ActionFactory::show_error( false, '%tr%caption_error', array('%tr%error_cant_set_password') ) )
						: ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('%tr%dialog_successful_password_set') );
					break;
				case 'profile_cancel':
					// TODO: Reset to the last selected profile instead of default.
					$this->profile = $this->profiles[1];
					$json_reply = ZTVApi::call( ZTVApi::set_profile($this->profile) );
					if($json_reply === false) {
						return;
					}

					return ($user_input->selected_media_url != 'setup') ?
						ActionFactory::close_dialog_and_run( ActionFactory::open_folder() )
						: ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('%ext%<key_local>dialog_default_profile_set<p>' . $this->profile->title . '</p></key_local>') );
					break;
			}
		}

		return ActionFactory::reset_controls( $this->do_get_control_defs($plugin_cookies) );
	}
}

///////////////////////////////////////////////////////////////////////////
?>