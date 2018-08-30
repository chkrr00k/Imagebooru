<?php

	function substitute($string){
		$match = array(
			"\"" => "&quot;",
			"`" => "&#96;",
			"'" => "&#39;",
			"-" => "&#45;",
			"‘" => "&#8216;",
			"’" => "&#8217;",
			"“" => "&#8220;",
			"”" => "&#8221;",
			"´" => "&#180;"
		);
		$tmp = $string;
		foreach($match as $mat => $sub){
			$tmp = str_replace($mat, $sub, $tmp);
		}
		return $tmp;
	}
	function accetableSQL(&$input){
		$SQLi = array("--", "\"", "`", "'");
		$input = substitute($input);
		$input = mysql_real_escape_string($input);
		foreach($SQLi as $s){
			if(strstr($input, $s)){
				return false;
			}
		}
		return true;
	}
	
	function checkDb($user, $pass, $db){
		if(accetableSQL($user) && accetableSQL($pass)){
			$hashPass = md5($pass);
			$dbResult = mysql_query("SELECT * FROM `MODS` WHERE `USER` = \"{$user}\" AND `PASS` = \"{$hashPass}\"", $db);
			while($line = mysql_fetch_array($dbResult)){
				if(strcmp($user, $line["USER"]) == 0 && strcmp($hashPass, $line["PASS"]) == 0){
					return true;
				}
			}
		}
		return false;
	}
	
	//XXX database functions
	// root access database here
	function instance_database(){
		$host = "localhost";
		$user = "root";
		$password = "root";
		$database = "db";
		$db = mysql_connect($host, $user, $password);
		mysql_select_db($database, $db);
		return $db;
	}
	// this shouldn't be the root one'
	function get_database(){
		$host = "localhost";
		$user = "root";
		$password = "root";
		$database = "db";
		$db = mysql_connect($host, $user, $password);
		mysql_select_db($database, $db);
		return $db;
	}
	
	class Image {
		
		private $id;
		private $src;
		private $uploader;
		private $desc;
		private $tags;
		
		public function __construct($id, $src, $uploader, $desc){
			$this->id = $id;
			$this->src = $src;
			$this->uploader = $uploader;
			$this->desc = $desc;
			$this->tags = array();
		}
		
		public function setTags($tags){
			$this->tags = $tags;
		}
		
		public function getTags(){
			return $this->tags;
		}
		public function getId(){
			return $this->id;
		}
		public function getSrc(){
			return $this->src;
		}
		public function getUploader(){
			return $this->uploader;
		}
		public function getDesc(){
			return $this->desc; 
		}
	}
	
	function loadAdminPage($db_root){
		$dbResult = mysql_query("SELECT * FROM `IMAGE`", $db_root);
		$images = array();
		while($line = mysql_fetch_array($dbResult, $type)){
			$images[] = new Image($line['ID'], $line['SRC'], $line['UPLOADER'], $line['DESC']);
		}
		mysql_free_result($dbResult);
		foreach($images as $im){
			$tags = array();
			$dbResult = mysql_query("SELECT * FROM `REL` WHERE `REL`.`ID`={$im->getId()}", $db_root);
			while($tag = mysql_fetch_array($dbResult)){
				$tags[] = $tag["NAME"];
			}
			mysql_free_result($dbResult);
			$im->setTags($tags);
		}
		$html = "<table>";
		foreach($images as $image){
			$html .= printImage($image);
		}
		return $html . "</table>";
	}
	
	//consider fusing this one and the one above
	function loadAdminPageSingle($db_root, $numId){
		$dbResult = mysql_query("SELECT * FROM `IMAGE` WHERE `ID` = {$numId}", $db_root);
		$images = array();
		while($line = mysql_fetch_array($dbResult, $type)){
			$images[] = new Image($line['ID'], $line['SRC'], $line['UPLOADER'], $line['DESC']);
		}
		mysql_free_result($dbResult);
		foreach($images as $im){
			$tags = array();
			$dbResult = mysql_query("SELECT * FROM `REL` WHERE `REL`.`ID`={$im->getId()}", $db_root);
			while($tag = mysql_fetch_array($dbResult)){
				$tags[] = $tag["NAME"];
			}
			mysql_free_result($dbResult);
			$im->setTags($tags);
		}
		$html = "<table>";
		foreach($images as $image){
			$html .= printImage($image);
		}
		return $html . "</table>";
	}
	
	function printImage($image){
		$html = "<tr><td>"
			. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
			. $image->getId() 
			. "<input type=\"submit\" value=\"Delete\">"
			. "<input type=\"hidden\" name=\"delet\" value=\"{$image->getId()}\">"
			. "</form>" . "</td><td>"
			. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
			. "<textarea name=\"desc\">{$image->getDesc()}</textarea>"
			. "<br><input type=\"submit\" value=\"Update\">"
			. "<input type=\"hidden\" name=\"imgId\" value=\"{$image->getId()}\">"
			. "</form>"
			. "</td><td>"
			. $image->getSrc() . "</td><td>"
			. $image->getUploader() . "</td><td>"
			. "<a href=\"images/{$image->getSrc()}\">image</a></td><td>"
			. "<div class=\"dropMenu\">Show tags<div class=\"menuCont\">"
			. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">";
		foreach($image->getTags() as $tag){
			$html .= "<div class=\"tag\"><input type=\"checkbox\" name=\"tags[]\" value=\"{$tag}\">{$tag}</div>";
		}
		$html .= "<input type=\"submit\" value=\"Delete\">"
			. "<input type=\"hidden\" name=\"imgId\" value=\"{$image->getId()}\">"
			. "</form></div></div></td></tr>";
		return $html;
	}
	
	function deleteTags($db_root, $tags, $imgId){
		if(!accetableSQL($imgId)){
			return;
		}
		foreach($tags as $tag){
			if(accetableSQL($tag)){
				mysql_query("DELETE FROM `REL` WHERE `REL`.`ID`=\"{$imgId}\" AND `REL`.`NAME`=\"{$tag}\"", $db_root);
			}
		}
	}
	
	function updateDesc($db_root, $desc, $imgId){
		if(!accetableSQL($desc) || !accetableSQL($imgId)){
			return;
		}
		mysql_query("UPDATE `IMAGE` SET `DESC` = \"{$desc}\" WHERE `ID` = {$imgId};", $db_root);
	}

	function deleteImg($db_root, $imgId){
		if(!accetableSQL($imgId)){
			return;
		}
		mysql_query("DELETE FROM `IMAGE` WHERE `ID` = {$imgId};", $db_root);
	}
	
	function checkBL($ip, $db){
		if(!accetableSQL($ip)){
			return false;
		}
		$dbResult = mysql_query("SELECT * FROM `BANNED` WHERE `IP`={$ip}", $db);
		$i = 0;
		while($line = mysql_fetch_array($dbResult)){
			$i++;
		}
		return $i == 0;
	}
	
	function blacklist($ip, $db){
		mysql_query("INSERT INTO `BANNED` VALUE(\"{$ip}\")", $db);
	}
	
	function tagPage($db_root, $tag){
		if(!accetableSQL($tag)){
			return "Invalid Tag";
		}
		$html = "<table>";
		$dbResult = mysql_query("SELECT * FROM `TAG` WHERE `NAME` = \"{$tag}\"", $db_root);
		while($line = mysql_fetch_array($dbResult)){
			$html .= "<tr><td><form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
				. "{$line['NAME']}<br><input type=\"submit\" value=\"Delete\">"
				. "<input type=\"hidden\" name=\"deletTag\" value=\"{$line['NAME']}\">"
				. "</form></td></td>"
				. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
				. "<td><textarea name=\"tagDesc\">{$line['DESC']}</textarea>"
				. "<br><input type=\"submit\" value=\"Update\">"
				. "<input type=\"hidden\" name=\"tagName\" value=\"{$line['NAME']}\">"
				. "</form></td>";
		}
		mysql_free_result($dbResult);
		return $html . "</table>";
	}
	
	function updateTagDesc($db_root, $desc, $tag){
		if(!accetableSQL($desc) || !accetableSQL($tag)){
			return;
		}
		mysql_query("UPDATE `TAG` SET `DESC` = \"{$desc}\" WHERE `NAME` = \"{$tag}\"", $db_root);
	}

	function deleteTag($db_root, $tag){
		if(!accetableSQL($tag)){
			return;
		}
		mysql_query("DELETE FROM `TAG` WHERE `NAME` = \"{$tag}\"", $db_root);
	}

	session_start();
	
	if(isset($_REQUEST['logout'])){
		$_SESSION['user'] = NULL;
		$_SESSION['password'] = NULL;
	}
	
	if(isset($_POST['user']) && isset($_POST['password'])){
		$db = get_database();
		$black = checkBL($_SERVER['HTTP_HOST'], $db);
		if($_SESSION['tried'] < 5 && $black){
			$user = $_POST['user'];
			$pass = $_POST['password'];
			if(checkDb($user, $pass, $db)){
				$_SESSION['user'] = $user;
				$_SESSION['password'] = $pass;
				$_SESSION['tried'] = 0;
			}else{
				$html = "Invalid password or username";
				if(isset($_SESSION['tried'])){
					$_SESSION['tried']++;
				}else{
					$_SESSION['tried'] = 1;
				}
			}
		}else{
			print("Your IP has been blacklisted");
			if($black){
				blacklist($_SERVER['HTTP_HOST'], $db);
			}
		}
	}else{
		$html = "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
			. "User: <input type=\"text\" name=\"user\">"
			. "Password: <input type=\"password\" name=\"password\">"
			. "<input type=\"submit\" value=\"LogIn\">"
			. "</form>";
	}
	
	if(isset($_SESSION['user']) && isset($_SESSION['password'])){
		$db = get_database();
		if(checkDb($_SESSION['user'], $_SESSION['password'], $db)){
			$db_root = instance_database();
			$html = "<div class=\"top\"><div class=\"topBar\"><form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}?logout\">"
				. "<input type=\"submit\" value=\"LogOut\">"
				. "</form></div><div class=\"topBar\">"
				. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}?home\">"
				. "<input type=\"submit\" value=\"Home\">"
				. "</form></div></div>";
		
			if(isset($_REQUEST['tags']) && isset($_REQUEST['imgId'])){
				$imgId = $_REQUEST['imgId'];
				$tags = array();
				foreach($_REQUEST['tags'] as $tag){
					$tags[] = $tag;
				}
				deleteTags($db_root, $tags, $imgId);
				$html .= "<div class=\"result\">DELETED TAGS</div>";
			}elseif(isset($_REQUEST['desc']) && isset($_REQUEST['imgId'])){
				$imgId = $_REQUEST['imgId'];
				$desc = $_REQUEST['desc'];
				updateDesc($db_root, $desc, $imgId);
				$html .= "<div class=\"result\">UPDATED DESCRIPTIONS</div>";
			}elseif(isset($_REQUEST['delet'])){
				$imgId = $_REQUEST['delet'];
				deleteImg($db_root, $imgId);
				$html .= "<div class=\"result\">DELETED PICTURE</div>";
			}elseif(isset($_REQUEST['number'])){
				$num = $_REQUEST['number'];
				if($num == "all"){
					$html .= loadAdminPage($db_root);
				}else{
					$html .= loadAdminPageSingle($db_root, $num);
				}
			}elseif(isset($_REQUEST['tag'])){
				$html .= tagPage($db_root, $_REQUEST['tag']);
			}elseif(isset($_REQUEST['tagDesc']) && isset($_REQUEST['tagName'])){
				updateTagDesc($db_root, $_REQUEST['tagDesc'], $_REQUEST['tagName']);
				$html .= "<div class=\"result\">UPDATED DESCRIPTION</div>";
			}elseif(isset($_REQUEST['deletTag'])){
				deleteTag($db_root, $_REQUEST['deletTag']);
				$html .= "<div class=\"result\">DELETED TAG</div>";
			}else{
				$html .= "<div class=\"search\">Search form: input the number of the picture you want to see or 'all' if"
				. " you want to see all of them (this could be slow)" 
				. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
				. "<input name=\"number\" type=\"text\"></input>"
				. "<br><input type=\"submit\" value=\"Search\">"
				. "</form></div>"
				. "<div class=\"search\">Search form: input the tag you want to examine" 
				. "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}\">"
				. "<input name=\"tag\" type=\"text\"></input>"
				. "<br><input type=\"submit\" value=\"Search\">"
				. "</form></div>";
			}
		}else{
			die("Invalid credential");
		}
		
		
		mysql_close($db_root);
	}
	
	print("<html><head>"
		. "<link rel=\"stylesheet\" type=\"text/css\" href=\"searchMod.css\"></link>"
		. "</head><body>"
		. $html
		. "</body></html>");
?>
