<?php
/* Subscription statistics plugin for Textcube 1.7
   ----------------------------------
   Version 2.1 
   Needlworks development team.

   Creator          : inureyes
   Maintainer       : gendoh, inureyes, graphittie

   Created at       : 2006.9.21
   Last modified at : 2008.4.18
 
 This plugin shows RSS subscription statistics on administration menu.
 For the detail, visit http://forum.tattersite.com/ko


 General Public License
 http://www.gnu.org/licenses/gpl.html

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

*/
function PN_Subscription_Default()
{
	global $pluginMenuURL, $pluginSelfParam, $totalSubscribers, $updatedSubscribers;
	requireComponent( "Textcube.Model.Statistics");
	requireComponent( "Textcube.Function.misc");
	$blogid = getBlogId();
	$temp = getSubscriptionStatistics($blogid);
	$aggregatorInfo = organizeAggregatorInfo($temp);
	Model_Setting::setBlogSetting('SubscriberCount',$totalSubscribers);
	
?>
						<script type="text/javascript">
							//<![CDATA[
								window.addEventListener("load", execLoadFunction, false);
								
								function execLoadFunction() {
									//removeItselfById('log-pages-submit');
								}
							//]]>
						</script>
						<div id="part-statistics-total" class="part">
							<h2 class="caption"><span class="main-text">전체 피드 통계</span></h2>
							<dl class="data-inbox">
								<dt class="number"><span class="text">전체 구독자수</span></dt>
								<dd class="number"><span class="text"><?php echo $totalSubscribers;?> 명</span></dd>
								<dt class="aggregator"><span class="text">구독기</span></dt>
								<dd class="aggregator"><span class="text"><?php echo sizeof($aggregatorInfo);?> 종류의 구독기 및 크롤러가 구독중입니다.</span></dd>
								<dt class="lastRSSupdate"><span class="text">최종 RSS 갱신일</span></dt>
								<dd class="lastRSSupdate"><span class="text"><?php echo Model_Setting::getBlogSetting('LatestRSSrefresh',null)!==null ? Timestamp::format5(Model_Setting::getBlogSetting('LatestRSSrefresh',null)) : '정보가 갱신되지 않았습니다';?></span></dd>
								<dt class="updatedAggregators"><span class="text">이후 갱신된 RSS 구독기</span></dt>
								<dd class="updatedAggregators"><span class="text"><?php echo $updatedSubscribers;?></span></dd>
							</dl>
						</div>

						<hr class="hidden" />

						<div id="part-statistics-rank" class="part">
							<h2 class="caption"><span class="main-text">피드 구독 순위</span></h2>
							<div class="main-explain-box">
								<p class="explain">
									크롤러에 구독자 수 정보를 넣지 않는 웹 RSS 리더의 경우 정상적인 구독자수를 판별할 수 없습니다.<br />
									강조 표시된 구독기는 검색 엔진 및 메타 프로그램을 의미합니다.
								</p>
							</div>
							<table class="data-inbox" cellspacing="0" cellpadding="0">
								<thead>
									<tr>
										<th class="rank"><span class="text">순위</span></th>
										<th class="aggregator"><span class="text">구독기</span></th>
										<th class="count"><span class="text">구독자 수</span></th>
										<th class="subscribed"><span class="text">구독 시작일</span></th>
										<th class="referred"><span class="text">최근 구독일</span></th>
									</tr>
								</thead>
								<tbody>
<?php
	$i = 0;
	foreach ($aggregatorInfo as $agent => $info) {
		$className = ($i % 2) == 1 ? 'even-line' : 'odd-line';
		$className .= ($i == sizeof($aggregatorInfo) - 1) ? ' last-line' : '';
?>
									<tr class="<?php echo $className;?> inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
										<td class="rank"><?php echo $i + 1;?></td>
										<td class="aggregator"><?php 
		if($info['isRobot'])
			echo '<span class="robot">'.$agent.'</span>';
		else echo $agent;
?></td>
										<td class="count"><?php echo $info["subscribers"];?>명</td>
										<td class="subscribed"><?php echo Timestamp::formatDate($info["subscribed"]);?></td>
										<td class="referred"><?php echo Timestamp::formatDate($info["referred"]);?></td>
									</tr>
<?php
		$i++;
	}
?>
								</tbody>
							</table>
						</div>
						
						<div class="clear"></div>
<?php 
}

