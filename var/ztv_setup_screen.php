<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class ZTVSetupScreen extends AbstractControlsScreen
{
	const ID = 'setup';

	///////////////////////////////////////////////////////////////////////

	public function __construct($tv)
	{
		parent::__construct(self::ID);
		$this->tv = $tv;
		$this->profile = null;
		$this->profiles = null;
	}

	///////////////////////////////////////////////////////////////////////

	public function do_get_control_defs(&$plugin_cookies)
	{
		$defs = array();

		$use_proxy = isset($plugin_cookies->use_proxy) ? $plugin_cookies->use_proxy : 'no';
		$proxy_ip = isset($plugin_cookies->proxy_ip) ? $plugin_cookies->proxy_ip : '192.168.0.1';
		$proxy_port = isset($plugin_cookies->proxy_port) ? $plugin_cookies->proxy_port : '8080';

		$this->add_label($defs, 'ZTV:', ZTVConfig::PLUGIN_VERSION);
		$this->add_button($defs, 'register_button', null, 'Уч. запись', 400);

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
		$this->add_combobox($defs, 'profile', 'Профиль:', $init_profile, $profiles, 0, true);

		$show_ops = array(
			'yes' => 'Да',
			'no' => 'Нет'
		);
		$this->add_combobox($defs, 'use_proxy', 'Использовать UDP-прокси:', $use_proxy, $show_ops, 0, true);
		if($use_proxy == 'yes') {
			$this->add_text_field($defs, 'proxy_ip', 'Адрес:', $proxy_ip,
									false, false, false, true, 500, false, true);
			$this->add_text_field($defs, 'proxy_port', 'Порт:', $proxy_port,
									true, false, false,  true, null, false, true);
		}

		$daemon_status = ZTVDaemonController::status();

		$this->add_vgap($defs, 30);
		$this->add_label( $defs, 'Сервис ARES:', $daemon_status['output'] );
		$this->add_button($defs, 'daemon_start_button', null, 'Запуск', 400);
		$this->add_button($defs, 'daemon_restart_button', null, 'Перезапуск', 400);
		$this->add_button($defs, 'daemon_stop_button', null, 'Стоп', 400);
		return $defs;
	}

	///////////////////////////////////////////////////////////////////////

	private function do_registration_form_defs(&$plugin_cookies)
	{
		$defs = array();

		$username = isset($plugin_cookies->username) ? $plugin_cookies->username : '';
		$password = isset($plugin_cookies->password) ? $plugin_cookies->password : '';

		$this->add_text_field($defs, 'username', 'Идентификатор:', $username,
								true/*numeric*/, false, false, false, 500);
		$this->add_text_field($defs, 'password', 'PIN-код:', $password,
								true/*numeric*/, true, false, false, 500);
		$this->add_vgap($defs, 50);
		$this->add_button($defs, 'register_terminal', null, 'Регистрация', 400);
		if( ZTVTerminal::login() ) {
			$this->add_button($defs, 'deregister_terminal', null, 'Выход', 400);
		}
		$this->add_button($defs, 'register_cancel', null, 'Отмена', 400);

		return $defs;
	}

	///////////////////////////////////////////////////////////////////////

	private function do_profile_form_defs(&$plugin_cookies, $set_password)
	{
		$defs = array();

		$this->add_text_field($defs, 'profile_password', 'Пароль:', '', false/*numeric*/, true, false, false, 500);
		if($set_password) {
			$this->add_text_field($defs, 'profile_password_repeat', 'Подтверждение пароля:', '', false/*numeric*/, true, false, false, 500);
		}

		$this->add_vgap($defs, 50);
		$this->add_button($defs, $set_password ? 'set_profile_password' : 'verify_profile_password', null, 'Ok', 400);
		$this->add_button($defs, 'profile_cancel', null, 'Отмена', 400);

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
			$result = $this->tv->api_call(
				array(
					HD::make_json_rpc_request('get_terminal_params',
						array( 'macaddr' => HD::get_mac_addr_dashed() )
					),
					HD::make_json_rpc_request('get_profiles',
						array( 'macaddr' => HD::get_mac_addr_dashed() )
					)
				)
			);
			if(count($result) == 2 && $result[0]->result && $result[1]->result) {
				$profile_id = $result[0]->result->profile_id;
				$profiles = $result[1]->result;
				$this->profiles = array();
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
				return ActionFactory::show_dialog('Введите регистрационные данные', $this->do_registration_form_defs($plugin_cookies), true);
			}

			$result = $this->load_profiles();
			if($result) {
				if($this->profile->require_password) {
					return ActionFactory::show_dialog('Введите пароль профиля ' . $this->profile->title, $this->do_profile_form_defs($plugin_cookies, false), true);
				}
				return ActionFactory::open_folder();
			}
			return;
		} else if($user_input->action_type == 'confirm' || $user_input->action_type == 'apply' ) {
			switch($user_input->control_id) {
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
				case 'daemon_start_button':
					ZTVDaemonController::start(
						($plugin_cookies->use_proxy == 'yes') ? array(
							'-y' => $plugin_cookies->proxy_ip . ':' . $plugin_cookies->proxy_port
						) : array()
					);
					break;
				case 'daemon_restart_button':
					ZTVDaemonController::restart(
						($plugin_cookies->use_proxy == 'yes') ? array(
							'-y' => $plugin_cookies->proxy_ip . ':' . $plugin_cookies->proxy_port
						) : array()
					);
					break;
				case 'daemon_stop_button':
					ZTVDaemonController::stop();
					break;
				case 'register_button':
					return ActionFactory::show_dialog(
						'Введите регистрационные данные',
						 $this->do_registration_form_defs($plugin_cookies),
						 true
					);
					break;
				case 'register_terminal':
					if(is_null($user_input->username) || trim($user_input->username) === '') {
						return ActionFactory::show_error( false, 'Ошибка', array('Идентификатор не может быть пустым.') );
					} else if(is_null($user_input->password) || trim($user_input->password) === '') {
						return ActionFactory::show_error( false, 'Ошибка', array('PIN-код не может быть пустым.') );
					}
					if( !ZTVTerminal::register($user_input->username, $user_input->password) ) {
						return ActionFactory::show_error( false, 'Ошибка', array('Неверный идентификатор или PIN-код.') );
					}
					$plugin_cookies->username = $user_input->username;
					$plugin_cookies->password = $user_input->password;
					if($user_input->selected_media_url == 'setup') {
						return ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('Вход выполнен.') );
					}
					return ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					break;
				case 'deregister_terminal':
					if(ZTVTerminal::deregister() && $user_input->selected_media_url == 'setup') {
						return ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('Выход выполнен.') );
					}
					return ActionFactory::show_error( false, 'Ошибка', array('Не удалось выполненить выход.') );
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
							'Введите пароль профиля ' . $this->profile->title,
							$this->do_profile_form_defs(
								$plugin_cookies,
								$this->profile->require_password && !$this->profile->password
							),
							true
						);
					}
					if( $this->tv->set_profile($this->profile->id) ) {
						return ActionFactory::show_title_dialog('Профиль успешно установлен');
					}
					break;
				case 'set_profile_password':
					if(!$user_input->profile_password)
						return ActionFactory::show_error( false, 'Ошибка', array('Пароль не задан.') );
					if($user_input->profile_password != $user_input->profile_password_repeat)
						return ActionFactory::show_error( false, 'Ошибка', array('Введенные пароли не совпадают.') );
					$result = $this->tv->api_call(
						array(
							HD::make_json_rpc_request('set_terminal_params',
								array(
									'macaddr' => HD::get_mac_addr_dashed(),
									'profile_id' => $this->profile->id
								)
							),
							HD::make_json_rpc_request('update_profile',
								array(
									'macaddr' => HD::get_mac_addr_dashed(),
									'profile_id' => $this->profile->id,
									'profile_password' => $user_input->profile_password
								)
							)
						)
					);
					if( count($result) == 2 && $result[0]->result && $result[1]->result ) {
						return ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('Профиль успешно установлен') );
					}
					return;
					break;
				case 'verify_profile_password':
					if($user_input->profile_password != $this->profile->password) {
						return ActionFactory::show_error( false, 'Ошибка', array('Неверный пароль.') );
					}
					if($user_input->selected_media_url != 'setup') {
						return ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					}
					$result = $this->tv->set_profile($this->profile->id);
					if($result) {
						return ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('Профиль успешно установлен') );
					}
					return;
					break;
				case 'profile_cancel':
					$result = $this->tv->set_profile(1);
					if(!$result) {
						return;
					}
					if($user_input->selected_media_url != 'setup') {
						return ActionFactory::close_dialog_and_run( ActionFactory::open_folder() );
					}
					$this->profile = $this->profiles[1];
					return ActionFactory::close_dialog_and_run( ActionFactory::show_title_dialog('Установлен профиль по умолчанию(' . $this->profile->title . ')') );
					break;
			}
		}

		return ActionFactory::reset_controls( $this->do_get_control_defs($plugin_cookies) );
	}
}

///////////////////////////////////////////////////////////////////////////
?>