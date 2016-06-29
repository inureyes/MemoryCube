<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

$context = Model_Context::getInstance();

/***** Automatic menu location routine. *****/
$blogMenu = array();
$urlFragments = preg_split('/\//',ltrim($context->getProperty('suri.directive'),'/'));
if(isset($urlFragments[1])) $blogMenu['topMenu'] = $urlFragments[1];
if(isset($urlFragments[2])) $blogMenu['contentMenu'] = $urlFragments[2];
else $blogMenu['contentMenu'] = $urlFragments[1];
if(isset($urlFragments[3])) $blogMenu['contentMenu'] .= $urlFragments[3];
// If admin.panel plugin, set the menu location again.
if(isset($urlFragments[2])&&strncmp($urlFragments[2],'adminMenu',9) == 0) {
	if($context->getProperty('service.fancyURL') < 2) {
		$plugin = isset($_GET['/note/plugin/adminMenu?name']) ? $_GET['/note/plugin/adminMenu?name'] : '';
	} else {
		$plugin = isset($_GET['name']) ? $_GET['name'] : '';
	}
	$pluginDir = strtok($plugin,'/');
	$blogMenu['topMenu'] = $adminMenuMappings[$plugin]['topMenu'];
}

if($urlFragments[0] == 'control' && Acl::check('group.creators')) {
	$blogTopMenuItem = array(
		array('menu'=>'control','title'=>_t('서비스관리'),'link'=>'/control/blog')
		);
	$blogMenu['topMenu'] = 'control';
} else if(Acl::check('group.administrators')) {
	$blogTopMenuItem = array(
		array('menu'=>'notes','title'=>_t('Notes'),'link'=>'/note/notes'),
		array('menu'=>'setting','title'=>_t('Settings'),'link'=>'/note/data')
		);
} else {
	$blogTopMenuItem = array(
		array('menu'=>'notes','title'=>_t('Notes'),'link'=>'/note/notes'),
		array('menu'=>'setting','title'=>_t('Settings'),'link'=>'/note/data')
		);
}
switch($blogMenu['topMenu']) {
	case 'center':
		$blogMenu['title'] = _t('센터');
		$blogMenu['loadCSS'] = array('center');
		break;
	case 'notes':
		$blogMenu['title'] = _t('노트');
		if ($blogMenu['contentMenu'] == 'post' || $blogMenu['contentMenu'] == 'edit') {
			$blogMenu['loadCSS'] = array('post','editor');
		} else {
			$blogMenu['loadCSS'] = array('post');
		}
		break;
	case 'communication':
		$blogMenu['title'] = _t('소통');
		$blogMenu['loadCSS'] = array('communication');
		break;
	case 'skin':
		$blogMenu['title'] = _t('꾸미기');
		$blogMenu['loadCSS'] = array('skin');
		break;
	case 'plugin':
		$blogMenu['title'] = _t('설정');
		$blogMenu['loadCSS'] = array('plugin');
		$blogMenu['topMenu'] = 'setting';
		break;
	case 'setting':
	case 'data':
		$blogMenu['title'] = _t('설정');
		$blogMenu['loadCSS'] = array('setting');
		break;
	case 'reader':
		$blogMenu['title'] = _t('리더');
		$blogMenu['loadCSS'] = array('reader');
		break;
	case 'control':
		$blogMenu['title'] = _t('서비스');
		$blogMenu['loadCSS'] = array('control');
		break;
}
// mapping data management to setting
if(isset($blogMenu['topMenu']) && $blogMenu['topMenu']=='data') $blogMenu['topMenu'] = 'setting';
$pluginListForCSS = array();
if ($blogMenu['topMenu'] == 'center' && $blogMenu['contentMenu'] == 'dashboard') {
	if (isset($centerMappings)) {
		foreach ($centerMappings as $tempPlugin) {
			array_push($pluginListForCSS, $tempPlugin['plugin']);
		}
	}
} else if ($blogMenu['topMenu'] == 'notes' && ($blogMenu['contentMenu'] == 'post' || $blogMenu['contentMenu'] == 'edit')) {
	if (isset($eventMappings['AddPostEditorToolbox'])) {
		foreach ($eventMappings['AddPostEditorToolbox'] as $tempPlugin) {
			array_push($pluginListForCSS, $tempPlugin['plugin']);
		}
	}
} else if (isset($pluginDir)) {
	array_push($pluginListForCSS, $pluginDir);
}
if ($blogMenu['topMenu'] == 'center' && $blogMenu['contentMenu'] == 'about') {
	$blogMenu['topMenu'] = 'setting';
}

