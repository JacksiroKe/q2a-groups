<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
	
}
	
	define( "QA_LIMIT_GROUPS", "G");

	$plugin_dir = dirname( __FILE__ ) . '/';
	$plugin_url = qa_path_to_root().'qa-plugin/q2a-groups';

	//qa_register_layer('group-admin.php', 'Group Settings', $plugin_dir, $plugin_url );
	qa_register_plugin_overrides('qa-overrides.php');	
	qa_register_plugin_phrases('langs/lang-*.php', 'group_lang');
	qa_register_plugin_module('page', 'qa-groups.php', 'group_plugin', 'Q2A Groups');
	qa_register_plugin_module('widget', 'qa-widget.php', 'group_widget', 'Q2A Groups Widget');
	qa_register_plugin_layer('qa-layer.php', 'Q2A Groups Layer');
		
/*
	Omit PHP closing tag to help avoid accidental output
*/
