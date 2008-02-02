<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
$IV = array(
	'POST' => array(
		'pwd' => array('string','default'=>''),
		'prevPwd' => array('string','default'=>''),
		'APIKey' => array('string', 'default'=>'')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();
$result = false;
if($_POST['pwd'] != '' && $_POST['prevPwd'] != '') {
	$result = changePassword(getUserId(), $_POST['pwd'], $_POST['prevPwd']);
	
}
if($_POST['APIKey'] != '') {
	$result = changeAPIKey(getUserId(), $_POST['APIKey']);
}
if($result) respond::ResultPage(0);
else respond::ResultPage(-1);
?>
