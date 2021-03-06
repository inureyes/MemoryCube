<?php
/// Copyright (c) 2004-2016, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

if (isset($_POST['page']))
	$_GET['page'] = $_POST['page'];

$IV = array(
	'GET' => array(
		'category' => array('int', 'mandatory' => false),
		'visibility' => array('string', 'mandatory' => false),
		'page' => array('int', 1, 'default' => 1),
		'tagId' => array('int', 1, 'mandatory' => false),
		'search' => array('string', 'mandatory' => false)
	),
	'POST' => array(
		'category' => array('int', 'mandatory' => false),
		'visibility' => array('string', 'mandatory' => false),
		'categoryAtHome' => array('int', 'mandatory' => false),
		'perPage' => array('int', 1, 'mandatory' => false),
		'search' => array('string', 'mandatory' => false),
		'withSearch' => array(array('on'), 'mandatory' => false)
	)
);
require ROOT . '/library/preprocessor.php';
importlib("model.blog.trash");
importlib("model.blog.entry");
importlib("model.blog.version");
trashVan();
publishEntries();

// 카테고리 설정.
if (isset($_POST['category'])) {
	$categoryId = $_POST['category'];
} else if (isset($_GET['category'])) {
	$categoryId = $_GET['category'];
} else if (isset($_POST['categoryAtHome'])) {
	$categoryId = $_POST['categoryAtHome'];
} else {
	$categoryId = -5;
}

// Open / Private 설정
if (isset($_GET['visibility'])) {
	$_POST['visibility'] = $_GET['visibility'];
}
$starred = $visibility = null;
$tabsClass = array();
if (isset($_POST['visibility'])) {
	if($_POST['visibility']=='public') {
		$visibility = '>=1';
		$tabsClass['public'] = true;
		$visibilityText = _t('Open');
	} else if($_POST['visibility']=='protected') {
		$visibility = '=1';
		$tabsClass['protected'] = true;
		$visibilityText = _t('보호');
	} else if($_POST['visibility']=='private') {
		$visibility = '0';
		$tabsClass['private'] = true;
		$visibilityText = _t('Private');
	} else if($_POST['visibility']=='reserved') {
		$visibility = '<-1';
		$tabsClass['reserved'] = true;
		$visibilityText = _t('예약');
	} else if($_POST['visibility']=='template') {
//		$categoryId = -4;
		$tabsClass['template'] = true;
		$visibilityText = _t('Template');
	} else if($_POST['visibility']=='starred') {
		$starred = 2;
		$tabsClass['starred'] = true;
		$visibilityText = _t('별표');
	} else if($_POST['visibility']=='draft') {
		$starred = 0;
		$tabsClass['draft'] = true;
		$visibilityText = _t('작성중');
	} else {
		$tabsClass['all'] = true;
		$visibilityText = _t('모든');
	}
} else {
	$tabsClass['all'] = true;
	$visibilityText = _t('모든');
	$_POST['visibility'] = '';
}

// 찾기 키워드 설정.
$searchKeyword = NULL;
if (isset($_POST['search']) && !empty($_POST['search']))
	$searchKeyword = trim($_POST['search']);
else if (isset($_GET['search']) && !empty($_GET['search']))
	$searchKeyword = trim($_GET['search']);

// 태그 목록 출력
if (isset($_GET['tagId']) && !empty($_GET['tagId'])) {
	$tag = intval($_GET['tagId']);
} else {
	$tag = null;
}

// 페이지당 출력되는 포스트 수.
$perPage = Setting::getBlogSettingGlobal('rowsPerPage', 50);
if ( isset($_POST['perPage']) && (in_array($_POST['perPage'], array(25, 50, 75, 100)))) {
	if ($_POST['perPage'] != $perPage) {
		Setting::setBlogSettingGlobal('rowsPerPage', $_POST['perPage']);
		$perPage = $_POST['perPage'];
	}
}

// 컨텐츠 목록 생성.
if(isset($_POST['visibility']) && $_POST['visibility'] == 'template') $categoryIdforPrint = -4;
else $categoryIdforPrint = $categoryId;	// preserves category selection even if template tab is activated.

list($entries, $paging) = getEntriesWithPagingForOwner(getBlogId(), $categoryIdforPrint, $searchKeyword, $suri['page'], $perPage, $visibility, $starred, null, $tag);

// query string 생성.
$paging['postfix'] = NULL;
if ($categoryId != 0) {
	$paging['postfix'] .= "&amp;category=$categoryId";
}
if (!empty($searchKeyword)) {
	$paging['postfix'] .= '&amp;search='.urlencode($searchKeyword);
}

$tab['postfix'] = $paging['postfix'];
if (isset($_POST['visibility'])) {
	$paging['postfix'] .= '&amp;visibility='.urlencode($_POST['visibility']);
}


$teamblog_users = null;
if(Acl::check('group.administrators')) {
	$teamblog_users = POD::queryAll("SELECT u.*, p.acl FROM {$database['prefix']}Users AS u INNER JOIN {$database['prefix']}Privileges AS p ON u.userid = p.userid AND p.blogid = $blogid ORDER BY p.acl DESC, name ASC");
}

require ROOT . '/interface/common/note/header.php';

?>
						<script type="text/javascript">
							//<![CDATA[
