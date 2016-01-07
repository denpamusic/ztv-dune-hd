<?php
///////////////////////////////////////////////////////////////////////////

require_once 'ztv_daemon_controller.php';

///////////////////////////////////////////////////////////////////////////

class ZTVTerminal
{
	///////////////////////////////////////////////////////////////////////

	private static $_logged_in = false;
	private static $_login_expire = 0;

	///////////////////////////////////////////////////////////////////////

	public static function login($anonymous = null)
	{
		$now = time();

		if(self::$_logged_in && self::$_login_expire > $now) {
			return self::$_logged_in;
		}

		$result = ZTVDaemonController::login(
			!is_null($anonymous) ? array(
				'-A' => $anonymous
			) : array()
		);
		if($result->output === false) {
			hd_print('Error: can\'t execute arescam.');
			throw new DuneException('Internal ARES error', 0,
				ActionFactory::show_error(true, '%tr%caption_system_error',
					array(
						'%tr%error_daemon_execution_failed',
						'%tr%error_contact_support'
					)
				)
			);
		}

		self::$_logged_in = ($result->rc === 0);
		if(self::$_logged_in) {
			self::$_login_expire = $now + 86400;
		}

		return self::$_logged_in;
	}

	///////////////////////////////////////////////////////////////////////

	public static function register($username, $password)
	{
		$result = ZTVDaemonController::register(
			array(
				'-u' => $username,
				'-p' => $password
			)
		);
		if($result->output === false) {
			hd_print('Error: can not execute arescam.');
			throw new DuneException('Internal ARES error', 0,
				ActionFactory::show_error(true, '%tr%caption_system_error',
					array(
						'%tr%error_daemon_execution_failed',
						'%tr%error_contact_support'
					)
				)
			);
		}

		return($result->rc === 0);
	}

	///////////////////////////////////////////////////////////////////////

	public static function deregister()
	{
		if(!self::$_logged_in) {
			return false;
		}

		$result = ZTVDaemonController::deregister();
		if($result->output === false) {
			hd_print('Error: can not execute arescam.');
			throw new DuneException('%tr%caption_system_error', 0,
				ActionFactory::show_error(true, '%tr%caption_system_error',
					array(
						'%tr%error_daemon_execution_failed',
						'%tr%error_contact_support'
					)
				)
			);
		}

		if($result->rc === 0) {
			self::$_logged_in = false;
			self::$_login_expire = 0;
			return true;
		}

		return false;
	}
}

///////////////////////////////////////////////////////////////////////////
?>