unset($tempPlugin);

/***** Submenu generation *****/
if(isset($blogMenu['topMenu'])) {
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['center'] = array(
			array('menu'=>'dashboard','title'=>_t('대시보드'),'link'=>'/note/center/dashboard'),
		);
	} else{
		$blogContentMenuItem['center'] = array(
			array('menu'=>'dashboard','title'=>_t('대시보드'),'link'=>'/note/center/dashboard')
		);
	}
	if(Acl::check('group.editors')) {
		$blogContentMenuItem['notes'] = array(
			array('menu'=>'notes','title'=>_t('Notes'),'link'=>'/note/notes'),
			array('menu'=>'divider','title'=> '-','link'=>'/'),
			array('menu'=>'post','title'=>_t('Write'),'link'=>'/note/notes/post'),
			array('menu'=>'template','title'=>_t('Create template'),'link'=>'/note/notes/post?category=-4'),
			array('menu'=>'divider','title'=> '-','link'=>'/'),
			array('menu'=>'category','title'=>_t('Category'),'link'=>'/note/notes/category'),
			array('menu'=>'tag','title'=>_t('Tag'),'link'=>'/note/notes/tag')
		);
	} else {
		$blogContentMenuItem['notes'] = array(
			array('menu'=>'notes','title'=>_t('Notes'),'link'=>'/note/notes'),
			array('menu'=>'divider','title'=> '-','link'=>'/'),
			array('menu'=>'post','title'=>_t('Write'),'link'=>'/note/notes/post'),
			array('menu'=>'template','title'=>_t('Create template'),'link'=>'/note/notes/post?category=-4')
		);
	}
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['plugin'] = array(
			array('menu'=>'plugin','title'=>_t('Extensions'),'link'=>'/note/plugin')
		);
		if(Acl::check('group.creators')) array_push($blogContentMenuItem['plugin'], array('menu'=>'tableSetting','title'=>_t('플러그인 데이터 관리'),'link'=>'/note/plugin/tableSetting'));
	}
	if(Acl::check('group.administrators')) {
		$blogContentMenuItem['setting'] = array(
			array('menu'=>'plugin','title'=>_t('Extensions'),'link'=>'/note/plugin'),
			array('menu'=>'data','title'=>_t('Data'),'link'=>'/note/data'),
			array('menu'=>'about','title'=>_t('About'),'link'=>'/note/center/about')
		);
		if(Acl::check('group.creators')) array_push($blogContentMenuItem['plugin'], array('menu'=>'tableSetting','title'=>_t('플러그인 데이터 관리'),'link'=>'/note/plugin/tableSetting'));
		$blogContentMenuItem['plugin'] = $blogContentMenuItem['setting'];
	} else {
		$blogContentMenuItem['setting'] = array(
			array('menu'=>'plugin','title'=>_t('Extensions'),'link'=>'/note/plugin'),
			array('menu'=>'data','title'=>_t('Data'),'link'=>'/note/data'),
			array('menu'=>'about','title'=>_t('About'),'link'=>'/note/center/about')
		);
		$blogContentMenuItem['plugin'] = $blogContentMenuItem['setting'];
	}
}

if( empty($blogContentMenuItem) ) {
	echo _t('접근권한이 없습니다');
	exit;
}

if(!empty($adminMenuMappings )) {
	foreach($adminMenuMappings as $path => $pluginAdminMenuitem) {
		if(isset($blogContentMenuItem[$pluginAdminMenuitem['topMenu']])) {
			if(count($blogContentMenuItem[$pluginAdminMenuitem['topMenu']]) < $pluginAdminMenuitem['contentMenuOrder']
			  || $pluginAdminMenuitem['contentMenuOrder'] < 1)
				$pluginAdminMenuitem['contentMenuOrder'] = count($blogContentMenuItem[$pluginAdminMenuitem['topMenu']]);
			array_splice($blogContentMenuItem[$pluginAdminMenuitem['topMenu']], $pluginAdminMenuitem['contentMenuOrder'], 0,
				array(array('menu'=>'adminMenu?name='.$path,
				'title'=>$pluginAdminMenuitem['title'],
				'link'=>'/note/plugin/adminMenu?name='.$path))
			);
		}
	}
}