<?php
printScriptCheckTextcubeVersion();
?>

								function setEntryStar(entry, mark) {
									if ((mark < 0) || (mark > 2))
										return false;
									var request = new HTTPRequest("<?php echo $context->getProperty('uri.blog');?>/note/notes/star/" + entry + "?mark=" + mark);
									request.onSuccess = function () {
										switch (mark) {
											case 1:
												document.getElementById("starIcon_" + entry).innerHTML = '<a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/star/' + entry + '?command=mark" onclick="setEntryStar(' + entry + ', 2); return false;" title="<?php echo _t('별표를 줍니다.');?>"><span class="text"><?php echo _t('별표');?></span></a></span>';
												document.getElementById("starIcon_" + entry).className = 'unstar-icon';
												break;
											case 2:
												document.getElementById("starIcon_" + entry).innerHTML = '<a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/star/' + entry + '?command=unmark" onclick="setEntryStar(' + entry + ', 1); return false;" title="<?php echo _t('별표를 지웁니다.');?>"><span class="text"><?php echo _t('별표');?></span></a></span>';
												document.getElementById("starIcon_" + entry).className = 'star-icon';
												break;
										}
									}
									request.onError = function () {
										window.location = "<?php echo $context->getProperty('uri.blog');?>/note/notes";
									}
									request.send();
								}

								function deleteEntry(id) {
									if (!confirm("<?php echo _t('이 글 및 이미지 파일을 완전히 삭제합니다. 계속 하시겠습니까?');?>"))
										return;
									var request = new HTTPRequest("<?php echo $context->getProperty('uri.blog');?>/note/notes/delete/" + id);
									request.onSuccess = function () {
										document.getElementById('list-form').submit();
									}
									request.onError = function () {
										window.location.href = "<?php echo $context->getProperty('uri.blog');?>/note/notes/delete/" + id + "?command=delete";
									}
									request.send();
								}

								function checkAll(checked) {
									for (i = 0; document.getElementById('list-form').elements[i]; i++) {
										if (document.getElementById('list-form').elements[i].name == "entry") {
											if (document.getElementById('list-form').elements[i].checked != checked) {
												document.getElementById('list-form').elements[i].checked = checked;
												toggleThisTr(document.getElementById('list-form').elements[i]);
											}
										}
									}
									if(document.getElementById('allCheckedTop').checked != checked) {
										document.getElementById('allCheckedTop').checked = checked;
									}
									if(document.getElementById('allCheckedBottom').checked != checked) {
										document.getElementById('allCheckedBottom').checked = checked;
									}
								}

								function processBatchByCommand(cmd) {
									obj = new Object;
									obj.value = cmd;
									return processBatch(obj);
								}
								function processBatch(obj) {
									mode = obj.value;
									if (mode.match('^category_')) {
										mode = 'category';
									}

									var entries = '';
									var isSelected = false;
									for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
										var oElement = document.getElementById('list-form').elements[i];
										if ((oElement.name == "entry") && oElement.checked) {
											isSelected = true;
											break;
										}
									}
									if (!isSelected) {
										alert("<?php echo _t('적용할 글을 선택해 주십시오.');?>")
										return false;
									}
									switch (mode) {
										case 'classify':
											for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
												var oElement = document.getElementById('list-form').elements[i];
												if ((oElement.name == "entry") && oElement.checked) {
													if (document.getElementById("privateIcon_" + oElement.value).className == 'private-on-icon')
														continue;
													setEntryVisibility(oElement.value, 0);
												}
											}
											break;
										case 'publish':
											for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
												var oElement = document.getElementById('list-form').elements[i];
												if ((oElement.name == "entry") && oElement.checked) {
													if (document.getElementById("publicIcon_" + oElement.value).className == 'public-on-icon')
														continue;
													setEntryVisibility(oElement.value, 2);
												}
											}
											countSyndicated = true;
											break;
										case 'delete':
											if (!confirm("<?php echo _t('선택된 글 및 이미지 파일을 완전히 삭제합니다. 계속 하시겠습니까?');?>"))
												return false;
											var targets = new Array();
											for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
												var oElement = document.getElementById('list-form').elements[i];
												if ((oElement.name == "entry") && oElement.checked)
													targets[targets.length] = oElement.value;
											}
											var request = new HTTPRequest("POST", "<?php echo $context->getProperty('uri.blog');?>/note/notes/delete/");
											request.onSuccess = function () {
												document.getElementById('list-form').submit();
											}

											request.OnError = function () {
												alert("<?php echo _t('실패했습니다..');?>");
											}

											request.send("targets="+targets.join(","));
											break;
										case 'category':
											var targets = "";
											var currentCategory = "<?php echo $categoryId;?>";
											var category = obj.options[obj.options.selectedIndex].value.replace('category_', '');
											var label = obj.options[obj.options.selectedIndex].getAttribute('label');
											var request = new HTTPRequest("POST", "<?php echo $context->getProperty('uri.blog');?>/note/notes/changeCategory/");
											for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
												var oElement = document.getElementById('list-form').elements[i];
												if ((oElement.name == "entry") && oElement.checked) {
													targets += oElement.value + ',';
												}
											}
											targets = targets.substr(0,targets.length-1);

											if (targets == '') {
												return false;
											}

											request.onSuccess = function () {
												hrefString = "<?php echo $context->getProperty('uri.blog');?>/note/notes/";
												queryPage = document.getElementById('list-form').page.value;
												queryCategory = document.getElementById('category-form-top').category.value;
												querySearch = document.getElementById('search-form').search.value;

												queryString = "";
												if (queryPage != "") {
													queryString = "page=" + queryPage;
												}
												if (queryCategory != "") {
													if (queryString == "")
														queryString = queryCategory;
													else
														queryString = queryString + "&category=" + queryCategory;
												}
												if (querySearch != "") {
													if (queryString == "")
														queryString = querySearch;
													else
														queryString = queryString + "&search=" + querySearch;
												}
												if (queryString != "")
													queryString = "?" + queryString;

												// 공지/키워드 화면에서 선택된 포스트를 카테고리로 변경할 경우 or 카테고리 리스트에서 포스트를 공지/키워드 포스트로 전환할 경우.
												if (((category == -1 || category == -2 || category == -3) && currentCategory != category) || ((currentCategory == -1 || currentCategory == -2 || currentCategory == -3) && (category != -1 && category != -2 && category != -3))) {
													window.location.href = hrefString + queryString;
												} else {
													for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
														var oElement = document.getElementById('list-form').elements[i];
														if ((oElement.name == "entry") && oElement.checked) {
															id = "category_" + oElement.value;
															if (label == "<?php echo _t('Uncategorized');?>") {
																document.getElementById(id).className = "uncategorized";
															} else {
																document.getElementById(id).className = "categorized";
															}
															document.getElementById(id).innerHTML = label.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll("<", "&gt;");
														}
													}
												}
											}

											request.onError = function () {
												alert("<?php echo _t('분류를 변경하지 못했습니다.');?>");
											}

											// "분류없음"이 DB 상으로는 카테고리 "0"으로 설정되어야 함.
											if (category == -10)
												category = 0;
											request.send("category="+category+"&targets="+targets);
											break;
										case 'set_author':
											var targets = "";
											var authorId = "";
											authorId = document.getElementById('author-to-entry').value;
											if (authorId == '') return false;
											for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
												var oElement = document.getElementById('list-form').elements[i];
												if ((oElement.name == "entry") && oElement.checked) {
													targets += oElement.value + ',';
												}
											}
											targets = targets.substr(0,targets.length-1);
											if (targets == '') return false;

											var request = new HTTPRequest("POST", "<?php echo $context->getProperty('uri.blog');?>/note/notes/setAuthor/");
											request.onSuccess = function () {
												var resultResponse = this.getText("/response/error");
												var resultName = resultResponse ? this.getText("/response/name") : '';

												if (resultResponse && resultName != '')
												{
													for (var i = 0; i < document.getElementById('list-form').elements.length; i++) {
														var oElement = document.getElementById('list-form').elements[i];
														if ((oElement.name == "entry") && oElement.checked) {
															id = "author_" + oElement.value;
															document.getElementById(id).innerHTML = resultName;
														}
													}
												} else
													alert("<?php echo _t('글 작성자를 변경하지 못했습니다.');?>");
											}

											request.onError = function () {
												alert("<?php echo _t('글 작성자를 변경하지 못했습니다.');?>");
											}

											request.send("targets="+targets+"&userid="+authorId);
											break;
									}
									obj.selectedIndex = 0
								}

								function removeTrackbackLog(removeid,entry) {
									if (confirm("<?php echo _t('선택된 걸린글을 지웁니다. 계속 하시겠습니까?');?>")) {
										var id = removeid.replace(/trackbackRemove_/g,'');
										var request = new HTTPRequest("<?php echo $context->getProperty('uri.blog');?>/owner/communication/trackback/log/remove/" + id);
										request.onSuccess = function () {
											document.getElementById("logs_"+entry).innerHTML = "";
											printTrackbackLog(entry);
										}
										request.onError = function () {
											alert("<?php echo _t('걸린글을 지우지 못했습니다.');?>");
										}
										request.send();
									}
								}

								function printTrackbackLog(id) {
									var request = new HTTPRequest("<?php echo $context->getProperty('uri.blog');?>/owner/communication/trackback/log/" + id);
									request.onVerify = function () {
										var resultResponse = this.getText("/response/result");
										var resultRow = resultResponse.split('*');
										if (resultRow.length == 1)
											tempTable ='';
										else {
											tempTable = '';
											for (var i=0; i<resultRow.length-1 ; i++) {
												if  (i == 0) {
													tempTable = document.createElement("TABLE");
													tempTable.setAttribute("cellpadding", 0);
													tempTable.setAttribute("cellspacing", 0);
												}
												field = resultRow[i].split(',');

												tempTr = document.createElement("TR");
												tempTr.id = "trackbackLog_" + field[0];

												tempTd_1 = document.createElement("TD");
												tempTd_1.className = "address";
												tempTd_1.innerHTML = field[1];
												tempTr.appendChild(tempTd_1);

												tempTd_2 = document.createElement("TD");
												tempTd_2.className = "date";
												tempTd_2.innerHTML = field[2];
												tempTr.appendChild(tempTd_2);

												tempTd_3 = document.createElement("TD");
												tempTd_3.className = "remove";

												tempA = document.createElement("A");
												tempA.id = "trackbackRemove_" + field[0];
												tempA.className = "remove-button button";
												tempA.setAttribute("href", "#void");
												tempA.onclick = function() { removeTrackbackLog(this.id, id); return false };
												tempA.setAttribute("title", "<?php echo _t('이 걸린글을 삭제합니다.');?>");

												tempSpan = document.createElement("SPAN");
												tempSpan.className = "text";
												tempSpan.innerHTML = "delete";

												tempA.appendChild(tempSpan);
												tempTd_3.appendChild(tempA);
												tempTr.appendChild(tempTd_3);

												tempTbody = document.createElement("TBODY");
												tempTbody.appendChild(tempTr);

												tempTable.appendChild(tempTbody);
											}
										}
										if (tempTable != '' ) {
											document.getElementById("logs_"+id).appendChild(tempTable);
											document.getElementById("logs_"+id).style.display = "block";
										}
										return true;
									}
									request.send();
								}

								function showProtectSetter(id) {
									if (document.getElementById("entry" + id + "Protection")) {
										objTable = getParentByTagName("TABLE", getObject("protectedIcon_" + id));
										objTr = getParentByTagName("TR", getObject("protectedIcon_" + id));
										objTable.deleteRow(objTr.rowIndex + 1);

//										document.getElementById("protectedSettingIcon_" + id).className = "protect-off-button button";
									} else {
										var request = new HTTPRequest("<?php echo $context->getProperty('uri.blog');?>/note/notes/protect/getPassword/" + id);
										request.onSuccess = function () {
											objTable = getParentByTagName("TABLE", getObject("protectedIcon_" + id));
											objTr = getParentByTagName("TR", getObject("protectedIcon_" + id));

											newRow = objTable.insertRow(objTr.rowIndex + 1);
											newRow.id = "entry" + id + "Protection";
											newRow.className = "hidden-layer";

											newCell = newRow.insertCell(0);
											newCell.colSpan = 11;
											newCell.setAttribute("align", "right");
											newSection = document.createElement("DIV");
											newSection.className = "layer-section";
											newSection.innerHTML = '<label for="entry' + id + 'Password"><?php echo _t('비밀번호');?><\/label><span class="divider"> | <\/span><input type="text" id="entry' + id + 'Password" class="input-text input-password" value="' + this.getText("/response/password") + '" maxlength="16" onkeydown="if (event.keyCode == 13) protectEntry(' + id + ')" \/> ';

											tempLink = document.createElement("input");
											tempLink.type = "button";
											tempLink.className = "input-button";
											tempLink.onclick = function() { protectEntry(id); return false };
											tempLink.value = '<?php echo _t('수정');?>';

											newSection.appendChild(tempLink);

											newCell.appendChild(newSection);

//											document.getElementById("protectedSettingIcon_" + id).className = "protect-on-button button";
										}
										request.onError = function () {
											alert("비밀번호를 가져오지 못했습니다.");
										}
										request.send();
									}
									return;
								}

								function toggleDeleteButton(obj, position) {
									index = obj.selectedIndex;
									button = document.getElementById("apply-button-" + position);

									if (obj.options[index].value == "delete") {
										button.className = button.className.replace("apply-button", "delete-button");
										button.value = '<?php echo _t('삭제');?>';
									} else {
										button.className = button.className.replace("delete-button", "apply-button");
										button.value = '<?php echo _t('적용');?>';
									}
								}

								function toggleThisTr(obj) {
									objTR = getParentByTagName("TR", obj);

									if (objTR.className.match('inactive')) {
										objTR.className = objTR.className.replace('inactive', 'active');
									} else {
										objTR.className = objTR.className.replace('active', 'inactive');
									}
								}
								function openNote(url) {
									document.getElementById('editor-area').src = url+'?popupEditor=1';
								}
							//]]>
						</script>

						<div id="part-post-list" class="part">
							<h2 class="caption"><span class="main-text">
