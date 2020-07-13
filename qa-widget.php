<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

	require_once QA_INCLUDE_DIR . 'db/users.php';
	require_once QA_INCLUDE_DIR . 'util/string.php';
	require_once QA_INCLUDE_DIR . 'app/users.php';
	require_once QA_INCLUDE_DIR . 'app/blobs.php';
	require_once QA_PLUGIN_DIR . 'q2a-groups/qa-base.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/db/selects.php';

	class group_widget
	{
		function allow_template($template)
        {

            $allow=true;
            
            return $allow;
        }

        function allow_region($region)
        {
            return ($region=='side');
        }

        function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
        {
			$groups = qa_db_select_with_pending(db_list_groups_selectspec(5));
			
			$themeobject = $themeobject;

			$themeobject->output('<h2>' . qa_lang_html('group_lang/group_title') . '</h2>');
			$themeobject->output('<ul class="qa-nav-cat-list qa-nav-cat-list-1">');
			foreach ($groups as $group) {
				$themeobject->output('<li class="qa-nav-cat-item qa-nav-cat-all">');
				$themeobject->output('<a href="'.qa_path_html('groups/' . $group['groupid'] ).'">'.$group['title'].'</a>');
				$themeobject->output('<span class="qa-nav-cat-note">(0)</span>');
				$themeobject->output('</li>');
			}
			$themeobject->output('</ul>');
			$themeobject->nav_clear('cat');
			$themeobject->clear_context('nav_type');
        }
	}
