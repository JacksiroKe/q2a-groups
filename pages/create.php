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

    function group_create($catid, $type, $title, $tags, $content, $note)
    {
        qa_db_query_sub(
            'INSERT INTO ^group (catid, type, title, tags, content, note, created) ' .
            'VALUES (#, $, $, $, $, $, NOW())',
            $catid, $type, $title, $tags, $content, $note
        );
    
        return qa_db_last_insert_id();
    }

    $in = array();
    
    // Process input
    $captchareason = qa_user_captcha_reason();

    if (qa_clicked('docreate')) {
        require_once QA_INCLUDE_DIR . 'app/post-create.php';

        $in['catid'] = 1;
        $in['type'] = qa_post_text('type');
        $in['title'] = qa_post_text('title');
        $in['tags'] = qa_post_text('tags');
        $in['content'] = qa_post_text('content');
        $in['note'] = qa_post_text('note');

        $errors = array();
        if (!qa_check_form_security_code('create', qa_post_text('code'))) {
            $errors['page'] = qa_lang_html('misc/form_security_again');
        }
        else {
            /*if (qa_using_categories() && count($Group_cats) && (!qa_opt('bp_allow_no_Groupcat')) && !isset($in['catid'])) {
                // check this here because we need to know count($Group_cats)
                $errors['catid'] = qa_lang_html('article/category_required');
            }
            elseif (qa_user_permit_error('bp_permit_post_p', null, $userlevel)) {
                $errors['catid'] = qa_lang_html('group_lang/category_write_not_allowed');
            }*/

            if ($captchareason) {
                require_once QA_INCLUDE_DIR . 'app/captcha.php';
                qa_captcha_validate_post($errors);
            }

            if (empty($errors)) {
                $cookieid = isset($userid) ? qa_cookie_get() : qa_cookie_get_create(); // create a new cookie if necessary
                //$title = qa_block_words_replace($in['title'], qa_get_block_words_preg());
                
                $groupid = group_create($in['catid'], $in['type'], $in['title'], $in['tags'], $in['content'], $in['note']);
                qa_redirect('groups/' .$groupid); 
            }
        }
    }

    $qa_content['title'] = qa_lang('group_lang/group_a_create');
    $qa_content['error'] = @$errors['page'];

    $type_options = array(
        "PUBLIC" => qa_lang_html('group_lang/group_public'), 
        "PRIVATE" => qa_lang_html('group_lang/group_private')
    );
    
    $qa_content['form'] = array(
        'tags' => 'name="write" method="post" action="'.qa_self_html().'"',
        'style' => 'wide',

        'fields' => array(
           'type' => array(
                'type' => 'select-radio',
                'label' => qa_lang_html('group_lang/group_type'),
                'tags' => 'name="type" id="type" autocomplete="off"',
                'options' => $type_options,
                'value' => $type_options['PUBLIC'],
                'error' => qa_html(@$errors['type']),
            ),
            'title' => array(
                 'label' => qa_lang_html('group_lang/group_name'),
                 'tags' => 'name="title" id="title" autocomplete="off"',
                 'value' => qa_html(@$in['title']),
                 'error' => qa_html(@$errors['title']),
             ),

             'tags' => array(
                'label' => qa_lang_html('group_lang/group_tags'),
                'tags' => 'name="tags" id="tags" autocomplete="off"',
                'value' => qa_html(@$in['tags']),
                'style' => 'tall',
                'error' => qa_html(@$errors['tags']),
            ),

            'content' => array(
                'label' => qa_lang_html('group_lang/group_description'),
                'tags' => 'name="content" id="content" autocomplete="off"',
                'value' => qa_html(@$in['content']),
                'type' => 'textatrea',
                'style' => 'tall',
                'rows' => 5,
                'error' => qa_html(@$errors['content']),
            ),

            'note' => array(
                'label' => qa_lang_html('group_lang/group_note'),
                'tags' => 'name="note" id="note" autocomplete="off"',
                'value' => qa_html(@$in['note']),
                'type' => 'textatrea',
                'style' => 'tall',
                'rows' => 5,
                'error' => qa_html(@$errors['note']),
            ),

        ),

        'buttons' => array(
            'save' => array(
                'tags' => 'onclick="qa_show_waiting_after(this, false);"',
                'label' => qa_lang_html('group_lang/group_create'),
            ),
        ),

        'hidden' => array(
            'code' => qa_get_form_security_code('create'),
            'docreate' => '1',
        ),
    );

    if ($captchareason) {
        require_once QA_INCLUDE_DIR . 'app/captcha.php';
        qa_set_up_captcha_field($qa_content, $qa_content['form']['fields'], @$errors, qa_captcha_reason_note($captchareason));
    }

    $qa_content['focusid'] = 'title';
    
    $qa_content['navigation']['sub'] = group_sub_navigation($qa_request);

    return $qa_content;

/*
	Omit PHP closing tag to help avoid accidental output
*/