<?php
	if(isset($tabsClass['template']) && $tabsClass['template'] == true) {
		echo _t('Template 목록입니다');
	} else if ($categoryId == -1) {
		echo _f('%1 키워드 목록입니다', $visibilityText);
	} else if ($categoryId == -2) {
		echo _f('%1 공지 목록입니다', $visibilityText);
	} else if ($categoryId == -3) {
		echo _f('%1 페이지 목록입니다', $visibilityText);
	} else if ($categoryId == -5) {
		echo _f('페이지, 공지와 키로그를 포함한 %1 글의 목록입니다', $visibilityText);
	} else {
		echo _f('%1 글 목록입니다', $visibilityText);
	}
?>
							</span></h2>

							<form id="category-form-top" class="category-box" method="post" action="<?php echo $context->getProperty('uri.blog');?>/note/notes">
								<input type="hidden" name="page" value="<?php echo $suri['page'];?>" />
								<input type="hidden" name="visibility" value="<?php echo $_POST['visibility'];?>" />

								<ul id="entry-tabs-box" class="tabs-box">
									<li class="entry-post"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/post<?php echo (isset($_POST['category']) ? '?category='.$_POST['category'] : '')?>"><?php echo _t('새 글 쓰기');?></a></li>
									<li class="entry-all<?php echo isset($tabsClass['all']) ? ' selected' : NULL;?>"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes?page=1<?php echo $tab['postfix'];?>"><?php echo _t('Category');?></a>
										<label for="category"><?php echo _t('Category');?></label>
										<select id="category" name="category" onchange="document.getElementById('category-form-top').page.value=1; document.getElementById('category-form-top').submit()">
											<option value="-5"<?php echo ($categoryId == -5 ? ' selected="selected"' : '');?>><?php echo _t('All notes');?></option>
											<optgroup class="category" label="<?php echo _t('Kind');?>">
												<option value="-1"<?php echo ($categoryId == -1 ? ' selected="selected"' : '');?>><?php echo _t('Keyword');?></option>
												<option value="-4"<?php echo ($categoryId == -4 ? ' selected="selected"' : '');?>><?php echo _t('Template');?></option>
											</optgroup>
											<optgroup class="category" label="<?php echo _t('Category');?>">
												<option value="0"<?php echo ($categoryId == 0 ? ' selected="selected"' : '');?>><?php echo htmlspecialchars(getCategoryNameById($blogid,0) ? getCategoryNameById($blogid,0) : _t('All'), ENT_QUOTES, "UTF-8");?></option>
	<?php
	foreach (getCategories($blogid) as $category) {
		if ($category['id'] != 0) {
	?>
												<option value="<?php echo $category['id'];?>"<?php echo ($category['id'] == $categoryId ? ' selected="selected"' : '');?>><?php echo ($category['visibility'] > 1 ? '' : _t('(Private)')).htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?></option>
	<?php
		}
		foreach ($category['children'] as $child) {
			if ($category['id'] != 0) {
	?>
												<option value="<?php echo $child['id'];?>"<?php echo ($child['id'] == $categoryId ? ' selected="selected"' : '');?>>&nbsp;― <?php echo ($category['visibility'] > 1 && $child['visibility'] > 1 ? '' : _t('(Private)')).htmlspecialchars($child['name'], ENT_QUOTES, "UTF-8");?></option>
	<?php
			}
		}
	}
	?>
												<option value="-10"<?php echo ($categoryId == -10 ? ' selected="selected"' : '');?>><?php echo _t('(Uncategorized)');?></option>
											</optgroup>
										</select>
									</li>

									<li class="entry-starred<?php echo isset($tabsClass['starred']) ? ' selected' : NULL;?>"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes?page=1<?php echo $tab['postfix'];?>&amp;visibility=starred"><?php echo _t('별표');?></a></li>
									<li class="entry-private<?php echo isset($tabsClass['private']) ? ' selected' : NULL;?>"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes?page=1<?php echo $tab['postfix'];?>&amp;visibility=private"><?php echo _t('Private');?></a></li>
									<li class="entry-template<?php echo isset($tabsClass['template']) ? ' selected' : NULL;?>"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes?page=1<?php echo $tab['postfix'];?>&amp;visibility=template"><?php echo _t('Template');?></a></li>
								</ul>
							</form>


							<div id="change-section-top" class="section">
								<input type="checkbox" id="allCheckedTop" class="checkbox" onclick="checkAll(this.checked);" />
								<label for="allCheckedTop"></label>
								<span class="label"><?php echo _t('Selected notes');?></span>
								<select name="commandBoxTop" id="commandBoxTop" onchange="toggleDeleteButton(this, 'top');return false;">
									<option class="default" selected="selected"><?php echo _t('행동을 지정합니다.');?></option>
