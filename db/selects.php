<?php
/*
	Q2A Groups by JacksiroKe
	https://github.com/JacksiroKe

*/

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../../../');
	exit;
}

function db_groups_basic_selectspec()
{    
	$selectspec = array(
        'columns' => array(
            'groupid', 'catid', 'type', 'title', 'tags', 'content', 'note', 'avatarblobid', 'avatarwidth', 'avatarheight', 'coverblobid', 'coverwidth', 'coverheight', 'pcount', 'userid', 'lastpost',
			'created' => 'UNIX_TIMESTAMP(created)', 'updated' => 'UNIX_TIMESTAMP(updated)', 
			'questions' => '(SELECT COUNT(*) FROM ^group_posts WHERE ^group_posts.groupid=^group.groupid)',		
			'members' => '(SELECT COUNT(*) FROM ^group_users WHERE ^group_users.groupid=^group.groupid)',		
        ),
        'arraykey' => 'groupid',
		'source' => '^group',
	);
	return $selectspec;
}
		
function db_groups_selectspec($userid, $groupid)
{
	
	$selectspec = db_groups_basic_selectspec();
	$selectspec['source'] .= " WHERE groupid=$";	
	$selectspec['arguments'][] = $groupid;
	$selectspec['single'] = true;

	return $selectspec;
}

function db_list_groups_selectspec($limit = null)
{    
	$selectspec = db_groups_basic_selectspec();
	if (isset($limit)) $selectspec['source'] .= " LIMIT " . $limit;	
	$selectspec['sortdesc'] = 'created';
	$selectspec['sortdesc_2'] = 'groupid';
	
	return $selectspec;
}

