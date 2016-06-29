<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

$context = Model_Context::getInstance();

$pluginListForCSS = array();
if (isset($eventMappings['AddPostEditorToolbox'])) {
	foreach ($eventMappings['AddPostEditorToolbox'] as $tempPlugin) {
		array_push($pluginListForCSS, $tempPlugin['plugin']);
	}
}
unset($tempPlugin);
?>
<!DOCTYPE html>
<html lang="<?php echo $context->getProperty('blog.language','ko');?>">
<head>
	<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($context->getProperty('blog.title'));?> &gt; <?php echo _t('글관리');?></title>
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $context->getProperty('service.path').$context->getProperty('panel.skin');?>/basic.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $context->getProperty('service.path').$context->getProperty('panel.skin');?>/post.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $context->getProperty('service.path').$context->getProperty('panel.skin');?>/popup-editor.css" />
<?php
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
	<script type="text/javascript">
		//<![CDATA[
			var servicePath = "<?php echo $context->getProperty('service.path');?>";
			var blogURL = "<?php echo $context->getProperty('uri.blog');?>";
			var adminSkin = "<?php echo $context->getProperty('panel.skin');?>";
			var displayMode = "<?php echo $context->getProperty('blog.displaymode','desktop');?>";
			var workMode = "<?php echo $context->getProperty('blog.workmode','enhanced');?>";
<?php
if(file_exists(ROOT.$context->getProperty('panel.editorTemplate'))) {
?>
			var editorCSS = "<?php echo $context->getProperty('panel.editorTemplate');?>";
<?php
} else {
?>
			var editorCSS = "/resources/style/default-wysiwyg.css";
<?php
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
?>
	<script type="text/javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/editor3.js"></script>
<?php
echo fireEvent('ShowAdminHeader', '');
?>
</head>
<body id="body-entry" class="popup-editor">
	<div id="temp-wrap">
		<div id="all-wrap">
			<div id="layout-header">
				<h1><?php echo _t('노트큐브 관리 페이지');?></h1>
			</div>

			<hr class="hidden" />