function getAggregatorName($useragent)
{
	if($useragent=='') return '알 수 없는 구독기';
	$agentPattern = array(
		'Bloglines' => 'Bloglines',
		'Allblog.net' => '올블로그 피드 로봇',
		'HanRSS' => '한RSS',
		'Netvibes' => 'Netvibes',
		'SharpReader' => 'Sharp Reader',
		'BlogBridge' => 'Blog Bridge',
		'Firefox' => 'Firefox 라이브북마크',
		'Fastladder' => 'Fastladder',
		'Sage' => 'Sage (Firefox 확장)',
		'NewsGatorOnline' => 'NewsGator',
		'NewsLife' => 'NewsLife',
		'Google Desktop' => '구글 데스크탑',
		'RSSOwl' => 'RSS Owl',
		'Eolin' => '태터툴즈 리더',
		'Textcube' => '텍스트큐브 리더',
		'Safari' => '사파리',
		'NetNewsWire' => 'NetNewsWire',
		'Feedfetcher-Google' => '구글 feedfetcher',
		'RssBandit' => 'RSS Bandit',
		'Yahoo! Slurp' => 'Yahoo! Slurp',
		'Mozilla/4.0 (compatible; MSIE 7.0' => 'MS 익스플로러 7',
		'FeedDemon' => 'FeedDemon',
		'UniversalFeedParser' => 'Universal Feed Parser',
		'nhn/1noon' => '첫눈',
		'MSIE 6.0' => 'MS 익스플로러 6',
		'YeonMo' => '연모',
		'RMOM' => '요줌',
		'msnbot' => 'MSN 검색엔진',
		'FeedOnFeeds' => 'Feed On Feeds Personal aggregator',
		'Technoratibot' => '테크노라티',
		'sproose' => 'sproose 봇',
		'Thunderbird' => 'Mozilla Thunderbird',
		'NaverBot' => '네이버 검색로봇',
		'DAUM RSS Robot' => '다음 RSS 검색로봇',
		'Googlebot' => '구글 검색로봇',
		'TechnoratiSnoop' => '테크노라티 피드 로봇',
		'CazoodleBot' => 'CazoodleBot',
		'Snapbot' => 'Snapbot (snap.com 서비스용)',
		'UCLA CS Dept' => '연구용 로봇 (UCLA 컴퓨터공학과)',
		'Windows-RSS-Platform/1.0 (MSIE 7.0' => '윈도우 비스타 RSS 개짓',
		'HTTPClientBox' => 'HTTPClientBox',
		'ONNET-OPENAPI' => '온네트 API 로봇',
		'S20 Wing' => '날개 피드 로봇',
		'FeedBurner' => 'FeedBurner 피드 로봇',
		'xMind' => '크로스마인드(xMind) 검색 로봇',
		'openmaru feed aggregator' => 'Openmaru Feed Aggregator',
		'ColFeed' => '콜콜넷 피드 로봇'
	);
	$declinePattern = array(
		//법칙이 있으면 사용하겠는데, 제멋대로다...
		'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'=>'Internet Explorer 5.01',
		'Mozilla/4.0 (compatible; MS; Windows NT 5.0'=>'Internet Explorer',
		'Mozilla/4.0 (compatible;MSIE 5.5; Windows NT 5.0'=>'Internet Explorer'
	);
	foreach ($agentPattern as $agentName => $realname)
		if(strpos($useragent,$agentName)!==false) return $realname;
	foreach ($declinePattern as $agentName => $realname)
		if(strpos($useragent,$agentName)!==false) return false;
	return $useragent;
}

function getNumberOfSubscribers($useragent)
{
	$agentPattern = array(
		'Bloglines' => 'subscribers',
		'HanRSS' => 'subscribers',
		'Feedfetcher-Google' => 'subscribers',
		'Netvibes' => 'subscribers',
		'NewsGatorOnline' => 'subscribers',
		'Fastladder' => 'subscribers'
	);
	foreach ($agentPattern as $agentName => $keyword)
		if(preg_match('/([0-9]+)\s*'.$keyword.'/',$useragent,$matches)) return $matches[1];
	return 1;
}

function robotChecker($useragent)
{
	$robotPattern = array(
		'Googlebot' => 1,
		'NaverBot' => 1,
		'TechnoratiSnoop' => 1,
		'Allblog.net' => 1,
		'CazoodleBot' => 1,
		'nhn/1noon' => 1,
		'Feedfetcher-Google' => 1,
		'Yahoo! Slurp' => 1,
		'RMOM' => 1,
		'msnbot' => 1,
		'Technoratibot' => 1,
		'sproose' => 1,
		'CazoodleBot' => 1,
		'ONNET-OPENAPI' => 1,
		'UCLA CS Dept' => 1,
		'Snapbot' => 1,
		'DAUM RSS Robot' => 1,
		'RMOM' => 1,
		'S20 Wing' => 1,
		'FeedBurner' => 1,
		'xMind' => 1,
		'openmaru feed aggregator' => 1,
		'ColFeed' => 1
	);
	foreach ($robotPattern as $agentName => $isRobot)
		if((strpos($useragent,$agentName)!==false)&&($isRobot)) return true;
	return false;
}