<?php
$categories = getCategories($blogid);
if (count($categories) >0) {
?>
									<optgroup class="category" label="<?php echo _t('아래의 분류로 변경합니다.');?>">
<?php
foreach ($categories as $category) {
	if ($category['id']!= 0) {
?>
										<option class="parent-category" value="category_<?php echo $category['id'];?>" label="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?>"><?php echo ($category['visibility'] > 1 ? '' : _t('(Private)')).htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?></option>
<?php
	}
	foreach ($category['children'] as $child) {
		if ($category['id']!= 0) {
?>
										<option class="child-category" value="category_<?php echo $child['id'];?>" label="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?>/<?php echo htmlspecialchars($child['name'], ENT_QUOTES, "UTF-8");?>">― <?php echo ($category['visibility'] > 1 && $child['visibility'] > 1 ? '' : _t('(Private)')).htmlspecialchars($child['name'], ENT_QUOTES, "UTF-8");?></option>
<?php
		}
	}
}
?>
										<option class="parent-category" value="category_-10" label="<?php echo _t('Uncategorized');?>">(<?php echo _t('Uncategorized');?>)</option>
									</optgroup>
<?php
}
?>
									<optgroup class="category" label="<?php echo _t('아래의 글 종류로 변경합니다.');?>">
										<option class="parent-category" value="category_-1" label="<?php echo _t('Keyword');?>"><?php echo _t('Keyword');?></option>
									</optgroup>
									<optgroup class="delete" label="<?php echo _t('Delete.');?>">
										<option value="delete"><?php echo _t('Delete.');?></option>
									</optgroup>
								</select>
								<input type="button" id="apply-button-top" class="apply-button input-button" value="<?php echo _t('Apply');?>" onclick="processBatch(document.getElementById('commandBoxTop'));" />