/***** Start header output *****/
?>
<!DOCTYPE html>
<html lang="<?php echo $context->getProperty('blog.language','ko');?>">
<head>
	<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Memorycube <?php echo htmlspecialchars($context->getProperty('blog.title'));?> &gt; <?php echo $blogMenu['title'];?></title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $context->getProperty('service.path').$context->getProperty('panel.skin');?>/basic.css" />
<?php
	$browser = Utils_Browser::getInstance();
	if($browser->isMobile()) {
?>
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no" />
<?php
	}
	if($context->getProperty('blog.useBlogIconAsIphoneShortcut') == true && file_exists(__TEXTCUBE_ATTACH_DIR__."/".$context->getProperty('blog.id')."/index.gif")) {
?>
	<link rel="apple-touch-icon" href="<?php echo $context->getProperty('uri.default')."/index.gif";?>" />
<?php
	}
// common CSS.
foreach($blogMenu['loadCSS'] as $loadCSS) {
?>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $context->getProperty('service.path').$context->getProperty('panel.skin');?>/<?php echo $loadCSS;?>.css" />
<?php
}

foreach ($pluginListForCSS as $tempPluginDir) {
	if (isset($tempPluginDir) && file_exists(ROOT . "/plugins/$tempPluginDir/plugin-main.css")) {
?>
	<link rel="stylesheet" type="text/css" href="<?php echo $context->getProperty('service.path');?>/plugins/<?php echo $tempPluginDir;?>/plugin-main.css" />
<?php
	}
}

unset($pluginListForCSS);
unset($tempPluginDir);
?>
	<![endif]-->
	<script type="text/javascript">
		//<![CDATA[
			var servicePath = "<?php echo $context->getProperty('service.path');?>";
			var blogURL = "<?php echo $context->getProperty('uri.blog');?>";
			var adminSkin = "<?php echo $context->getProperty('panel.skin');?>";
			var displayMode = "<?php echo $context->getProperty('blog.displaymode','desktop');?>";
			var workMode = "<?php echo $context->getProperty('blog.workmode','enhanced');?>";
<?php
if (in_array($blogMenu['contentMenu'],array('post','edit'))) {
	if(file_exists(ROOT.$context->getProperty('panel.editorTemplate'))) {
?>
			var editorCSS = "<?php echo $context->getProperty('panel.editorTemplate');?>";
<?php
	} else {
?>
			var editorCSS = "/resources/style/default-wysiwyg.css";
<?php
	}
}
include ROOT . '/resources/locale/messages.php';
?>
		//]]>
	</script>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/jquery/jquery-<?php echo JQUERY_VERSION;?>.js"></script>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/jquery/jquery.bpopup-<?php echo JQUERY_BPOPUP_VERSION;?>.js"></script>
	<script type="text/javascript">jQuery.noConflict();</script>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/EAF4.js"></script>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/common3.js"></script>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/owner.js"></script>
<?php
if(!in_array($blogMenu['contentMenu'],array('post','edit'))) {
?>
<!--		<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/jquery/jquery-ui-1.8.7.custom.min.js"></script>-->
<?php
}
if (file_exists(ROOT . $context->getProperty('panel.skin')."/custom.js")) {
?>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path').$context->getProperty('panel.skin');?>/custom.js"></script>
<?php
}
if( $context->getProperty('service.admin_script') !== null) {
	if( is_array($context->getProperty('service.admin_script')) ) {
		foreach( $context->getProperty('service.admin_script') as $src ) {
?>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/<?php echo $src;?>"></script>
<?php
		}
	} else {
?>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/<?php echo $context->getProperty('service.admin_script');?>"></script>
<?php
	}
}
if($blogMenu['topMenu']=='notes' && in_array($blogMenu['contentMenu'],array('post','edit','keylog','template','notice'))) {
?>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/editor3.js"></script>
<?php
}
echo fireEvent('ShowAdminHeader', '');

/** Get Help URL **/
$submenuURL = null;
if(strstr($blogMenu['contentMenu'], 'adminMenu?name=') !== false) { // Plugin.
	$submenuURL = $pluginMenuValue[0];
} else {
	$submenuURL = $blogMenu['contentMenu'];
}
$helpURL = $blogMenu['topMenu'].(isset($blogMenu['contentMenu']) ? '/'.$submenuURL : '');
$writer = User::getName();

