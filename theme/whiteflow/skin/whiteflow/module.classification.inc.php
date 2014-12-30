<?php
$accept = $_SERVER['HTTP_ACCEPT'];
$user_agent = getenv('HTTP_USER_AGENT');

if (eregi("application/xhtml\+xml",$accept)){
	$accept_xml = true;}

if (eregi("Win",$user_agent)){
	$platform == "Windows";
} elseif (eregi("Mac",$user_agent)){
	$platform == "Mac";
} elseif (eregi("Linux",$user_agent)){
	$platform == "Linux";
} else{
	$platform == "Unknown";
}

if (eregi("Opera", $user_agent)){
	$engine = "Opera";
} elseif(eregi("Gecko\/", $user_agent) && $accept_xml){
	$engine = "Gecko";
} elseif(eregi("MSIE", $user_agent) && !$accept_xml){
	if ($platform == "Mac"){
		$engine = "Mac IE";
	} else{
		if (eregi("MSIE 6", $user_agent)){
			$engine = "MSIE 6";
		} elseif (eregi("MSIE 7", $user_agent)){
			$engine = "MSIE 7";
		} else{
			$engine = "MSIE";
		}
	}
} elseif (ereg("(KHTML|Konqueror)", $user_agent) && !eregi("Win", $user_agent)){
	if (eregi("AppleWebkit", $user_agent)){
		$engine = "KHTML Safari";
	} else{
		$engine = "KHTML";
	}
} elseif (eregi("Another_HTML-lint", $user_agent)){
	$engine = "AHL";
} else {
	$engine = "Unknown";
}?>