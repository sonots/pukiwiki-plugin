<?php 
if ($lastmodified) {
	$filetime = get_filetime($title);

	// refer lib/func.php#get_passage
	static $units = array('分'=>60, '時間'=>24, '日'=>1);
	$time = max(0, (UTIME - $filetime) / 60); // minutes
	foreach ($units as $unit=>$card) {
		if ($time < $card) break;
		$time /= $card;
	}
	$time = floor($time);
	$pg_passage = ($filetime != 0) ? $time . $unit . '前' : '';

	echo '<p id="last-modified">';
	if ($card == "60" && $time <= $fresh_time) {
		echo '今さっき更新 ';
	}elseif ($card == "1" && $time <= "2"){
		if ($time == "1"){
			echo '昨日更新';
		}elseif ($time == "2"){
			echo '一昨日更新';
		}
	}else { 
		if ($pg_passage){
			echo 'だいたい'.$pg_passage.'に更新';
		}else {
			echo '今さっき更新';
		}
	}
	echo '<span class="detail">最終更新日時: '. $lastmodified .'</span></p>';
}
?>