?>
</head>
<body id="body-<?php echo $blogMenu['topMenu'];?>">
	<div id="tcDialog" style="display:none;"></div>
	<div id="temp-wrap">
		<div id="all-wrap">
			<div id="layout-header">
				<h1><?php echo _t('노트큐브 관리 페이지');?></h1>

				<div id="main-description-box">
					<ul id="main-description">
						<li id="description-blogger"><span class="text"><?php echo _f('환영합니다. <em>%1</em>님.', htmlspecialchars($writer));?></span></li>
					</ul>
				</div>

				<hr class="hidden" />

				<div id="main-blog-box">
					<div id="main-blog">
<?php
						if ('single' != $service['type'] ) {
?>
						<label for="blog-list"><?php echo _t('현재 노트');?></label>
<?php echo User::changeBlog();
						}
?>
					</div>
				</div>

				<hr class="hidden" />

				<h2><?php echo _t('메인메뉴');?></h2>

				<div id="main-menu-box">
					<ul id="main-menu">
						<li id="menu-textcube"><a href="<?php echo $context->getProperty('uri.blog').'/note/center/dashboard';?>" title="<?php echo _t('센터로 이동합니다.');?>"><span class="text"><?php echo _t('노트큐브');?></span></a></li>
<?php //echo User::changeBlog();?>
<?php
foreach($blogTopMenuItem as $menuItem) {
?>
						<li id="menu-<?php echo $menuItem['menu'];?>"<?php echo $menuItem['menu']==$blogMenu['topMenu'] ? ' class="selected"' : '';?>>
							<a href="<?php echo $context->getProperty('uri.blog').$menuItem['link'];?>" class="menu-name"><span><?php echo $menuItem['title'];?></span></a>
							<ul id="submenu-<?php echo $menuItem['menu'];?>" class="sub-menu">
<?php
	$firstChildClass = ' firstChild';
	if(isset($_POST['category']) && isset($_GET['category'])) {
		$_POST['category'] == $_GET['category'];
	}
/*	if (isset($_POST['category'])) $currentCategory = $_POST['category'];
	else if (isset($_GET['category'])) $currentCategory = $_GET['category'];
	else $currentCategory = null;*/
	if($blogMenu['contentMenu'] == 'post' && isset($_GET['category'])) {
		switch($_GET['category']) {
			case -1:
				$blogMenu['contentMenu'] = 'keylog';
				break;
			case -2:
				$blogMenu['contentMenu'] = 'notice';
				break;
			case -3:
				$blogMenu['contentMenu'] = 'page';
				break;
			case -4:
				$blogMenu['contentMenu'] = 'template';
				break;
			default:
		}
	}
	$currentCategory = null;
	if(isset($_POST['status'])) {
		if(($blogMenu['contentMenu'] == 'comment') && ($_POST['status'] == 'guestbook'))
			$blogMenu['contentMenu'] = 'guestbook';
		else if($blogMenu['contentMenu'] == 'trackback')
			$blogMenu['contentMenu'] = $blogMenu['contentMenu'].$_POST['status'];
	} else if(in_array($blogMenu['contentMenu'],array('trashcomment','trashtrackback'))) {
		$blogMenu['contentMenu'] = 'trash';
	} else if(in_array($blogMenu['contentMenu'],array('linkadd','linkedit','linkcategoryEdit','xfn'))) {
		$blogMenu['contentMenu'] = 'link';
	}
//	else if(in_array($blogMenu['contentMenu'],array('coverpage','sidebar')))
//		$blogMenu['contentMenu'] = 'widget';
	foreach($blogContentMenuItem[$menuItem['menu']] as &$contentMenuItem) {
		$PostIdStr = null;
		if(strstr($contentMenuItem['menu'], 'adminMenu?name=') !== false) {
			$pluginMenuValue = explode('/',substr($contentMenuItem['menu'], 15));
			$PostIdStr = $pluginMenuValue[0];
		} else {
			$PostIdStr = $contentMenuItem['menu'];
		}
		if($contentMenuItem['menu'] == 'divider') {
?>
								<li class="divider"><?php
		} else {
?>
								<li id="main-sub-menu-<?php echo $PostIdStr;?>"<?php echo
	((( $menuItem['menu'] == $blogMenu['topMenu'] && $blogMenu['contentMenu'] == $contentMenuItem['menu'])||
	(isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) ||
	($contentMenuItem['menu'] == 'add' && strpos($blogMenu['contentMenu'],'add') !== false) ||
	($contentMenuItem['menu'] == 'blog' && strpos($blogMenu['contentMenu'],'blog') !== false && strpos($blogMenu['contentMenu'],'teamblog') === false) ||
	($contentMenuItem['menu'] == 'user' && strpos($blogMenu['contentMenu'],'user') !== false) ||
	($blogMenu['contentMenu'] == 'edit' && $contentMenuItem['menu'] == 'post')) ?
		" class=\"selected{$firstChildClass}\"" : ($firstChildClass ? " class=\"$firstChildClass\"" : ''));?>><?php
		}
		if($contentMenuItem['menu'] != 'divider') {
?><a href="<?php
						echo $context->getProperty('uri.blog').
							$contentMenuItem['link'].
							($contentMenuItem['menu'] == 'post' && isset($currentCategory) ? '?category='.$currentCategory : '');
						?>"><span class="text"><?php echo $contentMenuItem['title'];?></span></a><?php
		} else {
?><span class="divider"><?php echo $contentMenuItem['title'];?></span><?php
		}
?></li>
<?php
		$firstChildClass = null;
	}
?>
							</ul>
						</li>
<?php
}
?>
					</ul>
				</div>
			</div>

			<hr class="hidden" />
