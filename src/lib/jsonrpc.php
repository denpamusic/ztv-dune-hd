<?php
///////////////////////////////////////////////////////////////////////////

class JsonRpc
{
	public static function call($json_request)
	{
		$json_reply = null;

		for($i = 0; $i < 3; ++$i) {
			try {
				$doc = HD::http_post_document( static::API_URL, json_encode($json_request) );
				$json_reply = json_decode($doc);
			} catch(Exception $e) {
				hd_print('Error: failed to do HTTP-request.');
				if($i == 2) {
					throw new DuneException('API is unavailable', 0,
						ActionFactory::show_error(true, 'Системная ошибка',
							array(
								'Сервер недоступен(' . $e->getMessage() . ').',
								'Пожалуйста обратитесь в тех. поддержку.'
							)
						)
					);
				}
				usleep(100000 << $i);
				continue;
			}
			break;
		}

		if( is_null($json_reply) ) {
			hd_print("Error: failed to decode API reply: '$doc'");
			throw new DuneException('API returned empty result', 0,
				ActionFactory::show_error(true, 'Системная ошибка',
					array(
						'Сервер вернул пустой ответ.',
						'Пожалуйста обратитесь в тех. поддержку.'
					)
				)
			);
		}

		if( isset($json_reply->error) && $json_reply->error ) {
			hd_print("Error: API returned error status($doc)");
			throw new DuneException('API returned error', $json_reply->code,
				ActionFactory::show_error(true, 'Системная ошибка',
					array(
						'Сервер вернул ошибку(' . $json_reply->message . ').',
						'Пожалуйста обратитесь в тех. поддержку.'
					)
				)
			);
		}

		// TODO: Think of a better check.
		if( !isset($json_request['method']) ) {
			$request_count = count($json_request);
			$reply_count = count($json_reply);
			if($request_count != $reply_count) {
				return false;
			}

			for($i = 0, $n = 0; $i < $reply_count; $n = $n + ($json_reply[$i]->result ? 1 : 0), $i++);
			if($i != $n) {
				return false;
			}
		}

		return $json_reply;
	}

	public static function format_timestamp($ts, $fmt = null)
    {
		if( is_null($fmt) ) {
			$fmt = static::API_DATETIME_FORMAT;
		}
		return HD::format_timestamp($ts, $fmt);
    }

    public static function parse_datetime($str, $fmt = null)
    {
		if( is_null($fmt) ) {
			$fmt = static::API_DATETIME_FORMAT;
		}

		if ( is_null($str) ) {
			return null;
		}

        $ts = DateTime::createFromFormat($fmt, $str);
        if ($ts === false) {
            hd_print ("Warning: invalid timestamp string '$str'");
            $ts = null;
        }

        return $ts;
    }

	public static function get_mac_addr()
	{
		static $mac_addr = null;

		if ( is_null($mac_addr) ) {
			$mac_addr = str_replace( ':', '-', HD::get_mac_addr() );
			hd_print("API: macaddr '$mac_addr'");
		}

		return $mac_addr;
	}
}
?>