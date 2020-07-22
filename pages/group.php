<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

    $qa_content = qa_content_prepare();
    $qa_content['error'] = @$errors['page'];

    require_once QA_INCLUDE_DIR . 'db/selects.php';
    require_once QA_INCLUDE_DIR . 'app/format.php';
    require_once QA_INCLUDE_DIR . 'app/q-list.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/app/format.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/db/selects.php';

    $pagestate = qa_get_state();
    $groupid = qa_request_part(1);
	$page = qa_request_part(2);
	$action = qa_request_part(3);
    $userid = qa_get_logged_in_userid();
    $groupurl = 'groups/' . $groupid;
    
    $html = '<center><h2> <a href="'.qa_path_html($groupurl . '/ask').'">'.qa_lang('group_lang/group_ask_here').'</a> | <a href="'.qa_path_html($groupurl . '/members').'">'.qa_lang('group_lang/group_members_nav').'</a> | <a href="'.qa_path_html($groupurl . '/info').'">'.qa_lang('group_lang/group_info_nav').'</a></h2></center>';
    
    $group = qa_db_select_with_pending( db_groups_selectspec($userid, $groupid) );
    if ($group['type'] == 'PRIVATE') {
        $grouptitle = qa_lang('group_lang/group_private_group') . ': ' . $group['title'];
    }
    else $grouptitle = qa_lang('group_lang/group_public_group') . ': ' . $group['title'];
    
    if (is_numeric($page)) {
		qa_set_template('question');
		$qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/question.php';
    }
    else {
        switch ($page)
        {
            case 'ask':
                $qa_content = require QA_PLUGIN_DIR . 'q2a-groups/pages/ask.php';
                break;

            case 'edit':
                $qa_content['custom'] = '';
                break;
    
            default:
                $requestparts = explode('/', qa_request());
                $explicitqa = (strtolower($requestparts[0]) == 'qa');
                
                $slugs = array();
                
                $countslugs = count($slugs);
                
                list($questions1, $questions2, $categories, $categoryid) = qa_db_select_with_pending(
                    db_qs_selectspec($groupid, $userid, 'created', 0, $slugs, null, false, false, qa_opt_if_loaded('page_size_activity')),
                    db_recent_a_qs_selectspec($groupid, $userid, 0, $slugs),
                    qa_db_category_nav_selectspec($slugs, false, false, true),
                    $countslugs ? qa_db_slugs_to_category_id_selectspec($slugs) : null,
                );
                
                qa_set_template('qa');
                $questions = qa_any_sort_and_dedupe(array_merge($questions1, $questions2));
                $pagesize = qa_opt('page_size_home');
                
                if ($countslugs) {
                    if (!isset($categoryid)) {
                        return include QA_INCLUDE_DIR . 'qa-page-not-found.php';
                    }
                
                    $categorytitlehtml = qa_html($categories[$categoryid]['title']);
                    $sometitle = qa_lang_html_sub('main/recent_qs_as_in_x', $categorytitlehtml);
                    $nonetitle = qa_lang_html_sub('main/no_questions_in_x', $categorytitlehtml);
                
                } else {
                    $sometitle = qa_lang_html('main/recent_qs_as_title');
                    $nonetitle = qa_lang_html('main/no_questions_found');
                }
                
                $qa_content = q_list_page_content(
                    $grouptitle, 
                    $questions, // questions
                    $pagesize, // questions per page
                    0, // start offset
                    null, // total count (null to hide page links)
                    $sometitle, // title if some questions
                    $nonetitle, // title if no questions
                    $categories, // categories for navigation
                    $categoryid, // selected category id
                    true, // show question counts in category navigation
                    qa_opt('feed_for_qa') ? 'qa' : null, // prefix for RSS feed paths (null to hide)
                    (count($questions) < $pagesize) ? qa_html_suggest_ask($categoryid) : qa_html_suggest_qs_tags(qa_using_tags(), qa_category_path_request($categories, $categoryid)),
                    $html
                );
                break;
        }
    }
    
    $qa_content['navigation']['sub'] = group_sub_navigation($qa_request);

    $qa_content['navigation']['sub']['group_home'] = array(	
        'label' => $group['title'] . ' - ' . qa_lang('group_lang/group_home_here'),
        'url' => qa_path_html($groupurl),
        'selected' => ($request == $groupurl ) ? 'selected' : '',
    );

    $qa_content['navigation']['sub']['group_ask'] = array(	
        'label' => qa_lang('group_lang/group_ask_here'),
        'url' => qa_path_html($groupurl . '/ask'),
        'selected' => ($request == $groupurl . '/ask' ) ? 'selected' : '',
    );

    return $qa_content;

/*
	Omit PHP closing tag to help avoid accidental output
*/