<?php
/********** Submenu part. ***********/

if(!defined('__TEXTCUBE_READER_SUBMENU__')) {
?>
			<div id="layout-body">
<?php
}
?>
				<h2><?php echo isset($blogMenu['title']) ? _f('서브메뉴 : %1', $blogMenu['title']) : _t('서브메뉴');?></h2>

				<div id="sub-menu-box">
					<ul id="sub-menu">
<?php
	$firstChildClass = ' firstChild';
	$submenuURL = null;

	foreach($blogContentMenuItem[$blogMenu['topMenu']] as &$contentMenuItem) {
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
		if($contentMenuItem['menu'] == 'divider') {
?>
						<li class="divider"><span class="divider"><?php echo $contentMenuItem['title'];?></span><?php
		} else {
?>
						<li id="sub-menu-<?php echo $PostIdStr;?>"<?php echo
						(($blogMenu['contentMenu'] == $contentMenuItem['menu'] ||
							(isset($_GET['name']) && ('adminMenu?name='.$_GET['name'] == $contentMenuItem['menu'])) ||
							($contentMenuItem['menu'] == 'add' && strpos($blogMenu['contentMenu'],'add') !== false) ||
							($contentMenuItem['menu'] == 'blog' && strpos($blogMenu['contentMenu'],'blog') !== false && strpos($blogMenu['contentMenu'],'teamblog') === false) ||
							($contentMenuItem['menu'] == 'user' && strpos($blogMenu['contentMenu'],'user') !== false) ||
							($blogMenu['contentMenu'] == 'edit' && $contentMenuItem['menu'] == 'post')) ? " class=\"selected{$firstChildClass}\"" : ($firstChildClass ? " class=\"$firstChildClass\"" : ''));?>><?php
			if($contentMenuItem['menu'] == 'divider') {?><span class="divider"><?php echo $contentMenuItem['title'];?></span><?php
				} else {?><a title="<?php echo $contentMenuItem['title'];?>" href="<?php
						echo $context->getProperty('uri.blog').
							$contentMenuItem['link'].
							($contentMenuItem['menu'] == 'post' && isset($currentCategory) ? '?category='.$currentCategory : '');
						?>"><span class="text"><?php echo $contentMenuItem['title'];?></span></a><?php
				}
		}?></li>
<?php
		$firstChildClass = null;
	}

	$helpURL = $blogMenu['topMenu'].(isset($blogMenu['contentMenu']) ? '/'.$submenuURL : '');
?>
					</ul>
					<div id="custom-sub-menu">
					</div>
				</div>
<?php
if(!defined('__TEXTCUBE_READER_SUBMENU__')) {
?>
				<hr class="hidden" />

				<div id="pseudo-box">
					<div id="data-outbox">
<?php
}
?>
