<?php
$toplist = array(
	12 => array(
		'score' => 54533,
		'viewing' => true,
		'last_seen' => <timestamp>
	)
);

// ztv_channel.php
private $score;
private $last_seen;

public function set_last_seen() {
	$this->last_seen = now();
}

public function set_score() {
	$this->score = time() - $this->last_seen;
}

// ztv_tv.php
public function get_tv_playback_url($channel_id, $archive_ts, $protect_code, &$plugin_cookies)
{
	$this->ensure_channels_loaded($plugin_cookies);

	$channel = $this->get_channel($channel_id);
	$channel->set_last_seen();
	
	$channels = usort($this->get_channels(), 'sort_by_view_date');
	$channel = $this->get_channel( $channels[0]->get_id() );
	$channel = set_score();
	


	return $channel->get_streaming_url();
}

private function sort_by_view_date($a, $b) {
	switch(true) {
		case $a->get_last_seen() > $b->get_last_seen():
			return 1;
			break;
		case $a->get_last_seen() < $b->get_last_seen():
			return -1;
			break;
		default:
			return 0;
	}
}

public function folder_entered(MediaURL $media_url, &$plugin_cookies)
{
	if(
		!isset($media_url->screen_id) ||
		$media_url->screen_id === TvGroupListScreen::ID ||
		$media_url->screen_id === TvChannelListScreen::ID
	) {
		$this->unload_channels();
	}
}

private function get_previous_viewed()
{
	foreach($this->toplist as $channel) {
		if($channel['viewing']) {
			return $channel;
		}
	}
	return false;
}

public function load_channels(&$plugin_cookies)
{
	$toplist = $plugin_cookies->toplist ? unserialize($plugin_cookies->toplist) : array();
	
	$channel = new ZTVChannel(
		$media->id,
		$media->name,
		$media->logo == null ? 'plugin_file://icons/tv-missing.png' : $media->logo,
		$mrl,
		$number++,
		$past_epg_days,
		$future_epg_days,
		$toplist[$media->id]['score'],
		$toplist[$media->id]['last_seen'],
	);
}


public function unload_channels(&$plugin_cookies)
{
	$this->channels = null;
	$this->groups = null;
}

function sort_toplist($a, $b) {
	switch(true) {
		case $a['score'] > $b['score']:
			return 1;
			break;
		case $a['score'] < $b['score']:
			return -1;
			break;
		case $a['score'] == $b['score']:
			return 0;
	}
}