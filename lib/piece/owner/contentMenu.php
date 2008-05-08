<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
if(isset($blogMenu['topMenu'])) {
	switch($blogMenu['topMenu']) {
		case 'center':
			if(Acl::check('group.administrators')) {
				$blogContentMenuItem = array(
					array('menu'=>'dashboard','title'=>_t('조각보'),'link'=>'/owner/center/dashboard'),
				);
			}
			else{
				$blogContentMenuItem = array(
					array('menu'=>'dashboard','title'=>_t('조각보'),'link'=>'/owner/center/dashboard')
				);
			}
		break;
		case 'entry':
			if(Acl::check('group.editors')) {
				$blogContentMenuItem = array(
					array('menu'=>'post','title'=>_t('글쓰기'),'link'=>'/owner/entry/post'),
					array('menu'=>'entry','title'=>_t('글 목록'),'link'=>'/owner/entry'),
					array('menu'=>'category','title'=>_t('분류 관리'),'link'=>'/owner/entry/category')
				);
			} else {
				$blogContentMenuItem = array(
					array('menu'=>'post','title'=>_t('글쓰기'),'link'=>'/owner/entry/post'),
					array('menu'=>'entry','title'=>_t('글 목록'),'link'=>'/owner/entry')
				);
			}
		break;
		case 'communication':
			if(Acl::check('group.administrators')) {
				$blogContentMenuItem = array(
					array('menu'=>'comment','title'=>_t('소통 기록'),'link'=>'/owner/communication/comment'),
					array('menu'=>'openid','title'=>_t('오픈아이디 목록'),'link'=>'/owner/communication/openid'),
					array('menu'=>'link','title'=>_t('링크'),'link'=>'/owner/communication/link')
				);
				if($service['reader'] == true) array_push($blogContentMenuItem,array('menu'=>'reader','title'=>_t('바깥 글 읽기'),'link'=>'/owner/communication/reader'));
			} else {
				$blogContentMenuItem = array(
					array('menu'=>'comment','title'=>_t('소통 기록'),'link'=>'/owner/communication/comment'),
					array('menu'=>'trash','title'=>_t('휴지통'),'link'=>'/owner/communication/trash/comment')
				);
				if($service['reader'] == true) array_push($blogContentMenuItem,array('menu'=>'reader','title'=>_t('바깥 글 읽기'),'link'=>'/owner/communication/reader'));
			}
		break;
		case 'skin':
		if(Acl::check('group.administrators')) {
			$blogContentMenuItem = array(
				array('menu'=>'skin','title'=>_t('스킨 선택'),'link'=>'/owner/skin'),
				array('menu'=>'edit','title'=>_t('스킨 편집'),'link'=>'/owner/skin/edit'),
				array('menu'=>'setting','title'=>_t('상세 설정'),'link'=>'/owner/skin/setting'),
				array('menu'=>'coverpage','title'=>_t('색동'),'link'=>'/owner/skin/coverpage'),
				array('menu'=>'sidebar','title'=>_t('사이드바'),'link'=>'/owner/skin/sidebar'),
				array('menu'=>'adminSkin','title'=>_t('관리자 패널 스킨 선택'),'link'=>'/owner/skin/adminSkin')
			);
		}
		break;
		case 'plugin':
		if(Acl::check('group.administrators')) {
			$blogContentMenuItem = array(
				array('menu'=>'plugin','title'=>_t('플러그인 목록'),'link'=>'/owner/plugin')
			);
			if(Acl::check('group.creators')) array_push($blogContentMenuItem, array('menu'=>'tableSetting','title'=>_t('플러그인 데이터 관리'),'link'=>'/owner/plugin/tableSetting'));
		}
		break;
		case 'setting':
		case 'data':
		if(Acl::check('group.administrators')) {
			$blogContentMenuItem = array(
				array('menu'=>'blog','title'=>_t('블로그'),'link'=>'/owner/setting/blog'),
				array('menu'=>'entry','title'=>_t('글 작성'),'link'=>'/owner/setting/entry'),
				array('menu'=>'account','title'=>_t('개인 정보'),'link'=>'/owner/setting/account'),
				array('menu'=>'teamblog','title'=>_t('필진 목록'),'link'=>'/owner/setting/teamblog'),
				array('menu'=>'filter','title'=>_t('스팸 필터'),'link'=>'/owner/setting/filter'),
				array('menu'=>'data','title'=>_t('데이터 관리'),'link'=>'/owner/data')
			);
		} else if(Acl::check('group.editors')) {
			$blogContentMenuItem = array(
				array('menu'=>'account','title'=>_t('개인 정보'),'link'=>'/owner/setting/account')
			);
		} else {
			$blogContentMenuItem = array(
				array('menu'=>'account','title'=>_t('개인 정보'),'link'=>'/owner/setting/account')
			);
		}
		break;
		case 'reader':
		break;
		case 'control':
		if(Acl::check('group.creators')) {
			$blogContentMenuItem = array(
				array('menu'=>'blog','title'=>_t('블로그'),'link'=>'/owner/control/blog'),
				array('menu'=>'user','title'=>_t('사용자'),'link'=>'/owner/control/user'),
				array('menu'=>'server','title'=>_t('서버'),'link'=>'/owner/control/server'),
				array('menu'=>'system','title'=>_t('시스템 정보'),'link'=>'/owner/control/system')
			);
		}
		break;
	}

	if( empty($blogContentMenuItem) ) {
		echo _t('접근권한이 없습니다');
		exit;
	}

	foreach($adminMenuMappings as $path => $pluginAdminMenuitem) {
		if($pluginAdminMenuitem['topMenu'] == $blogMenu['topMenu']) {
			if(count($blogContentMenuItem) < $pluginAdminMenuitem['contentMenuOrder'] 
			  || $pluginAdminMenuitem['contentMenuOrder'] < 1)
				$pluginAdminMenuitem['contentMenuOrder'] = count($blogContentMenuItem);
			array_splice($blogContentMenuItem, $pluginAdminMenuitem['contentMenuOrder'], 0, 
				array(array('menu'=>'adminMenu?name='.$path,
				'title'=>$pluginAdminMenuitem['title'],
				'link'=>'/owner/plugin/adminMenu?name='.$path))
			);
		}
	}

	if($blogMenu['topMenu'] == 'center') {  // exception handling for 'about.' about menu will hold the end of center.
		$blogContentMenuItem = array_merge($blogContentMenuItem, array(array('menu'=>'about','title'=>_t('텍스트큐브는'),'link'=>'/owner/center/about')));
	}
}
if(!defined('__TEXTCUBE_READER_SUBMENU__')) {
?>
			<div id="layout-body">
<?php
}
?>
				<h2><?php echo isset($blogMenu['title']) ? _f('서브메뉴 : %1', $blogMenu['title']) : _t('서브메뉴');?></h2>

