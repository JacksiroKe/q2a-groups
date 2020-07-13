<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/
    
    function post_is_closed(array $question)
    {
        return isset($question['closedbyid']) || (isset($question['selchildid']) && qa_opt('do_close_on_select'));
    }

    function group_sub_navigation($request)
    {		
        $navigation = array();
        $navigation['home'] = array(	
            'label' => qa_lang('group_lang/group_home'),
            'url' => qa_path_html('groups'),
            'selected' => ($request == 'groups' ) ? 'selected' : '',
        );

        if (qa_is_logged_in()) {
            $navigation['create'] = array(
                'label' => qa_lang('group_lang/group_create'),
                'url' => qa_path_html('groups/create'),
                'selected' => ($request == 'groups/create' ) ? 'selected' : '',
            );
        }

        $groupid = qa_request_part(1);
        if (is_numeric($groupid)) {
            $group = qa_db_select_with_pending( db_groups_selectspec(qa_get_logged_in_userid(), $groupid) );
            $groupurl = 'groups/' . $groupid;

            $navigation['group_home'] = array(	
                'label' => $group['title'] . ' - ' . qa_lang('group_lang/group_home_here'),
                'url' => qa_path_html($groupurl),
                'selected' => ($request == $groupurl ) ? 'selected' : '',
            );
        
            $navigation['group_ask'] = array(	
                'label' => qa_lang('group_lang/group_ask_here'),
                'url' => qa_path_html($groupurl . '/ask'),
                'selected' => ($request == $groupurl . '/ask' ) ? 'selected' : '',
            );
        }

        return $navigation;
    }

    function any_to_q_html_fields($question, $userid, $cookieid, $usershtml, $dummy, $options)
    {
        if (isset($question['opostid']))
            $fields = qa_other_to_q_html_fields($question, $userid, $cookieid, $usershtml, null, $options);
        else
            $fields = post_html_fields($question, $userid, $cookieid, $usershtml, null, $options);

        return $fields;
    }
    
    function q_path($groupid, $questionid, $title, $absolute = false, $showtype = null, $showid = null)
    {
        if (($showtype == 'Q' || $showtype == 'A' || $showtype == 'C') && isset($showid)) {
            $params = array('show' => $showid); // due to pagination
            $anchor = qa_anchor($showtype, $showid);
    
        } else {
            $params = null;
            $anchor = null;
        }
    
        return qa_path('groups/' . $groupid . '/' . qa_q_request($questionid, $title), $params, $absolute ? qa_opt('site_url') : null, null, $anchor);
    }
    
    function q_path_html($groupid, $questionid, $title, $absolute = false, $showtype = null, $showid = null)
    {
        return qa_html(q_path($groupid, $questionid, $title, $absolute, $showtype, $showid));
    }

    function q_list_page_content($grouptitle, $questions, $pagesize, $start, $count, $sometitle, $nonetitle,
    $navcategories, $categoryid, $categoryqcount, $categorypathprefix, $suggest, $html = null)
    {
        require_once QA_INCLUDE_DIR . 'app/format.php';
        require_once QA_INCLUDE_DIR . 'app/updates.php';
        require_once QA_INCLUDE_DIR . 'app/posts.php';

        $userid = qa_get_logged_in_userid();


        // Chop down to size, get user information for display
        if (isset($pagesize)) {
            $questions = array_slice($questions, 0, $pagesize);
        }

        $usershtml = qa_userids_handles_html(qa_any_get_userids_handles($questions));

        // Prepare content for theme
        $qa_content = qa_content_prepare(true, array_keys(qa_category_path($navcategories, $categoryid)));
        
        if (isset($html)) $qa_content['custom'] = $html;

        $qa_content['q_list']['form'] = array(
            'tags' => 'method="post" action="' . qa_self_html() . '"',

            'hidden' => array(
                'code' => qa_get_form_security_code('vote'),
            ),
        );
        
        //$qa_content['title'] = $grouptitle;
        $qa_content['q_list']['qs'] = array();

        if (!empty($questions)) {
            $qa_content['title'] = $grouptitle . '; <br>' . $sometitle;

            $defaults = qa_post_html_defaults('Q');
            if (isset($categorypathprefix)) {
                $defaults['categorypathprefix'] = $categorypathprefix;
            }

            foreach ($questions as $question) {
                $fields = any_to_q_html_fields($question, $userid, qa_cookie_get(), $usershtml, null, qa_post_html_options($question, $defaults));

                if (post_is_closed($question)) {
                    $fields['closed'] = array(
                        'state' => qa_lang_html('main/closed'),
                    );
                }

                $qa_content['q_list']['qs'][] = $fields;
            }
        } else {
            $qa_content['title'] = $grouptitle;
            $qa_content['custome'] = '<h1>' . $nonetitle . '</h1>';
        }

        if (isset($count) && isset($pagesize)) {
            $qa_content['page_links'] = qa_html_page_links(qa_request(), $start, $pagesize, $count, qa_opt('pages_prev_next'), $pagelinkparams);
        }

        $qa_content['canonical'] = qa_get_canonical();

        if (empty($qa_content['page_links'])) {
            //$qa_content['suggest_next'] = $suggest;
        }

        return $qa_content;
    }

/*
	Omit PHP closing tag to help avoid accidental output
*/
