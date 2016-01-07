<?php
class ZTVConfig
{
	const PLUGIN_VERSION			= '1.1.14';

	const TV_FAVORITES_SUPPORTED	= true;

	const ALL_CHANNEL_GROUP_CAPTION		= '%tr%caption_all_channels';
	const ALL_CHANNEL_GROUP_ICON_PATH   = 'plugin_file://icons/tv-all.png';

	const FAV_CHANNEL_GROUP_CAPTION		= '%tr%caption_favorites';
	const FAV_CHANNEL_GROUP_ICON_PATH   = 'plugin_file://icons/tv-fav.png';

	///////////////////////////////////////////////////////////////////////
	// Folder views.

	public static function GET_TV_GROUP_LIST_FOLDER_VIEWS()
	{
		return array(
			array
			(
				PluginRegularFolderView::async_icon_loading => false,

				PluginRegularFolderView::view_params => array
				(
					ViewParams::num_cols => 4,
					ViewParams::num_rows => 4,
					ViewParams::paint_icon_selection_box => false,
					ViewParams::paint_content_box_background => false,
					ViewParams::paint_scrollbar => false,
					ViewParams::paint_help_line => false,
					ViewParams::paint_path_box => false,
					ViewParams::paint_details => false,
					ViewParams::paint_sandwich => false,
					ViewParams::sandwich_base => 'gui_skin://special_icons/sandwich_base.aai',
					ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
					ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
					ViewParams::sandwich_width => 245,
					ViewParams::sandwich_height => 140,
					ViewParams::sandwich_icon_upscale_enabled => true,
					ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path => 'plugin_file://icons/group-background.png',
					ViewParams::optimize_full_screen_background => true,
				),

				PluginRegularFolderView::base_view_item_params => array
				(
					ViewItemParams::item_paint_icon => true,
					ViewItemParams::item_layout => HALIGN_CENTER,
					ViewItemParams::icon_valign => VALIGN_CENTER,
					ViewItemParams::item_paint_caption => true,
					ViewItemParams::item_caption_color => 4,
					ViewItemParams::icon_scale_factor => 1.0,
					ViewItemParams::icon_sel_scale_factor => 1.3,
					ViewItemParams::icon_path => 'plugin_file://icons/tv-missing.png',
				),

				PluginRegularFolderView::not_loaded_view_item_params => array (),
			),
		);
	}

	public static function GET_TV_CHANNEL_LIST_FOLDER_VIEWS()
	{
		return array(
			array
			(
				PluginRegularFolderView::async_icon_loading => true,

				PluginRegularFolderView::view_params => array
				(
					ViewParams::num_cols => 2,
					ViewParams::num_rows => 10,
					ViewParams::paint_details => false,
					ViewParams::paint_scrollbar => false,
					ViewParams::paint_help_line => true,
					ViewParams::paint_path_box => false,
					ViewParams::paint_details => false,
					ViewParams::paint_content_box_background => false,
					ViewParams::background_path => 'plugin_file://icons/channel-background.png',
					ViewParams::background_order => 'before_all',
					ViewParams::optimize_full_screen_background => true,
				),

				PluginRegularFolderView::base_view_item_params => array
				(
					ViewItemParams::item_paint_icon => true,
					ViewItemParams::item_layout => HALIGN_LEFT,
					ViewItemParams::icon_valign => VALIGN_CENTER,
					ViewItemParams::icon_dx => 30,
					ViewItemParams::icon_dy => -5,
					ViewItemParams::icon_width => 86,
					ViewItemParams::icon_height => 50,
					ViewItemParams::item_caption_dx => 60,
					ViewItemParams::item_caption_dy => 0,
					ViewItemParams::item_caption_width => 750,
					ViewItemParams::item_caption_font_size => FONT_SIZE_SMALL,
					ViewItemParams::icon_path => 'plugin_file://icons/channel-missing.png',
				),

				PluginRegularFolderView::not_loaded_view_item_params => array (),
			),

			array
			(
				PluginRegularFolderView::async_icon_loading => false,

				PluginRegularFolderView::view_params => array
				(
					ViewParams::num_cols => 4,
					ViewParams::num_rows => 4,
					ViewParams::paint_icon_selection_box => false,
					ViewParams::paint_content_box_background => false,
					ViewParams::paint_scrollbar => false,
					ViewParams::paint_help_line => true,
					ViewParams::paint_path_box => false,
					ViewParams::paint_details => false,
					ViewParams::paint_sandwich => false,
					ViewParams::sandwich_base => 'gui_skin://special_icons/sandwich_base.aai',
					ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
					ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
					ViewParams::sandwich_width => 245,
					ViewParams::sandwich_height => 140,
					ViewParams::sandwich_icon_upscale_enabled => true,
					ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path => 'plugin_file://icons/channel-background.png',
					ViewParams::background_order => 'before_all',
					ViewParams::optimize_full_screen_background => true,
				),

				PluginRegularFolderView::base_view_item_params => array
				(
					ViewItemParams::item_paint_icon => true,
					ViewItemParams::item_layout => HALIGN_CENTER,
					ViewItemParams::icon_valign => VALIGN_CENTER,
					ViewItemParams::item_paint_caption => true,
					ViewItemParams::icon_scale_factor => 0.8,
					ViewItemParams::icon_sel_scale_factor => 1.0,
					ViewItemParams::icon_path => 'plugin_file://icons/channel-missing.png',
				),

				PluginRegularFolderView::not_loaded_view_item_params => array (),
			),

			array
			(
				PluginRegularFolderView::async_icon_loading => false,

				PluginRegularFolderView::view_params => array
				(
					ViewParams::num_cols => 3,
					ViewParams::num_rows => 3,
					ViewParams::paint_icon_selection_box => false,
					ViewParams::paint_content_box_background => false,
					ViewParams::paint_scrollbar => false,
					ViewParams::paint_help_line => true,
					ViewParams::paint_path_box => false,
					ViewParams::paint_details => false,
					ViewParams::paint_sandwich => false,
					ViewParams::sandwich_base => 'gui_skin://special_icons/sandwich_base.aai',
					ViewParams::sandwich_mask => 'cut_icon://{name=sandwich_mask}',
					ViewParams::sandwich_cover => 'cut_icon://{name=sandwich_cover}',
					ViewParams::sandwich_width => 245,
					ViewParams::sandwich_height => 140,
					ViewParams::sandwich_icon_upscale_enabled => true,
					ViewParams::sandwich_icon_keep_aspect_ratio => false,
					ViewParams::background_path => 'plugin_file://icons/channel-background.png',
					ViewParams::background_order => 'before_all',
					ViewParams::optimize_full_screen_background => true,
				),

				PluginRegularFolderView::base_view_item_params => array
				(
					ViewItemParams::item_paint_icon => true,
					ViewItemParams::item_layout => HALIGN_CENTER,
					ViewItemParams::icon_valign => VALIGN_CENTER,
					ViewItemParams::item_paint_caption => false,
					ViewItemParams::icon_scale_factor => 1.0,
					ViewItemParams::icon_sel_scale_factor => 1.2,
					ViewItemParams::icon_path => 'plugin_file://icons/channel-missing.png',
				),

				PluginRegularFolderView::not_loaded_view_item_params => array (),
			),
		);
	}
}
?>