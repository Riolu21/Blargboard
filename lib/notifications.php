<?php

function notifsort($a, $b)
{
	if ($a['date'] == $b['date']) return 0;
	return ($a['date'] > $b['date']) ? -1 : 1;
}

function getNotifications()
{
	global $loguserid, $loguser;
	$notifs = array();
	
	if (!$loguserid) return $notifs;
	
	$staffpms = '';
	if (HasPermission('admin.viewstaffpms')) $staffpms = ' OR p.userto={1}';
	
	$unreadpms = Query("	SELECT 
								p.*, 
								pt.title pmtitle, 
								u.(_userfields) 
							FROM 
								{pmsgs} p 
								LEFT JOIN {pmsgs_text} pt ON pt.pid=p.id 
								LEFT JOIN {users} u ON u.id=p.userfrom 
							WHERE 
							(p.userto={0}{$staffpms}) AND p.msgread=0 AND p.drafting=0", 
						$loguserid, -1);
	
	while ($pm = Fetch($unreadpms))
	{
		$userdata = getDataPrefix($pm, 'u_');
		
		$notifs[] = array
		(
			'date' => $pm['date'],
			'text' => format(__('{2}New private message from {0}{3}{4}{2}{1}{3}'), 
				userLink($userdata), actionLinkTag(htmlspecialchars($pm['pmtitle']), 'showprivate', $pm['id']),
				'<span class="nobr">', '</span>', '<br>'),
		);
	}
	
	if ($loguser['newcomments'])
	{
		$lastcmtdate = FetchResult("SELECT MAX(date) FROM {usercomments} WHERE uid={0}", $loguserid);
		
		$notifs[] = array
		(
			'date' => $lastcmtdate,
			'text' => format(__('{1}New comments in {0}{2}'), actionLinkTag(__('your profile'), 'profile', $loguserid, '', $loguser['name']),
				'<span class="nobr">', '</span>'),
		);
	}
	
	usort($notifs, 'notifsort');
	
	foreach ($notifs as $i=>$n)
		$notifs[$i]['formattedDate'] = relativedate($n['date']);
	
	return $notifs;
}


// new notification system plans:
// * type: 'pm' / 'profilecomment' / more
// * key: identifier of the notification (derived from PM ID and such)
// * args: serialized array of notification-specific arguments