function db_posts_basic_selectspec($voteuserid = null, $full = false, $user = true)
{
	$selectspec = array(
		'columns' => array(
			'^group_posts.groupid', '^group_posts.postid', '^group_posts.categoryid', '^group_posts.type', 'basetype' => 'LEFT(^group_posts.type, 1)',
			'hidden' => "INSTR(^group_posts.type, '_HIDDEN')>0", 'queued' => "INSTR(^group_posts.type, '_QUEUED')>0",
			'^group_posts.acount', '^group_posts.selchildid', '^group_posts.closedbyid', '^group_posts.upvotes', '^group_posts.downvotes', '^group_posts.netvotes', '^group_posts.views', '^group_posts.hotness',
			'^group_posts.flagcount', '^group_posts.title', '^group_posts.tags', 'created' => 'UNIX_TIMESTAMP(^group_posts.created)', '^group_posts.name',
			'categoryname' => '^categories.title', 'categorybackpath' => "^categories.backpath",
			'categoryids' => "CONCAT_WS(',', ^group_posts.catidpath1, ^group_posts.catidpath2, ^group_posts.catidpath3, ^group_posts.categoryid)",
		),

		'arraykey' => 'postid',
		'source' => '^group_posts LEFT JOIN ^categories ON ^categories.categoryid=^group_posts.categoryid',
		'arguments' => array(),
	);

	if (isset($voteuserid)) {
		require_once QA_INCLUDE_DIR . 'app/updates.php';

		$selectspec['columns']['uservote'] = '^uservotes.vote';
		$selectspec['columns']['userflag'] = '^uservotes.flag';
		$selectspec['columns']['userfavoriteq'] = '^userfavorites.entityid<=>^group_posts.postid';
		$selectspec['source'] .= ' LEFT JOIN ^uservotes ON ^group_posts.postid=^uservotes.postid AND ^uservotes.userid=$';
		$selectspec['source'] .= ' LEFT JOIN ^userfavorites ON ^group_posts.postid=^userfavorites.entityid AND ^userfavorites.userid=$ AND ^userfavorites.entitytype=$';
		array_push($selectspec['arguments'], $voteuserid, $voteuserid, QA_ENTITY_QUESTION);
	}

	if ($full) {
		$selectspec['columns']['content'] = '^group_posts.content';
		$selectspec['columns']['notify'] = '^group_posts.notify';
		$selectspec['columns']['updated'] = 'UNIX_TIMESTAMP(^group_posts.updated)';
		$selectspec['columns']['updatetype'] = '^group_posts.updatetype';
		$selectspec['columns'][] = '^group_posts.format';
		$selectspec['columns'][] = '^group_posts.lastuserid';
		$selectspec['columns']['lastip'] = '^group_posts.lastip';
		$selectspec['columns'][] = '^group_posts.parentid';
		$selectspec['columns']['lastviewip'] = '^group_posts.lastviewip';
	}

	if ($user) {
		$selectspec['columns'][] = '^group_posts.userid';
		$selectspec['columns'][] = '^group_posts.cookieid';
		$selectspec['columns']['createip'] = '^group_posts.createip';
		$selectspec['columns'][] = '^userpoints.points';

		if (!QA_FINAL_EXTERNAL_USERS) {
			$selectspec['columns'][] = '^users.flags';
			$selectspec['columns'][] = '^users.level';
			$selectspec['columns']['email'] = '^users.email';
			$selectspec['columns']['handle'] = '^users.handle';
			$selectspec['columns']['avatarblobid'] = 'BINARY ^users.avatarblobid';
			$selectspec['columns'][] = '^users.avatarwidth';
			$selectspec['columns'][] = '^users.avatarheight';
			$selectspec['source'] .= ' LEFT JOIN ^users ON ^group_posts.userid=^users.userid';

			if ($full) {
				$selectspec['columns']['lasthandle'] = 'lastusers.handle';
				$selectspec['source'] .= ' LEFT JOIN ^users AS lastusers ON ^group_posts.lastuserid=lastusers.userid';
			}
		}

		$selectspec['source'] .= ' LEFT JOIN ^userpoints ON ^group_posts.userid=^userpoints.userid';
	}

	return $selectspec;
}
function db_qs_selectspec($groupid, $voteuserid, $sort, $start, $categoryslugs = null, $createip = null, $specialtype = false, $full = false, $count = null)
{
	if ($specialtype == 'Q' || $specialtype == 'Q_QUEUED') {
		$type = $specialtype;
	} else {
		$type = $specialtype ? 'Q_HIDDEN' : 'Q'; // for backwards compatibility
	}

	$count = isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;

	switch ($sort) {
		case 'acount':
		case 'flagcount':
		case 'netvotes':
		case 'views':
			$sortsql = 'ORDER BY ^group_posts.' . $sort . ' DESC, ^group_posts.created DESC';
			break;

		case 'created':
		case 'hotness':
			$sortsql = 'ORDER BY ^group_posts.' . $sort . ' DESC';
			break;

		default:
			qa_fatal_error('qa_db_qs_selectspec() called with illegal sort value');
			break;
	}

	$selectspec = db_posts_basic_selectspec($voteuserid, $full);

	$selectspec['source'] .=
		" JOIN (SELECT postid FROM ^group_posts WHERE " .
		qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']) .
		(isset($createip) ? "createip=UNHEX($) AND " : "") .
		"type=$ " . $sortsql . " LIMIT #,#) y ON ^group_posts.postid=y.postid WHERE ^group_posts.groupid=#";

	if (isset($createip)) {
		$selectspec['arguments'][] = bin2hex(@inet_pton($createip));
	}

	array_push($selectspec['arguments'], $type, $start, $count, $groupid);

	$selectspec['sortdesc'] = $sort;

	return $selectspec;
}