<?php
if(Acl::check('group.administrators')) {
	if (isset($teamblog_users) && count($teamblog_users) > 1) {
?>
								<?php echo _t('or');?>
								<select name="author-to-entry" id="author-to-entry">
									<option value=""><?=_t('Change author.')?></option>
<?php
		foreach($teamblog_users as $teamblog_user) {
			$tmpstr = '';
			if ($teamblog_user['acl'] & BITWISE_ADMINISTRATOR) $tmpstr .= _t('관리자');
			if ($teamblog_user['acl'] & BITWISE_OWNER) $tmpstr .= _t('소유자');
			if ($teamblog_user['acl'] & BITWISE_EDITOR) $tmpstr .= _t('글관리');
			$tmpstr = ($tmpstr?$tmpstr:_t('없음'));
?>
									<option value="<?php echo $teamblog_user['userid']?>"><?="{$teamblog_user['name']}($tmpstr)"?></option>
<?php
		}
?>
								</select>
								<input type="button" class="input-button" onclick="processBatchByCommand('set_author');return false;" value="<?php echo _t('변경');?>"/>
<?php
	}
}
?>
							</div>

							<form id="list-form" method="post" action="<?php echo $context->getProperty('uri.blog');?>/note/notes">
								<input type="hidden" name="category" value="<?php echo $categoryId;?>" />
