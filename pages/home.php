<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

   
if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../');
	exit;
}

    $qa_content = qa_content_prepare(); 
    $qa_content['title'] = qa_lang('group_lang/group_title');
    $qa_content['error'] = @$errors['page'];
    $qa_content['custom'] = '';
    
	require_once QA_INCLUDE_DIR . 'db/selects.php';
	require_once QA_INCLUDE_DIR . 'app/format.php';
    require_once QA_INCLUDE_DIR . 'app/q-list.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/app/format.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/db/selects.php';

	$categoryslugs = qa_request_parts(1);
	$countslugs = count($categoryslugs);

	$sort = ($countslugs && !QA_ALLOW_UNINDEXED_QUERIES) ? null : qa_get('sort');
	$start = qa_get_start();
	$userid = qa_get_logged_in_userid();

	// Get list of groups, plus category information
    $selectsort = 'created';

	$groups = qa_db_select_with_pending(db_list_groups_selectspec());

    if (isset($pagesize)) {
        $groups = array_slice($groups, 0, $pagesize);
    }

    $usershtml = qa_userids_handles_html(qa_any_get_userids_handles($groups));

    $qa_content['q_list']['form'] = array(
        'tags' => 'method="post" action="' . qa_self_html() . '"',

        'hidden' => array(
            'code' => qa_get_form_security_code('vote'),
        ),
    );

    $qa_content['q_list']['qs'] = array();
	
    if (!empty($groups)) {
       foreach ($groups as $group) {
           $fields = group_html_fields($group, $userid, null);
           $qa_content['q_list']['qs'][] = $fields;
       }
   }

   if (isset($count) && isset($pagesize)) {
       $qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'), $pagelinkparams);
   }

   $qa_content['canonical'] = qa_get_canonical();

   if (empty($qa_content['page_links'])) {
       //$qa_content['suggest_next'] = $suggest;
   }

    $qa_content['navigation']['sub'] = group_sub_navigation($qa_request);

    return $qa_content;

/*
	Omit PHP closing tag to help avoid accidental output
*/
