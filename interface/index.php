<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)
//header("Location: /note/entry");
require ROOT . '/library/preprocessor.php';
$refererURI = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
// Redirect.
$_SESSION['refererURI'] = $refererURI;
header("Location: ".$context->getProperty('uri.blog')."/note/notes");
?>