<?php
$returnURLpostfix = '';
if(isset($_GET['page'])) $returnURLpostfix .= '?page='.$_GET['page'];
if(isset($_GET['category']) || ($categoryId != -5)) $returnURLpostfix .= (empty($returnURLpostfix) ? '?' : '&amp;').'category='.$categoryId;
if(isset($_POST['visibility'])) $returnURLpostfix .= (empty($returnURLpostfix) ? '?' : '&amp;').'visibility='.$_POST['visibility'];
?>
								<input type="hidden" name="returnURL" value="<?php echo $context->getProperty('uri.blog').'/note/notes'.$returnURLpostfix;?>" />
								<div id="list-edit-container">
								<div id="list-area">
								<table class="data-inbox" cellspacing="0" cellpadding="0">
									<thead>
										<tr>
											<th class="selection">&nbsp;</th>
											<th class="starred">&nbsp;</th>
											<th class="title"><span class="text"><?php echo _t('제목');?></span></th>
											<th class="category"><span class="text"><?php echo _t('분류');?></span></th>
											<th class="date"><span class="text"><?php echo _t('등록일자');?></span></th>
											<th class="status"><span class="text"><?php echo _t('상태');?></span></th>
											<th class="delete"><span class="text"><?php echo _t('삭제');?></span></th>
										</tr>
									</thead>
									<tbody>
