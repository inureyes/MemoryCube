<?php
/// Copyright (c) 2004-2009, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

// Dependency : Textcube.Model.Paging (getRefererLogsWithPage)

class Model_Statistics {
	function getStatistics($blogid) {
		global $database;
		$stats = array('total' => 0, 'today' => 0, 'yesterday' => 0);
		$result = Data_IAdapter::queryCell("SELECT visits FROM {$database['prefix']}BlogStatistics WHERE blogid = $blogid");
		if (!empty($result)) $stats['total'] = $result;
		
		$result = Data_IAdapter::queryAll("SELECT date, visits FROM {$database['prefix']}DailyStatistics WHERE blogid = $blogid AND `date` in ('" . Timestamp::getDate()."','".Timestamp::getDate(time()-86400)."')");
		$stat['today'] = $stat['yesterday'] = 0;
		foreach($result as $data) {
			if($data['date'] == Timestamp::getDate()) $stats['today'] = $data['visits'];
			if($data['date'] == Timestamp::getDate(time()-86400)) $stats['yesterday'] = $data['visits'];
		}
	
		return $stats;
	}

	function getDailyStatistics($period) {
		global $database, $blogid;
		return Data_IAdapter::queryAll("SELECT date, visits 
			FROM {$database['prefix']}DailyStatistics 
			WHERE blogid = $blogid 
				AND LEFT(date, 6) = $period 
			ORDER BY date DESC");
	}
	
	function getWeeklyStatistics() {
		global $database, $blogid;
		$now_day = date('Ymd', strtotime("now"));
		$old_day = date('Ymd', strtotime("-1 week"));
		return Data_IAdapter::queryAll("SELECT date, visits FROM {$database['prefix']}DailyStatistics WHERE blogid = $blogid AND  date BETWEEN $old_day AND $now_day ORDER BY date DESC");
	}

	function getMonthlyStatistics($blogid) {
		global $database;
		$statistics = array();
		if ($result = Data_IAdapter::queryAll("SELECT left(date, 6) date, sum(visits) visits 
			FROM {$database['prefix']}DailyStatistics 
			WHERE blogid = $blogid 
			GROUP BY left(date, 6) 
			ORDER BY date DESC")) {
			foreach($result as $record)
				array_push($statistics, $record);
		}
		return $statistics;
	}

	function getRefererStatistics($blogid) {
		global $database;
		$statistics = array();
		if ($result = Data_IAdapter::queryAll("SELECT host, count FROM {$database['prefix']}RefererStatistics WHERE blogid = $blogid ORDER BY COUNT DESC LIMIT 20")) {
			foreach($result as $record)
				array_push($statistics, $record);
		}
		return $statistics;
	}

	function getRefererLogsWithPage($page, $count) {  
		global $database, $blogid;
		return Model_Paging::fetchWithPaging("SELECT host, url, referred FROM {$database['prefix']}RefererLogs WHERE blogid = $blogid ORDER BY referred DESC", $page, $count);  
	}

	function getRefererLogs() {
		global $database;
		return Data_IAdapter::queryAll("SELECT host, url, referred FROM {$database['prefix']}RefererLogs WHERE blogid = ".getBlogId()." ORDER BY referred DESC LIMIT 1500");
	}

	function updateVisitorStatistics($blogid) {
		global $database, $blogURL;
		if (!fireEvent('UpdatingVisitorStatistics', true))
			return;
		if (doesHaveOwnership())
			return;
		$id = session_id();
		if(Data_IAdapter::queryCount("SELECT blogid FROM {$database['prefix']}SessionVisits WHERE id = '$id' AND address = '{$_SERVER['REMOTE_ADDR']}' AND blogid = $blogid") > 0)
			return;
		if (Data_IAdapter::queryCount("INSERT INTO {$database['prefix']}SessionVisits values('$id', '{$_SERVER['REMOTE_ADDR']}', $blogid)") > 0) {
			if(Data_IAdapter::queryCount("UPDATE {$database['prefix']}BlogStatistics SET visits = visits + 1 WHERE blogid = $blogid LIMIT 1") < 1) {
				Data_IAdapter::execute("INSERT into {$database['prefix']}BlogStatistics values($blogid, 1)");
			}
			
			$period = Timestamp::getDate();
			if(Data_IAdapter::queryCount("UPDATE {$database['prefix']}DailyStatistics SET visits = visits + 1 WHERE blogid = $blogid AND `date` = $period LIMIT 1") < 1) {
				Data_IAdapter::execute("INSERT into {$database['prefix']}DailyStatistics values($blogid, $period, 1)");
			}
			if (!empty($_SERVER['HTTP_REFERER'])) {
				$referer = parse_url($_SERVER['HTTP_REFERER']);
				if (!empty($referer['host']) && (($referer['host'] != $_SERVER['HTTP_HOST']) || (strncmp($referer['path'], $blogURL, strlen($blogURL)) != 0))) {
					if (Model_Filter::isFiltered('ip', $_SERVER['REMOTE_ADDR']) || Model_Filter::isFiltered('url', $_SERVER['HTTP_REFERER']))
						return;
					if (!fireEvent('AddingRefererLog', true, array('host' => $referer['host'], 'url' => $_SERVER['HTTP_REFERER'])))
						return;
					$host = Data_IAdapter::escapeString(UTF8::lessenAsEncoding($referer['host'], 64));
					$url = Data_IAdapter::escapeString(UTF8::lessenAsEncoding($_SERVER['HTTP_REFERER'], 255));
					Data_IAdapter::query("INSERT INTO {$database['prefix']}RefererLogs values($blogid, '$host', '$url', UNIX_TIMESTAMP())");
					Data_IAdapter::query("DELETE FROM {$database['prefix']}RefererLogs WHERE referred < UNIX_TIMESTAMP() - 604800");
					if (!Data_IAdapter::queryCount("UPDATE {$database['prefix']}RefererStatistics SET count = count + 1 WHERE blogid = $blogid AND host = '$host' LIMIT 1"))
						Data_IAdapter::execute("INSERT into {$database['prefix']}RefererStatistics values($blogid, '$host', 1)");
				}
			}
		}
	}

	function setTotalStatistics($blogid) {
		global $database;
		Data_IAdapter::execute("DELETE FROM {$database['prefix']}DailyStatistics WHERE blogid = $blogid");
		$prevCount = Data_IAdapter::queryCell("SELECT visits FROM {$database['prefix']}BlogStatistics WHERE blogid = $blogid");
		if ((!is_null($prevCount)) && ($prevCount == 0))
			return true;
		if(Data_IAdapter::execute("UPDATE {$database['prefix']}BlogStatistics SET visits = 0 WHERE blogid = $blogid")) {
			return true;
		} else {
			$result = Data_IAdapter::execute("INSERT INTO {$database['prefix']}BlogStatistics values($blogid, 0)");
			return $result;
		}
	}
}
?>