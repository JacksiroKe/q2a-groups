<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

    function db_posts_calc_category_path($firstpostid, $lastpostid = null)
    {
        if (!isset($lastpostid))
            $lastpostid = $firstpostid;

        qa_db_query_sub(
            "UPDATE ^group_posts AS x, (SELECT ^posts.postid, " .
            "COALESCE(parent2.parentid, parent1.parentid, parent0.parentid, parent0.categoryid) AS catidpath1, " .
            "IF (parent2.parentid IS NOT NULL, parent1.parentid, IF (parent1.parentid IS NOT NULL, parent0.parentid, IF (parent0.parentid IS NOT NULL, parent0.categoryid, NULL))) AS catidpath2, " .
            "IF (parent2.parentid IS NOT NULL, parent0.parentid, IF (parent1.parentid IS NOT NULL, parent0.categoryid, NULL)) AS catidpath3 " .
            "FROM ^posts LEFT JOIN ^categories AS parent0 ON ^posts.categoryid=parent0.categoryid LEFT JOIN ^categories AS parent1 ON parent0.parentid=parent1.categoryid LEFT JOIN ^categories AS parent2 ON parent1.parentid=parent2.categoryid WHERE ^posts.postid BETWEEN # AND #) AS a SET x.catidpath1=a.catidpath1, x.catidpath2=a.catidpath2, x.catidpath3=a.catidpath3 WHERE x.postid=a.postid",
            $firstpostid, $lastpostid
        ); // requires QA_CATEGORY_DEPTH=4
    }

    function db_post_create($groupid, $type, $parentid, $userid, $cookieid, $ip, $title, $content, $format, $tagstring, $notify, $categoryid = null, $name = null)
    {
        qa_db_query_sub(
            'INSERT INTO ^group_posts (groupid, categoryid, type, parentid, userid, cookieid, createip, title, content, format, tags, notify, name, created) ' .
            'VALUES (#, #, $, #, $, #, UNHEX($), $, $, $, $, $, $, NOW())',
            $groupid, $categoryid, $type, $parentid, $userid, $cookieid, bin2hex(@inet_pton($ip)), $title, $content, $format, $tagstring, $notify, $name
        );

        return qa_db_last_insert_id();
    }