<?php
if(isset($blogContentMenuItem)) {
?>	
				<div id="sub-menu-box">
					<ul id="sub-menu">
<?php
	$firstChildClass = ' firstChild';
	$submenuURL = null;
	if (isset($_POST['category'])) $currentCategory = $_POST['category'];
	else if (isset($_GET['category'])) $currentCategory = $_GET['category'];
	else $currentCategory = null;
	if(in_array($blogMenu['contentMenu'],array('notify','trackback','trashcomment','trashtrackback')))
		$blogMenu['contentMenu'] = 'comment';
	else if(in_array($blogMenu['contentMenu'],array('linkadd','linkedit','linkcategoryEdit','xfn')))
		$blogMenu['contentMenu'] = 'link';
	
	foreach($blogContentMenuItem as $contentMenuItem) { 
		$PostIdStr = null;
		if(strstr($contentMenuItem['menu'], 'adminMenu?name=') !== false) {
			$pluginMenuValue = explode('/',substr($contentMenuItem['menu'], 15));
			$PostIdStr = $pluginMenuValue[0];
			if(($blogMenu['contentMenu'] == $contentMenuItem['menu'] || (isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) || ($contentMenuItem['menu'] == 'trash' && strpos($blogMenu['contentMenu'],'trash') !== false))) {
				$submenuURL = $pluginMenuValue[0];
			}
		} else {
			$PostIdStr = $contentMenuItem['menu'];
			if(($blogMenu['contentMenu'] == $contentMenuItem['menu'] 
				|| (isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) 
				|| (in_array($contentMenuItem['menu'],array('blog','user')) && strpos($blogMenu['contentMenu'],'detail') !== false)
				)) {
				$submenuURL = $blogMenu['contentMenu'];
			}
		}
?>
						<li id="sub-menu-<?php echo $PostIdStr;?>"<?php echo 
						(($blogMenu['contentMenu'] == $contentMenuItem['menu'] || 
							(isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) ||
							($contentMenuItem['menu'] == 'add' && strpos($blogMenu['contentMenu'],'add') !== false) ||
							($contentMenuItem['menu'] == 'blog' && strpos($blogMenu['contentMenu'],'blog') !== false && strpos($blogMenu['contentMenu'],'teamblog') === false) ||
							($contentMenuItem['menu'] == 'user' && strpos($blogMenu['contentMenu'],'user') !== false) ||
							($blogMenu['contentMenu'] == 'edit' && $contentMenuItem['menu'] == 'post')) ? " class=\"selected{$firstChildClass}\"" : ($firstChildClass ? " class=\"$firstChildClass\"" : ''));?>><a href="<?php 
						echo $blogURL.
							$contentMenuItem['link'].
							($contentMenuItem['menu'] == 'post' && isset($currentCategory) ? '?category='.$currentCategory : '');
						?>"><span class="text"><?php echo $contentMenuItem['title'];?></span></a></li>
<?php
		$firstChildClass = null;
	}
	
	$helpURL = $blogMenu['topMenu'].(isset($blogMenu['contentMenu']) ? '/'.$submenuURL : '');
?>
						<li id="sub-menu-helper"><a href="<?php echo getHelpURL($helpURL);?>" onclick="window.open(this.href); return false;"><span class="text"><?php echo _t('도우미');?></span></a></li>
					</ul>
				</div>
<?php
}
if(!defined('__TEXTCUBE_READER_SUBMENU__')) {
?>
				<hr class="hidden" />
				
				<div id="pseudo-box">
					<div id="data-outbox">
<?php
}
?>