function db_recent_a_qs_selectspec($groupid, $voteuserid, $start, $categoryslugs = null, $createip = null, $specialtype = false, $fullanswers = false, $count = null)
{
	if ($specialtype == 'A' || $specialtype == 'A_QUEUED') {
		$type = $specialtype;
	} else {
		$type = $specialtype ? 'A_HIDDEN' : 'A'; // for backwards compatibility
	}

	$count = isset($count) ? min($count, QA_DB_RETRIEVE_QS_AS) : QA_DB_RETRIEVE_QS_AS;

	$selectspec = db_posts_basic_selectspec($voteuserid);

	qa_db_add_selectspec_opost($selectspec, 'aposts', false, $fullanswers);
	qa_db_add_selectspec_ousers($selectspec, 'ausers', 'auserpoints');

	$selectspec['source'] .=
		" JOIN ^group_posts AS aposts ON ^group_posts.postid=aposts.parentid" .
		(QA_FINAL_EXTERNAL_USERS ? "" : " LEFT JOIN ^users AS ausers ON aposts.userid=ausers.userid") .
		" LEFT JOIN ^userpoints AS auserpoints ON aposts.userid=auserpoints.userid" .
		" JOIN (SELECT postid FROM ^group_posts WHERE " .
		qa_db_categoryslugs_sql_args($categoryslugs, $selectspec['arguments']) .
		(isset($createip) ? "createip=UNHEX($) AND " : "") .
		"type=$ ORDER BY ^group_posts.created DESC LIMIT #,#) y ON aposts.postid=y.postid" .
		($specialtype ? '' : " WHERE ^group_posts.type='Q' AND ^group_posts.groupid=#");

	if (isset($createip)) {
		$selectspec['arguments'][] = bin2hex(@inet_pton($createip));
	}

	array_push($selectspec['arguments'], $type, $start, $count, $groupid);

	$selectspec['sortdesc'] = 'otime';

	return $selectspec;
}

function db_full_post_selectspec($voteuserid, $postid)
{
	$selectspec = db_posts_basic_selectspec($voteuserid, true);

	$selectspec['source'] .= " WHERE ^group_posts.postid=#";
	$selectspec['arguments'][] = $postid;
	$selectspec['single'] = true;

	return $selectspec;
}

function db_full_child_posts_selectspec($voteuserid, $parentid)
{
	$selectspec = db_posts_basic_selectspec($voteuserid, true);

	$selectspec['source'] .= " WHERE ^group_posts.parentid=#";
	$selectspec['arguments'][] = $parentid;

	return $selectspec;
}

function db_full_a_child_posts_selectspec($voteuserid, $questionid)
{
	$selectspec = db_posts_basic_selectspec($voteuserid, true);

	$selectspec['source'] .= " JOIN ^group_posts AS parents ON ^group_posts.parentid=parents.postid WHERE parents.parentid=# AND LEFT(parents.type, 1)='A'";
	$selectspec['arguments'][] = $questionid;

	return $selectspec;
}

function db_post_parent_q_selectspec($postid)
{
	$selectspec = db_posts_basic_selectspec();

	$selectspec['source'] .= " WHERE ^group_posts.postid=(SELECT IF(LEFT(parent.type, 1)='A', parent.parentid, parent.postid) FROM ^group_posts AS child LEFT JOIN ^group_posts AS parent ON parent.postid=child.parentid WHERE child.postid=# AND parent.type IN('Q','A'))";
	$selectspec['arguments'] = array($postid);
	$selectspec['single'] = true;

	return $selectspec;
}

function db_post_close_post_selectspec($questionid)
{
	$selectspec = db_posts_basic_selectspec(null, true);

	$selectspec['source'] .= " WHERE ^group_posts.postid=(SELECT closedbyid FROM ^group_posts WHERE postid=#)";
	$selectspec['arguments'] = array($questionid);
	$selectspec['single'] = true;

	return $selectspec;
}

function db_post_duplicates_selectspec($questionid)
{
	$selectspec = db_posts_basic_selectspec(null, true);

	$selectspec['source'] .= " WHERE ^group_posts.closedbyid=#";
	$selectspec['arguments'] = array($questionid);

	return $selectspec;
}

function db_post_meta_selectspec($postid, $title)
{
	$selectspec = array(
		'columns' => array('title', 'content'),
		'source' => "^postmetas WHERE postid=# AND " . (is_array($title) ? "title IN ($)" : "title=$"),
		'arguments' => array($postid, $title),
		'arrayvalue' => 'content',
	);

	if (is_array($title)) {
		$selectspec['arraykey'] = 'title';
	} else {
		$selectspec['single'] = true;
	}

	return $selectspec;
}