<?php
if (sizeof($entries) == 0) {
?>
        <tr class="empty-list">
            <td colspan="11"><?php echo _t('Still empty.');?></td>
        </tr>
<?php
} else {
	for ($i=0; $i<sizeof($entries); $i++) {
		$entry = $entries[$i];

		$className = ($i % 2) == 1 ? 'even-line' : 'odd-line';
		$className .= ($i == sizeof($entries) - 1) ? ' last-line' : '';
		if ($entry['category'] == -1)
			$className .= ' keyword-line';
		else if ($entry['category'] == -2)
			$className .= ' notice-line';
		else if ($entry['category'] == -3)
			$className .= ' page-line';
        if ($entry['draft']) {
            $className .= ' draft';
        }
?>
										<tr class="<?php echo $className;?> inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
											<td class="selection">
												<input id="entryCheckId<?php echo $entry['id'];?>" type="checkbox" class="checkbox" name="entry" value="<?php echo $entry['id'];?>" onclick="document.getElementById('allCheckedTop').checked=false;document.getElementById('allCheckedBottom').checked=false; toggleThisTr(this);" />
												<label for="entryCheckId<?php echo $entry['id'];?>"></label>
											</td>
											<td class="starred"><?php
	if($entry['starred'] == 2) {
?>
												<span id="starIcon_<?php echo $entry['id'];?>" class="star-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/star/<?php echo $entry['id'];?>?command=unmark" onclick="setEntryStar(<?php echo $entry['id'];?>, 1); return false;" title="<?php echo _t('별표를 지웁니다.');?>"><span class="text"><?php echo _t('별표');?></span></a></span>
<?php
	} else {
?>
												<span id="starIcon_<?php echo $entry['id'];?>" class="unstar-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/star/<?php echo $entry['id'];?>?command=mark" onclick="setEntryStar(<?php echo $entry['id'];?>, 2); return false;" title="<?php echo _t('별표를 줍니다.');?>"><span class="text"><?php echo _t('별표');?></span></a></span>
<?php
	}
?></td>
											<td class="title">
												<?php echo ($entry['draft'] ? ('<span class="temp-icon bullet" title="' . _t('임시 저장본이 있습니다.') . '"><span>' . _t('[임시]') . '</span></span> ') : '');?>
<?php
	$editmode = 'notes';
	$entryModifyLink = $entry['id'];
	$contentLength = 150-Utils_Unicode::lengthAsEm(htmlspecialchars($entry['title'], ENT_QUOTES, "UTF-8"));
?>
												<a href="#" onclick="openNote('<?php echo $context->getProperty('uri.blog');?>/note/<?php echo $editmode;?>/edit/<?php echo $entryModifyLink;?>');"><?php echo htmlspecialchars($entry['title'], ENT_QUOTES, "UTF-8");?></a>
												<span class="description"><?php echo (($contentLength > 0) ? Utils_Unicode::lessenAsEm(removeAllTags(strip_tags($entry['content'])),$contentLength) : '');?></span>
											</td>
											<td class="category">
<?php
	if ($entry['category'] == 0) {
?>
<a id="category_<?php echo $entry['id'];?>" class="uncategorized" href="<?php echo $context->getProperty('uri.blog');?>/note/notes?category=-10"><?php echo _t('Uncategorized');?><?php echo ($entry['visibility'] < 0 ? '('._t('예약된 글').')' : '');?></a>
<?php
	} else if (!empty($entry['categoryLabel'])) {
?>
												<a id="category_<?php echo $entry['id'];?>" class="categorized" href="<?php echo $context->getProperty('uri.blog');?>/note/notes?category=<?php echo $entry['category'];?>"><?php echo htmlspecialchars($entry['categoryLabel'], ENT_QUOTES, "UTF-8");?><?php echo ($entry['visibility'] < 0 ? '('._t('예약된 글').')' : '');?></a>
<?php
	} else if ($entry['category'] == -3) {
?>
												<a id="category_<?php echo $entry['id'];?>" class="page" href="<?php echo $context->getProperty('uri.blog');?>/note/notes?category=-3"><?php echo _t('페이지');?><?php echo ($entry['visibility'] < 0 ? '('._t('예약된 글').')' : '');?></a>
<?php
	} else if ($entry['category'] == -2) {
?>
												<a id="category_<?php echo $entry['id'];?>" class="notice" href="<?php echo $context->getProperty('uri.blog');?>/note/notes?category=-2"><?php echo _t('공지');?><?php echo ($entry['visibility'] < 0 ? '('._t('예약된 글').')' : '');?></a>
<?php
	} else if ($entry['category'] == -1) {
?>
												<a id="category_<?php echo $entry['id'];?>" class="keyword" href="<?php echo $context->getProperty('uri.blog');?>/note/notes?category=-1"><?php echo _t('키워드');?><?php echo ($entry['visibility'] < 0 ? '('._t('예약된 글').')' : '');?></a>
<?php
	} else if ($entry['category'] == -4) {
?>
												<a id="category_<?php echo $entry['id'];?>" class="template" href="<?php echo $context->getProperty('uri.blog');?>/note/notes?category=-4"><?php echo _t('Template');?><?php echo ($entry['visibility'] < 0 ? '('._t('예약된 글').')' : '');?></a>
<?php
	}
?>
											</td>
											<td class="date"><?php echo Timestamp::formatDate($entry['published']);?></td>
											<td class="status">
<?php
	if($entry['category'] == -4) {
?>
												<span id="templateInstruction"><?php echo _t('Template');?></span>
<?php

	} else if ($entry['visibility'] == 0) {
?>
												<span id="privateIcon_<?php echo $entry['id'];?>" class="private-on-icon" title="<?php echo _t('현재 Private 상태입니다.');?>"><span class="text"><?php echo _t('Private');?></span></span>
												<span id="protectedIcon_<?php echo $entry['id'];?>" class="protected-off-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>?command=protect" onclick="setEntryVisibility(<?php echo $entry['id'];?>, 1); return false;" title="<?php echo _t('현재 상태를 보호로 전환합니다.');?>"><span class="text"><?php echo _t('보호');?></span></a></span>
												<span id="publicIcon_<?php echo $entry['id'];?>" class="public-off-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>?command=public" onclick="setEntryVisibility(<?php echo $entry['id'];?>, 2); return false;" title="<?php echo _t('현재 상태를 Open로 전환합니다.');?>"><span class="text"><?php echo _t('Open');?></span></a></span>
<?php
	} else if ($entry['visibility'] == 1) {
?>
												<span id="privateIcon_<?php echo $entry['id'];?>" class="private-off-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>?command=private" onclick="setEntryVisibility(<?php echo $entry['id'];?>, 0); return false;" title="<?php echo _t('현재 상태를 Private로 전환합니다.');?>"><span class="text"><?php echo _t('Private');?></span></a></span>
												<span id="protectedIcon_<?php echo $entry['id'];?>" class="protected-on-icon" title="<?php echo _t('현재 보호 상태입니다.');?>"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>#status-line" onclick="showProtectSetter('<?php echo $entry['id'];?>'); return false;" title="<?php echo _t('보호 패스워드를 설정합니다.');?>"><span class="text"><?php echo _t('보호설정');?></span></a></span>
												<span id="publicIcon_<?php echo $entry['id'];?>" class="public-off-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>?command=public" onclick="setEntryVisibility(<?php echo $entry['id'];?>, 2); return false;" title="<?php echo _t('현재 상태를 Open로 전환합니다.');?>"><span class="text"><?php echo _t('Open');?></span></a></span>
<?php
	} else if ($entry['visibility'] == 2 || $entry['visibility'] == 3) {
?>
												<span id="privateIcon_<?php echo $entry['id'];?>" class="private-off-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>?command=private" onclick="setEntryVisibility(<?php echo $entry['id'];?>, 0); return false;" title="<?php echo _t('현재 상태를 Private로 전환합니다.');?>"><span class="text"><?php echo _t('Private');?></span></a></span>
												<span id="protectedIcon_<?php echo $entry['id'];?>" class="protected-off-icon"><a href="<?php echo $context->getProperty('uri.blog');?>/note/notes/visibility/<?php echo $entry['id'];?>?command=protect" onclick="setEntryVisibility(<?php echo $entry['id'];?>, 1); return false;" title="<?php echo _t('현재 상태를 보호로 전환합니다.');?>"><span class="text"><?php echo _t('보호');?></span></a></span>
												<span id="publicIcon_<?php echo $entry['id'];?>" class="public-on-icon" title="<?php echo _t('현재 Open 상태입니다.');?>"><span class="text"><?php echo _t('Open');?></span></span>
<?php
	} else {
?>
												<span id="privateIcon_<?php echo $entry['id'];?>" class="private-off-icon"><span class="text"><?php echo _t('Private');?></span></span>
												<span id="protectedIcon_<?php echo $entry['id'];?>" class="protected-off-icon"><span class="text"><?php echo _t('보호');?></span></span>
												<span id="publicIcon_<?php echo $entry['id'];?>" class="public-off-icon"><span class="text"><?php echo _t('Open');?></span></span>
<?php
	}
?>
											</td>
											<td class="delete">
												<a class="delete-button button" href="<?php echo $context->getProperty('uri.blog');?>/note/notes/delete/<?php echo $entry['id'];?>" onclick="deleteEntry(<?php echo $entry['id'];?>); return false;" title="<?php echo _t('이 포스트를 삭제합니다.');?>"><span class="text"><?php echo _t('삭제');?></span></a>
											</td>
										</tr>
<?php
	}
}
?>
									</tbody>
								</table>
								</div>
								<iframe src="<?php echo $context->getProperty('uri.blog');?>/note/notes/edit/1?popupEditor=1" style="height:calc(100vh - 200px);" frameBorder="0" width="100%"  id="editor-area"></iframe>
							</div>
								<hr class="hidden" />

								<div class="data-subbox">
									<input type="hidden" name="page" value="<?php echo $suri['page'];?>" />

									<div id="change-section-bottom" class="section">
										<input type="checkbox" id="allCheckedBottom" class="checkbox" onclick="checkAll(this.checked);" />
										<label for="allCheckedBottom"></label>
										<span class="label"><?php echo _t('Selected notes');?></span>
										<select name="commandBoxBottom" id="commandBoxBottom" onchange="toggleDeleteButton(this, 'bottom');return false;">
											<option class="default" selected="selected"><?php echo _t('Action');?></option>
