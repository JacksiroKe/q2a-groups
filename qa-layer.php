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

class qa_html_theme_layer extends qa_html_theme_base {
	var $q2a_groups_layer;
	function __construct($template, $content, $rooturl, $request)
	{
		global $qa_layers;
		$this->q2a_groups_layer = './' . $qa_layers['Q2A Groups Layer']['urltoroot'];
		qa_html_theme_base::qa_html_theme_base($template, $content, $rooturl, $request);
	}
		
}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
