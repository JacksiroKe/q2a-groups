<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

    function post_html_fields($post, $userid, $cookieid, $usershtml, $dummy, $options = array())
    {
        if (qa_to_override(__FUNCTION__)) { $args=func_get_args(); return qa_call_override(__FUNCTION__, $args); }

        require_once QA_INCLUDE_DIR . 'app/updates.php';
        require_once QA_INCLUDE_DIR . 'app/posts.php';

        if (isset($options['blockwordspreg']))
            require_once QA_INCLUDE_DIR . 'util/string.php';

        $fields = array('raw' => $post);

        // Useful stuff used throughout function

        $postid = $post['postid'];
        $isquestion = $post['basetype'] == 'Q';
        $isanswer = $post['basetype'] == 'A';
        $iscomment = $post['basetype'] == 'C';
        $isbyuser = qa_post_is_by_user($post, $userid, $cookieid);
        $anchor = urlencode(qa_anchor($post['basetype'], $postid));
        $elementid = isset($options['elementid']) ? $options['elementid'] : $anchor;
        $microdata = qa_opt('use_microdata') && !empty($options['contentview']);
        $isselected = @$options['isselected'];
        $favoritedview = @$options['favoritedview'];
        $favoritemap = $favoritedview ? qa_get_favorite_non_qs_map() : array();

        // High level information

        $fields['hidden'] = isset($post['hidden']) ? $post['hidden'] : null;
        $fields['queued'] = isset($post['queued']) ? $post['queued'] : null;
        $fields['tags'] = 'id="' . qa_html($elementid) . '"';

        $fields['classes'] = ($isquestion && $favoritedview && @$post['userfavoriteq']) ? 'qa-q-favorited' : '';
        if ($isquestion && post_is_closed($post)) {
            $fields['classes'] = ltrim($fields['classes'] . ' qa-q-closed');
        }

        if ($microdata) {
            if ($isanswer) {
                $fields['tags'] .= ' itemprop="suggestedAnswer' . ($isselected ? ' acceptedAnswer' : '') . '" itemscope itemtype="https://schema.org/Answer"';
            }
            if ($iscomment) {
                $fields['tags'] .= ' itemscope itemtype="https://schema.org/Comment"';
            }
        }

        // Question-specific stuff (title, URL, tags, answer count, category)

        if ($isquestion) {
            if (isset($post['title'])) {
                $fields['url'] = q_path_html($post['groupid'], $postid, $post['title']);

                if (isset($options['blockwordspreg']))
                    $post['title'] = qa_block_words_replace($post['title'], $options['blockwordspreg']);

                $fields['title'] = qa_html($post['title']);
                if ($microdata) {
                    $fields['title'] = '<span itemprop="name">' . $fields['title'] . '</span>';
                }

                /*if (isset($post['score'])) // useful for setting match thresholds
                    $fields['title'].=' <small>('.$post['score'].')</small>';*/
            }

            if (@$options['tagsview'] && isset($post['tags'])) {
                $fields['q_tags'] = array();

                $tags = qa_tagstring_to_tags($post['tags']);
                foreach ($tags as $tag) {
                    if (isset($options['blockwordspreg']) && count(qa_block_words_match_all($tag, $options['blockwordspreg']))) // skip censored tags
                        continue;

                    $fields['q_tags'][] = qa_tag_html($tag, $microdata, @$favoritemap['tag'][qa_strtolower($tag)]);
                }
            }

            if (@$options['answersview'] && isset($post['acount'])) {
                $fields['answers_raw'] = $post['acount'];

                $fields['answers'] = ($post['acount'] == 1) ? qa_lang_html_sub_split('main/1_answer', '1', '1')
                    : qa_lang_html_sub_split('main/x_answers', qa_format_number($post['acount'], 0, true));

                $fields['answer_selected'] = isset($post['selchildid']);
            }

            if (@$options['viewsview'] && isset($post['views'])) {
                $fields['views_raw'] = $post['views'];

                $fields['views'] = ($post['views'] == 1) ? qa_lang_html_sub_split('main/1_view', '1', '1') :
                    qa_lang_html_sub_split('main/x_views', qa_format_number($post['views'], 0, true));
            }

            if (@$options['categoryview'] && isset($post['categoryname']) && isset($post['categorybackpath'])) {
                $favoriteclass = '';

                if (isset($favoritemap['category']) && !empty($favoritemap['category'])) {
                    if (isset($favoritemap['category'][$post['categorybackpath']])) {
                        $favoriteclass = ' qa-cat-favorited';
                    } else {
                        foreach ($favoritemap['category'] as $categorybackpath => $dummy) {
                            if (substr('/' . $post['categorybackpath'], -strlen($categorybackpath)) == $categorybackpath)
                                $favoriteclass = ' qa-cat-parent-favorited';
                        }
                    }
                }

                $fields['where'] = qa_lang_html_sub_split('main/in_category_x',
                    '<a href="' . qa_path_html(@$options['categorypathprefix'] . implode('/', array_reverse(explode('/', $post['categorybackpath'])))) .
                    '" class="qa-category-link' . $favoriteclass . '">' . qa_html($post['categoryname']) . '</a>');
            }
        }

        // Answer-specific stuff (selection)

        if ($isanswer) {
            $fields['selected'] = $isselected;

            if ($isselected)
                $fields['select_text'] = qa_lang_html('question/select_text');
        }

        // Post content

        if (@$options['contentview'] && isset($post['content'])) {
            $viewer = qa_load_viewer($post['content'], $post['format']);

            $fields['content'] = $viewer->get_html($post['content'], $post['format'], array(
                'blockwordspreg' => @$options['blockwordspreg'],
                'showurllinks' => @$options['showurllinks'],
                'linksnewwindow' => @$options['linksnewwindow'],
            ));

            if ($microdata) {
                $fields['content'] = '<div itemprop="text">' . $fields['content'] . '</div>';
            }

            // this is for backwards compatibility with any existing links using the old style of anchor
            // that contained the post id only (changed to be valid under W3C specifications)
            $fields['content'] = '<a name="' . qa_html($postid) . '"></a>' . $fields['content'];
        }

        // Voting stuff

        if (@$options['voteview']) {
            $voteview = $options['voteview'];

            // Calculate raw values and pass through

            if (@$options['ovoteview'] && isset($post['opostid'])) {
                $upvotes = (int)@$post['oupvotes'];
                $downvotes = (int)@$post['odownvotes'];
                $fields['vote_opostid'] = true; // for voters/flaggers layer
            } else {
                $upvotes = (int)@$post['upvotes'];
                $downvotes = (int)@$post['downvotes'];
            }

            $netvotes = $upvotes - $downvotes;

            $fields['upvotes_raw'] = $upvotes;
            $fields['downvotes_raw'] = $downvotes;
            $fields['netvotes_raw'] = $netvotes;

            // Create HTML versions...

            $upvoteshtml = qa_html(qa_format_number($upvotes, 0, true));
            $downvoteshtml = qa_html(qa_format_number($downvotes, 0, true));

            if ($netvotes >= 1)
                $netvotesPrefix = '+';
            elseif ($netvotes <= -1)
                $netvotesPrefix = '&ndash;';
            else
                $netvotesPrefix = '';

            $netvotes = abs($netvotes);
            $netvoteshtml = $netvotesPrefix . qa_html(qa_format_number($netvotes, 0, true));

            // Pass information on vote viewing

            // $voteview will be one of:
            // updown, updown-disabled-page, updown-disabled-level, updown-uponly-level, updown-disabled-approve, updown-uponly-approve
            // net, net-disabled-page, net-disabled-level, net-uponly-level, net-disabled-approve, net-uponly-approve

            $fields['vote_view'] = (substr($voteview, 0, 6) == 'updown') ? 'updown' : 'net';

            $fields['vote_on_page'] = strpos($voteview, '-disabled-page') ? 'disabled' : 'enabled';

            if ($iscomment) {
                // for comments just show number, no additional text
                $fields['upvotes_view'] = array('prefix' => '', 'data' => $upvoteshtml, 'suffix' => '');
                $fields['downvotes_view'] = array('prefix' => '', 'data' => $downvoteshtml, 'suffix' => '');
                $fields['netvotes_view'] = array('prefix' => '', 'data' => $netvoteshtml, 'suffix' => '');
            } else {
                $fields['upvotes_view'] = $upvotes == 1
                    ? qa_lang_html_sub_split('main/1_liked', $upvoteshtml, '1')
                    : qa_lang_html_sub_split('main/x_liked', $upvoteshtml);
                $fields['downvotes_view'] = $downvotes == 1
                    ? qa_lang_html_sub_split('main/1_disliked', $downvoteshtml, '1')
                    : qa_lang_html_sub_split('main/x_disliked', $downvoteshtml);
                $fields['netvotes_view'] = $netvotes == 1
                    ? qa_lang_html_sub_split('main/1_vote', $netvoteshtml, '1')
                    : qa_lang_html_sub_split('main/x_votes', $netvoteshtml);
            }

            // schema.org microdata - vote display might be formatted (e.g. '2k') so we use meta tag for true count
            if ($microdata) {
                $fields['netvotes_view']['suffix'] .= ' <meta itemprop="upvoteCount" content="' . qa_html($netvotes) . '"/>';
                $fields['upvotes_view']['suffix'] .= ' <meta itemprop="upvoteCount" content="' . qa_html($upvotes) . '"/>';
            }

            // Voting buttons

            $fields['vote_tags'] = 'id="voting_' . qa_html($postid) . '"';
            $onclick = 'onclick="return qa_vote_click(this);"';

            if ($fields['hidden']) {
                $fields['vote_state'] = 'disabled';
                $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_hidden_post') . '"';
                $fields['vote_down_tags'] = $fields['vote_up_tags'];

            } elseif ($fields['queued']) {
                $fields['vote_state'] = 'disabled';
                $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_queued') . '"';
                $fields['vote_down_tags'] = $fields['vote_up_tags'];

            } elseif ($isbyuser) {
                $fields['vote_state'] = 'disabled';
                $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_my_post') . '"';
                $fields['vote_down_tags'] = $fields['vote_up_tags'];

            } elseif (strpos($voteview, '-disabled-')) {
                $fields['vote_state'] = (@$post['uservote'] > 0) ? 'voted_up_disabled' : ((@$post['uservote'] < 0) ? 'voted_down_disabled' : 'disabled');

                if (strpos($voteview, '-disabled-page'))
                    $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_q_page_only') . '"';
                elseif (strpos($voteview, '-disabled-approve'))
                    $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_approve') . '"';
                else
                    $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_disabled_level') . '"';

                $fields['vote_down_tags'] = $fields['vote_up_tags'];

            } elseif (@$post['uservote'] > 0) {
                $fields['vote_state'] = 'voted_up';
                $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/voted_up_popup') . '" name="' . qa_html('vote_' . $postid . '_0_' . $elementid) . '" ' . $onclick;
                $fields['vote_down_tags'] = ' ';

            } elseif (@$post['uservote'] < 0) {
                $fields['vote_state'] = 'voted_down';
                $fields['vote_up_tags'] = ' ';
                $fields['vote_down_tags'] = 'title="' . qa_lang_html('main/voted_down_popup') . '" name="' . qa_html('vote_' . $postid . '_0_' . $elementid) . '" ' . $onclick;

            } else {
                $fields['vote_up_tags'] = 'title="' . qa_lang_html('main/vote_up_popup') . '" name="' . qa_html('vote_' . $postid . '_1_' . $elementid) . '" ' . $onclick;

                if (strpos($voteview, '-uponly-level')) {
                    $fields['vote_state'] = 'up_only';
                    $fields['vote_down_tags'] = 'title="' . qa_lang_html('main/vote_disabled_down') . '"';

                } elseif (strpos($voteview, '-uponly-approve')) {
                    $fields['vote_state'] = 'up_only';
                    $fields['vote_down_tags'] = 'title="' . qa_lang_html('main/vote_disabled_down_approve') . '"';

                } else {
                    $fields['vote_state'] = 'enabled';
                    $fields['vote_down_tags'] = 'title="' . qa_lang_html('main/vote_down_popup') . '" name="' . qa_html('vote_' . $postid . '_-1_' . $elementid) . '" ' . $onclick;
                }
            }
        }

        // Flag count

        if (@$options['flagsview'] && @$post['flagcount']) {
            $fields['flags'] = ($post['flagcount'] == 1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
                : qa_lang_html_sub_split('main/x_flags', $post['flagcount']);
        }

        // Created when and by whom

        $fields['meta_order'] = qa_lang_html('main/meta_order'); // sets ordering of meta elements which can be language-specific

        if (@$options['whatview']) {
            $fields['what'] = qa_lang_html($isquestion ? 'main/asked' : ($isanswer ? 'main/answered' : 'main/commented'));

            if (@$options['whatlink'] && strlen(@$options['q_request'])) {
                $fields['what_url'] = $post['basetype'] == 'Q'
                    ? qa_path_html($options['q_request'])
                    : qa_path_html($options['q_request'], array('show' => $postid), null, null, qa_anchor($post['basetype'], $postid));
                if ($microdata) {
                    $fields['what_url_tags'] = ' itemprop="url"';
                }
            }
        }

        if (isset($post['created']) && @$options['whenview']) {
            $fields['when'] = qa_when_to_html($post['created'], @$options['fulldatedays']);

            if ($microdata) {
                $gmdate = gmdate('Y-m-d\TH:i:sO', $post['created']);
                $fields['when']['data'] = '<time itemprop="dateCreated" datetime="' . $gmdate . '" title="' . $gmdate . '">' . $fields['when']['data'] . '</time>';
            }
        }

        if (@$options['whoview']) {
            $fields['who'] = qa_who_to_html($isbyuser, @$post['userid'], $usershtml, @$options['ipview'] ? @inet_ntop(@$post['createip']) : null, $microdata, $post['name']);

            if (isset($post['points'])) {
                if (@$options['pointsview'])
                    $fields['who']['points'] = ($post['points'] == 1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
                        : qa_lang_html_sub_split('main/x_points', qa_format_number($post['points'], 0, true));

                if (isset($options['pointstitle']))
                    $fields['who']['title'] = qa_get_points_title_html($post['points'], $options['pointstitle']);
            }

            if (isset($post['level']))
                $fields['who']['level'] = qa_html(qa_user_level_string($post['level']));
        }

        if (@$options['avatarsize'] > 0) {
            if (QA_FINAL_EXTERNAL_USERS)
                $fields['avatar'] = qa_get_external_avatar_html($post['userid'], $options['avatarsize'], false);
            else
                $fields['avatar'] = qa_get_user_avatar_html(@$post['flags'], @$post['email'], @$post['handle'],
                    @$post['avatarblobid'], @$post['avatarwidth'], @$post['avatarheight'], $options['avatarsize']);
        }

        // Updated when and by whom

        if (@$options['updateview'] && isset($post['updated']) &&
            ($post['updatetype'] != QA_UPDATE_SELECTED || $isselected) && // only show selected change if it's still selected
            ( // otherwise check if one of these conditions is fulfilled...
                (!isset($post['created'])) || // ... we didn't show the created time (should never happen in practice)
                ($post['hidden'] && ($post['updatetype'] == QA_UPDATE_VISIBLE)) || // ... the post was hidden as the last action
                (post_is_closed($post) && $post['updatetype'] == QA_UPDATE_CLOSED) || // ... the post was closed as the last action
                (abs($post['updated'] - $post['created']) > 300) || // ... or over 5 minutes passed between create and update times
                ($post['lastuserid'] != $post['userid']) // ... or it was updated by a different user
            )
        ) {
            switch ($post['updatetype']) {
                case QA_UPDATE_TYPE:
                case QA_UPDATE_PARENT:
                    $langstring = 'main/moved';
                    break;

                case QA_UPDATE_CATEGORY:
                    $langstring = 'main/recategorized';
                    break;

                case QA_UPDATE_VISIBLE:
                    $langstring = $post['hidden'] ? 'main/hidden' : 'main/reshown';
                    break;

                case QA_UPDATE_CLOSED:
                    $langstring = post_is_closed($post) ? 'main/closed' : 'main/reopened';
                    break;

                case QA_UPDATE_TAGS:
                    $langstring = 'main/retagged';
                    break;

                case QA_UPDATE_SELECTED:
                    $langstring = 'main/selected';
                    break;

                default:
                    $langstring = 'main/edited';
                    break;
            }

            $fields['what_2'] = qa_lang_html($langstring);

            if (@$options['whenview']) {
                $fields['when_2'] = qa_when_to_html($post['updated'], @$options['fulldatedays']);

                if ($microdata) {
                    $gmdate = gmdate('Y-m-d\TH:i:sO', $post['updated']);
                    $fields['when_2']['data'] = '<time itemprop="dateModified" datetime="' . $gmdate . '" title="' . $gmdate . '">' . $fields['when_2']['data'] . '</time>';
                }
            }

            if (isset($post['lastuserid']) && @$options['whoview'])
                $fields['who_2'] = qa_who_to_html(isset($userid) && ($post['lastuserid'] == $userid), $post['lastuserid'], $usershtml, @$options['ipview'] ? @inet_ntop($post['lastip']) : null, false);
        }


        // That's it!

        return $fields;
    }

    function group_html_fields($group, $userid, $options = array())
    {
        require_once QA_INCLUDE_DIR . 'app/updates.php';
        require_once QA_INCLUDE_DIR . 'app/posts.php';

        if (isset($options['blockwordspreg']))
            require_once QA_INCLUDE_DIR . 'util/string.php';

        $groupid = $group['groupid'];
        $fields = array('raw' => $group);
        $microdata = qa_opt('use_microdata') && !empty($options['contentview']);

        if (isset($group['title'])) {
            $fields['url'] = qa_path_html('groups/' . $groupid );

            $fields['title'] = qa_html($group['title']);
            if ($microdata) {
                $fields['title'] = '<span itemprop="name">' . $fields['title'] . '</span>';
            }
        }

        //$fields['classes'] = ($isquestion && $favoritedview && @$post['userfavoriteq']) ? 'qa-q-favorited' : '';
        if ($group['type'] == 'PRIVATE') {
            $fields['classes'] = 'qa-q-closed';
            $fields['content'] = '<b>' . qa_lang('group_lang/group_private_group') . ' | ' . 
                $group['questions']. ' ' . qa_lang('group_lang/group_questions') . ' | ' .
                $group['members']. ' ' . qa_lang('group_lang/group_members') . '</b>';
        }
        else $fields['content'] = '<b>' . qa_lang('group_lang/group_public_group') . ' | ' . 
            $group['questions']. ' ' . qa_lang('group_lang/group_questions') . ' | ' .
            $group['members']. ' ' . qa_lang('group_lang/group_members') . '</b>';

        if (isset($group['content'])) {
            $limit = 100;
            if (strlen($group['content']) > $limit) 
                $fields['content'] .= '<br>'.substr(strip_tags($group['content']), 0, $limit). ' ...';
            else $fields['content'] .= '<br>'.substr(strip_tags($group['content']), 0, $limit);
        }

        $fields['q_tags'] = array();
        $tags = qa_tagstring_to_tags($group['tags']);
        foreach ($tags as $tag) {
            if (isset($options['blockwordspreg']) && count(qa_block_words_match_all($tag, $options['blockwordspreg']))) // skip censored tags
                continue;

            $fields['q_tags'][] = qa_tag_html($tag, $microdata, @$favoritemap['tag'][qa_strtolower($tag)]);
        }

        return $fields;
    }
