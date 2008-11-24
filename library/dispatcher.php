<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

/** Dispatcher
    ----------
*/
/** Loading Basic Components */
require_once (ROOT.'/library/components/Needlworks.PHP.BaseClasses.php');
require_once (ROOT.'/library/components/Needlworks.PHP.Loader.php');

/** Input validation */

/** Loading Configuration */
global $config, $context;
//		global $serviceURL, $pathURL, $defaultURL, $baseURL, $pathURL, $hostURL, $folderURL, $blogURL;
//		global $suri, $blog, $blogid, $skinSetting, $gCacheStorage;
		
$config = Config::getInstance();
$context = Context::getInstance(); // automatic initialization via first instanciation

/** Loading debug module */

/** Loading components / models / views */
require_once (ROOT.'/library/include.'.$context->accessInfo['interfaceType'].'.php');
require_once (ROOT.'/library/include.php');

/** Sending header */
header('Content-Type: text/html; charset=utf-8');
/** Database I/O initialization. */
if(!empty($database) && !empty($database["database"])) {
	if(POD::bind($database) === false) {
		Respond::MessagePage('Problem with connecting database.<br /><br />Please re-visit later.');
		exit;
	}
}
$database['utf8'] = (POD::charset() == 'utf8') ? true : false;
/** Memcache module bind (if possible) */
$memcache = null;
if(!empty($config->database) && !empty($config->service['memcached']) && $config->service['memcached'] == true): 
	$memcache = new Memcache;
	$memcache->connect((isset($memcached['server']) && $memcached['server'] ? $memcached['server'] : 'localhost'));
endif;

/** Parse URI and gather blogID and URI parameters */
$context->URIParser();
/** Setting global variables */
$context->globalVariableParser();

/** Initializing Locale Resources */
$__locale = array(
	'locale' => null,
	'directory' => './locale',
	'domain' => null,
	);

// Set timezone.
if(isset($config->database) && !empty($config->database['database'])) {
	$timezone = new Timezone;
	$timezone->set(isset($blog['timezone']) ? $blog['timezone'] : $config->service['timezone']);
	POD::query('SET time_zone = \'' . $timezone->getCanonical() . '\'');
}

// Load administration panel locale.
if(!defined('NO_LOCALE')) {
	Locale::setDirectory(ROOT . '/resources/language');
	Locale::set(isset($blog['language']) ? $blog['language'] : $service['language']);

	// Load blog screen locale.
	if (!isset($blog['blogLanguage'])) {
		$blog['blogLanguage'] = $service['language'];
	}
	Locale::setSkinLocale(isset($blog['blogLanguage']) ? $blog['blogLanguage'] : $service['language']);
}

/** Initializing Session */
if (!defined('NO_SESSION')) {
	session_name(Session::getName());
	Session::set();
	session_set_save_handler( array('Session','open'), array('Session','close'), array('Session','read'), array('Session','write'), array('Session','destroy'), array('Session','gc') );
	session_cache_expire(1);
	session_set_cookie_params(0, '/', $service['domain']);
	if (session_start() !== true) {
		header('HTTP/1.1 503 Service Unavailable');
	}
}
  
/** Plugin module initialization (if necessary) */ 
if(in_array($context->accessInfo['interfaceType'], array('blog','owner','reader'))) {
	require_once(ROOT.'/library/plugins.php');
}

/** Access privilege Check */
header('Content-Type: text/html; charset=utf-8');
if($context->accessInfo['interfaceType'] == 'blog' && !defined('__TEXTCUBE_LOGIN__')) {
	$blogVisibility = Setting::getBlogSettingGlobal('visibility',2);
	if($blogVisibility == 0) requireOwnership();
	else if($blogVisibility == 1) requireMembership();
}

if(in_array($context->accessInfo['interfaceType'], array('owner','reader'))) {
	requireOwnership();     // Check access control list
	require ROOT .'/library/pageACL.php';
}
?>
