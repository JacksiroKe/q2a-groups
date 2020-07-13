<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

    require_once QA_INCLUDE_DIR . 'db/maxima.php';
    require_once QA_PLUGIN_DIR . 'q2a-groups/db/post-create.php';
    require_once QA_INCLUDE_DIR . 'db/points.php';
    require_once QA_INCLUDE_DIR . 'db/hotness.php';
    require_once QA_INCLUDE_DIR . 'util/string.php';


    function combine_notify_email($userid, $notify, $email)
    {
        return $notify ? (empty($email) ? (isset($userid) ? '@' : null) : $email) : null;
    }

    function post_index($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid)
    {
        global $qa_post_indexing_suspended;
    
        if ($qa_post_indexing_suspended > 0)
            return;
    
        // Send through to any search modules for indexing
    
        $searches = qa_load_modules_with('search', 'index_post');
        foreach ($searches as $search)
            $search->index_post($postid, $type, $questionid, $parentid, $title, $content, $format, $text, $tagstring, $categoryid);
    }
    
    function update_counts_for_q($postid)
    {
        /*if (isset($postid)) // post might no longer exist
            qa_db_category_path_qcount_update(qa_db_post_get_category_path($postid));

        qa_db_qcount_update();
        qa_db_unaqcount_update();
        qa_db_unselqcount_update();
        qa_db_unupaqcount_update();
        qa_db_tagcount_update();*/
    }

    function question_create($groupid, $followanswer, $userid, $handle, $cookieid, $title, $content, $format, $text, $tagstring, $notify, $email,
        $categoryid = null, $extravalue = null, $queued = false, $name = null)
    {
        require_once QA_INCLUDE_DIR . 'db/selects.php';
        require_once QA_PLUGIN_DIR . 'q2a-groups/db/selects.php';

        $postid = db_post_create($groupid, $queued ? 'Q_QUEUED' : 'Q', @$followanswer['postid'], $userid, isset($userid) ? null : $cookieid,
            qa_remote_ip_address(), $title, $content, $format, $tagstring, combine_notify_email($userid, $notify, $email),
            $categoryid, isset($userid) ? null : $name);

        if (isset($extravalue)) {
            require_once QA_INCLUDE_DIR . 'db/metas.php';
            qa_db_postmeta_set($postid, 'qa_q_extra', $extravalue);
        }

        db_posts_calc_category_path($postid);
        qa_db_hotness_update($postid);

        if ($queued) {
            //qa_db_queuedcount_update();

        } else {
            //post_index($postid, 'Q', $postid, @$followanswer['postid'], $title, $content, $format, $text, $tagstring, $categoryid);
            update_counts_for_q($postid);
            //qa_db_points_update_ifuser($userid, 'qposts');
        }

        qa_report_event($queued ? 'q_queue' : 'q_post', $userid, $handle, $cookieid, array(
            'postid' => $postid,
            'parentid' => @$followanswer['postid'],
            'parent' => $followanswer,
            'title' => $title,
            'content' => $content,
            'format' => $format,
            'text' => $text,
            'tags' => $tagstring,
            'categoryid' => $categoryid,
            'extra' => $extravalue,
            'name' => $name,
            'notify' => $notify,
            'email' => $email,
        ));

        return $postid;
    }