<?php
	$categories = getCategories($blogid);
	if (count($categories) >0) {
?>
											<optgroup class="category" label="<?php echo _t('아래의 분류로 변경합니다.');?>">
<?php
		foreach ($categories as $category) {
			if ($category['id']!= 0) {
?>
												<option class="parent-category" value="category_<?php echo $category['id'];?>" label="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?>"><?php echo ($category['visibility'] > 1 ? '' : _t('(Private)')).htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?></option>
<?php
			}
			foreach ($category['children'] as $child) {
				if ($category['id']!= 0) {
?>
												<option class="child-category" value="category_<?php echo $child['id'];?>" label="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, "UTF-8");?>/<?php echo htmlspecialchars($child['name'], ENT_QUOTES, "UTF-8");?>">― <?php echo ($category['visibility'] > 1 && $child['visibility'] > 1 ? '' : _t('(Private)')).htmlspecialchars($child['name'], ENT_QUOTES, "UTF-8");?></option>
<?php
				}
			}
		}
?>
												<option class="parent-category" value="category_-10" label="<?php echo _t('Uncategorized');?>">(<?php echo _t('Uncategorized');?>)</option>
											</optgroup>
<?php
	}
?>
											<optgroup class="category" label="<?php echo _t('아래의 글 종류로 변경합니다.');?>">
												<option class="parent-category" value="category_-1" label="<?php echo _t('키워드');?>"><?php echo _t('키워드');?></option>
											</optgroup>
											<optgroup class="delete" label="<?php echo _t('삭제합니다.');?>">
												<option value="delete"><?php echo _t('삭제합니다.');?></option>
											</optgroup>
										</select>
										<input type="button" id="apply-button-bottom" class="apply-button input-button" value="<?php echo _t('적용');?>" onclick="processBatch(document.getElementById('commandBoxBottom'));" />
									</div>

									<hr class="hidden" />

									<div id="page-section" class="section">
										<h2><?php echo _t('페이지 네비게이션');?></h2>

										<div id="page-navigation">
											<span id="page-list">
<?php
$pagingTemplate = '[##_paging_rep_##]';
$pagingItemTemplate = '<a [##_paging_rep_link_##]>[##_paging_rep_link_num_##]</a>';
echo str_repeat("\t", 12).Paging::getPagingView($paging, $pagingTemplate, $pagingItemTemplate, false).CRLF;
?>
											</span>
											<span id="total-count"><?php echo _f('Total %1 notes', empty($paging['total']) ? "0" : $paging['total']);?></span>
										</div>
										<div class="page-count">
											<?php echo Utils_Misc::getArrayValue(explode('%1', _t('한 페이지에 글 %1건 표시')), 0);?>

											<select name="perPage" onchange="document.getElementById('list-form').page.value=1; document.getElementById('list-form').submit()">
<?php
for ($i = 25; $i <= 100; $i += 25) {
	if ($i == $perPage) {
?>
												<option value="<?php echo $i;?>" selected="selected"><?php echo $i;?></option>
<?php
	} else {
?>
												<option value="<?php echo $i;?>"><?php echo $i;?></option>
<?php
	}
}
?>
											</select>
											<?php echo Utils_Misc::getArrayValue(explode('%1', _t('Display %1 notes for page')), 1).CRLF;?>
										</div>
									</div>
								</div>
							</form>

							<hr class="hidden" />

							<form id="search-form" class="data-subbox" method="post" action="<?php echo $context->getProperty('uri.blog');?>/note/notes">
								<h2><?php echo _t('검색');?></h2>

								<div class="section">
									<label for="search"><?php echo _t('제목');?>, <?php echo _t('내용');?></label>
									<input type="text" id="search" class="input-text" name="search" value="<?php echo htmlspecialchars($searchKeyword, ENT_QUOTES, "UTF-8");?>" onkeydown="if (event.keyCode == '13') { document.getElementById('search-form').withSearch.value = 'on'; document.getElementById('search-form').submit(); }" />
									<input type="hidden" name="withSearch" value="" />
									<input type="submit" class="search-button input-button" value="<?php echo _t('검색');?>" onclick="document.getElementById('search-form').withSearch.value = 'on'; document.getElementById('search-form').submit();" />
								</div>
							</form>
						</div>
<?php
require ROOT . '/interface/common/note/footer.php';
?>