function organizeAggregatorInfo($info)
{
	requireComponent( "Textcube.Function.misc");
	global $totalSubscribers, $updatedSubscribers;
	$aggregatorInfo = array();
	$totalSubscribers = 0;
	$updatedSubscribers = 0;
	$latestUpdatedTime = Model_Setting::getBlogSetting('LatestRSSrefresh',null);
	for ($i=0; $i<sizeof($info); $i++) {
		$record = $info[$i];
		$aggregatorName = getAggregatorName($record['useragent']);
		if(!$aggregatorName) continue;
		$subscribers = getNumberOfSubscribers($record['useragent']);
		$startDate = $record['subscribed'];
		$referred = $record['referred'];
		if(time()- $referred > 604800) continue;
		if(array_key_exists($aggregatorName,$aggregatorInfo)) {
			if(($subscribers > $aggregatorInfo[$aggregatorName]['subscribers'])&&($subscribers!==1)) {
				$totalSubscribers -= $aggregatorInfo[$aggregatorName]['subscribers'];
				$totalSubscribers += $subscribers;
				if(isset($latestUpdatedTime) && $latestUpdatedTime - $referred < 0) {
					$updatedSubscribers -=$aggregatorInfo[$aggregatorName]['subscribers'];
					$updatedSubscribers += $subscribers;
				}
				$aggregatorInfo[$aggregatorName]['subscribers'] = $subscribers;
			} else if($subscribers==1) {
				$aggregatorInfo[$aggregatorName]['subscribers'] += $subscribers;
				$totalSubscribers += $subscribers;
				if(isset($latestUpdatedTime) && $latestUpdatedTime - $referred < 0) {
					$updatedSubscribers += $subscribers;
				}
			}

			if($aggregatorInfo[$aggregatorName]['subscribed'] > $startDate)
				$aggregatorInfo[$aggregatorName]['subscribed'] = $startDate;
			if($aggregatorInfo[$aggregatorName]['referred'] < $referred)
				$aggregatorInfo[$aggregatorName]['referred'] = $referred;
		}
		else {
			$aggregatorInfo[$aggregatorName]['subscribers'] = $subscribers;
			$aggregatorInfo[$aggregatorName]['subscribed'] = $startDate;
			$aggregatorInfo[$aggregatorName]['referred'] = $referred;
			$totalSubscribers += $subscribers;
		}
		$aggregatorInfo[$aggregatorName]['isRobot'] = robotChecker($record['useragent']);
	}
	arsort($aggregatorInfo);
	return $aggregatorInfo;
}

function organizeRobotInfo($info)
{
}

function getSubscriptionStatistics($blogid) {
	global $database;
	$statistics = array();
	if ($result = Data_IAdapter::queryAll("SELECT ip, host, useragent, subscribed, referred FROM {$database['prefix']}SubscriptionStatistics WHERE blogid = $blogid ORDER BY referred DESC")) {
		return $result;
	}
	return $statistics;
}

function getSubscriptionLogsWithPage($page, $count) {  
	global $database;
	$blogid = getBlogId();
	requireComponent( "Textcube.Model.Paging");
	return Model_Paging::fetchWithPaging("SELECT ip, host, useragent, referred FROM {$database['prefix']}SubscriptionLogs WHERE blogid = $blogid ORDER BY referred DESC", $page, $count);  
}  

function getSubscriptionLogs() {
	global $database;
	$blogid = getBlogId();
	return Data_IAdapter::queryAll("SELECT ip, host, useragent, referred FROM {$database['prefix']}SubscriptionLogs WHERE blogid = $blogid ORDER BY referred DESC LIMIT 1000");
}

function updateSubscriptionStatistics($target, $mother) {
	global $database, $blogURL;
	$blogid = getBlogId();
	$period = Timestamp::getDate();
	requireComponent('Textcube.Data.Filter');
	if (Filter::isFiltered('ip', $_SERVER['REMOTE_ADDR']))
		return;
	$ip = Data_IAdapter::escapeString($_SERVER['REMOTE_ADDR']);
	$host = Data_IAdapter::escapeString(isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '');
	$useragent = Data_IAdapter::escapeString(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
	Data_IAdapter::query("INSERT INTO {$database['prefix']}SubscriptionLogs values($blogid, '$ip', '$host', '$useragent', UNIX_TIMESTAMP())");
	Data_IAdapter::query("DELETE FROM {$database['prefix']}SubscriptionLogs WHERE referred < UNIX_TIMESTAMP() - 604800");
	if (!Data_IAdapter::queryCount("UPDATE {$database['prefix']}SubscriptionStatistics SET referred = UNIX_TIMESTAMP() WHERE blogid = $blogid AND ip = '$ip' AND host = '$host' AND useragent = '$useragent'"))
		Data_IAdapter::query("INSERT INTO {$database['prefix']}SubscriptionStatistics VALUES ($blogid, '$ip', '$host', '$useragent', UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
	return $target;
}

function PN_Subscription_setTime($target) {
	requireComponent("Textcube.Function.Setting");
	Model_Setting::setBlogSetting('LatestRSSrefresh',time());
	return true;
}

function PN_Subscription_Sidebar($target) {
	requireComponent("Textcube.Function.Setting");
	$count = Model_Setting::getBlogSetting('SubscriberCount',null);
	$text = '<div class="SubscriptionPanel" style="text-align:center">';
	if($count===null) $text .= '구독 정보 갱신이 필요합니다';
	else $text .= $count.'명이 RSS를 구독하고 있습니다.';
	$text .= '</div>';
	return $text;
}
?>
