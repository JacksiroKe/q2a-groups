<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

    function group_sub_navigationx($request)
    {		
        $navigation = array();
        $navigation['home'] = array(	
            'label' => qa_lang('group_lang/group_home'),
            'url' => qa_path_html('groups'),
            'selected' => ($request == 'groups' ) ? 'selected' : '',
        );

        /*$navigation['browse'] = array(	
            'label' => qa_lang('group_lang/group_discover'),
            'url' => qa_path_html('groups/discover'),
            'selected' => ($request == 'groups/discover' ) ? 'selected' : '',
        );*/
        
        //if (qa_user_maximum_permit_error('bp_permit_post_p') != 'level') {
        if (qa_is_logged_in()) {
            $navigation['create'] = array(
                'label' => qa_lang('group_lang/group_create'),
                'url' => qa_path_html('groups/create'),
                'selected' => ($request == 'groups/create' ) ? 'selected' : '',
            );
        }
        
        if (qa_get_logged_in_level() >= QA_USER_LEVEL_ADMIN || !qa_user_maximum_permit_error('permit_moderate') ||
            !qa_user_maximum_permit_error('permit_hide_show') || !qa_user_maximum_permit_error('permit_delete_hidden')
        ) {
            $navigation['admin_cats'] = array(
                'label' => qa_lang('group_lang/group_manage_cats'),
                'url' => qa_path_html('groups/admin_cats'),
                'selected' => ($request == 'groups/admin_cats' ) ? 'selected' : '',
            );
        }
            
        return $navigation;
    }

/*
	Omit PHP closing tag to help avoid accidental output
*/
