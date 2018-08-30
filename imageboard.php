<?php
	//session_start();
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
	
	$host = "localhost";
	$user = "root";
	$password = "root";
	$database = "db";

	class Database {
		private $db;
		private $connected;
		
		public function __construct($host, $user, $password, $database){
			if(!($this->db = mysql_connect($host, $user, $password))){
				throw new Exception("Unable to connect to the database");
			}
			if(!mysql_select_db($database, $this->db)){
				throw new Exception("Unable to select the database");
			}
			$this->connected = true;
		}
		public function __destruct(){
			if($this->connected){
				mysql_close($this->db);
			}
		}
		
		public function disconnect(){
			if($this->connected){
				mysql_close($this->db);
				$this->connected = false;
			}
		}
		
		public function query($query, $type=MYSQL_ASSOC){
			if($this->invalid($query)){
				throw new Exception("Bad formatted SQLi");
			}
			if($this->connected){
				$dbResult = mysql_query($query, $this->db);
				if(!$dbResult){
					return ["success" => false,
						"set" => array()];
				}
				$result = array();
				while($line = mysql_fetch_array($dbResult, $type)){
					$result[] = $line;
				}
				mysql_free_result($dbResult);
				return ["set" => $result,
					"success" => true];
			}
		}
		
		public function queryWithInsertId($query){
			$re = $this->query($query);
			return mysql_insert_id($this->db);
		}
		
		private function invalid($query){
			return strstr($query, '--');
		}
	}
	
	function formatTags($tags){
		foreach($tags as $tag){
			$result .= "<div class=\"tag\">"
				. "<a href=\"{$_SERVER['PHP_SELF']}?query={$tag}\">" 
				. $tag
				. "</a>"
				. "</div>";
		}
		$result .= "<div class=\"tag\" id=\"newTag\">"
				. "<div id=\"tagHide\">+</div>"
				. "<input type=\"text\" id=\"tagInput\">"
				. "</div>";
		return $result;
	}

	function formatPage($id, $desc, $tags){
		$imageFolder = "images/";
		return "<div class=\"imageContainer\">"
			. "<img id=\"image\" src=\"" . $imageFolder . $id . "\" >"
			. "</div>"
			. "<div class=\"description\">"
			. $desc
			. "</div>"
			. "<div class=\"tagContainer\" id=\"tagContainer\">"
			. $tags
			. "</div>";
	}
	
	function splitTag($tags){
		return split(",", $tags);
	}
	
	function checkTags($tags){
		foreach($tags as $tag){
			if(!accetableSQL($tag)){
				return false;
			}
		}
		return true;
	}
	
	function generateQuery($tags){
		$cur = 0;
		$from = "";
		$where = "WHERE ";
		$n = count($tags);
		foreach($tags as $tag){
			$from .= "REL AS R" . ++$cur;
			$where .= "R" . $cur . ".NAME = \"" . trim($tag) . "\" ";
			if($cur > 1){
				$where .= " AND R" . $cur . ".ID = R" . ($cur - 1) . ".ID ";
			}
			if($cur != $n){
				$from .= ", ";
				$where .= "AND ";
			}
			
		}
		return "SELECT IM.SRC, IM.ID" 
			. " FROM IMAGE AS IM,"
			. " (SELECT R1.ID FROM " . $from . " " . $where
			. ") AS ID WHERE IM.ID = ID.ID";
	}
	
	function generateTopBar(){
		return "<div class=\"topBar\"><span><input type=\"text\" name=\"query\" id=\"query\">"
			. "<input class=\"topBatButton\" id=\"sendButton\" type=\"button\" value=\"Search\"></span>"
			. "<span><input class=\"topBatButton\" id=\"fileUplBut\" type=\"button\" value=\"Upload a file\"></span>"
			. "<span><input class=\"topBatButton\" id=\"homeBut\" type=\"button\" value=\"Home\"></span>"
			. "<div id=\"error\">"
			. "</div></div>";
	}
	
	//TODO generate a good homepage
	function generateHomePage(){
		global $db;
		$dbResult = $db->query("SELECT `TAG`.`NAME`, `TAG`.`DESC`, COUNT(*) AS `NUM`"
						. "FROM `TAG`, `REL`"
						. "WHERE `TAG`.`NAME` = `REL`.`NAME`"
						. "GROUP BY `TAG`.`NAME`"
						. "ORDER BY `NUM` DESC, `NAME` ASC")["set"];
		$html = "<table><tr class=\"first\"><td>Name</td><td>Description</td><td>#</td></tr>";
		foreach($dbResult as $row){
			$html .= "<tr>"
				. "<td class=\"tagName\"><a class=\"tagTab\" href=\"{$_SERVER['PHP_SELF']}?query={$row["NAME"]}\">{$row["NAME"]}</td>"
				. "<td>{$row["DESC"]}</td>"
				. "<td>{$row["NUM"]}</td>"
				. "</tr>";
		}
		$html .= "</table>";
		return "<div class=\"banner\">Welcome! This is a list of all aviable tags sort by popularity:</div>"
			. $html;
	}
	
	//XXX here
	$maxN = 8;
	
	function bottomSearch($nu){
		global $maxN;
		$n = $nu / $maxN;
		for($i = 0; $i < $n; $i++){
			$result .= "<input type=\"button\" class=\"number\" value=\"{$i}\">"
				. "</input>";
		}
		return "<div class=\"bNContainer\">"
			. $result
			. "</div>";
	}
	
	function formatSearchPage($dbResult, $st = 0){
		if($dbResult){
			$imageFolder = "images/";
			$html = "";
			$n = count($dbResult);
			global $maxN;
			$st *= $maxN;
			if($st > $n){
				$st = $n - $maxN;
			}elseif($st < 0){
				$st = 0;
			}
			for($i = $st; $i < $n && $i < $st + $maxN; $i ++){
				$tuple = $dbResult[$i];
				$html .= "<div class=\"container\">"
					. "<div class=\"imageMiniature\">"
					. "<img class=\"imageM\" src=\"" . $imageFolder . $tuple['SRC'] . "\">"
					. "</div>"
					. "<div class=\"info\">"
					. "<a href=\"{$_SERVER['PHP_SELF']}?numId={$tuple['ID']}\">"
					. ">>{$tuple['ID']}"
					. "</a>"
					. "</div>"
					. "</div>";
			}
			$html .= bottomSearch(count($dbResult));
		}else{
			$html = generateNoImages();
		}
		return $html;
	}
	
	function generateNoImages(){
		return "<div class=\"noTag\">"
			. "No images found for your tag.<br>(Why don't you upload some?)"
			. "</div>";
	}
	
	function jsonImage($tuple){
		return "{\"src\":\"images/{$tuple['SRC']}\","
			. "\"url\":\"{$_SERVER['PHP_SELF']}?numId={$tuple['ID']}\","
			. "\"num\":{$tuple['ID']}}";
	}
	
	function jsonPage($images){
		$result = "[";
		for($i = 0; $i < count($images); $i++){
			$result .= jsonImage($images[$i]);
			if($i != count($images) - 1){
				$result .= ",";
			}
		}
		$result .= "]";
		return $result;
	}
	
	function slicePage($dbResult, $maxN, $page){
		$result = array();
		$index = $page * $maxN;
		for($i = $index; $i < $index + $maxN && $i < count($dbResult); $i++){
			$result[] = $dbResult[$i];
		}
		return $result;
	}
	
	function searchToJSON($dbResult){
		global $maxN;
		$pages = ceil(count($dbResult)/$maxN);
		$result = "[";
		for($i = 0; $i < $pages; $i++){ 
			$result .= jsonPage(slicePage($dbResult, $maxN, $i));
			if($i < $pages - 1){
				$result .= ",";
			}
		}
		$result .= "]";
		return $result;
	}

	function addTag($tagName, $tagDesc){
		global $db;
		$result = accetableSQL($tagName)
			&& accetableSQL($tagDesc)
			&& !($tagPresent = tagPresence($tagName));
		if($result){
			$result = $db->query("INSERT INTO TAG(`NAME`, `DESC`) VALUE(\"{$tagName}\", \"{$tagDesc}\")")["success"];
		}
		if($result){
			$json = makeJSON(tagJSON($tagName, $tagDesc));
		}else{
			if($tagPresent){
				$msg = "The tag you are trying to define exists already";
				$code = 1;
			}else{
				$msg = "Error in defining tag";
				$code = 0;
			}
			$json = makeErrorJSON($code, $msg);
		}
		return $json;
	}
	
	function tagPresence($tagName){
		global $db;
		return count($db->query("SELECT * FROM `TAG` WHERE NAME = \"{$tagName}\"")["set"]);
	}

	function linkTagToImg($tagName, $imgID){
		global $db;
		$result = is_numeric($imgID) 
			&& accetableSQL($tagName) 
			&& accetableSQL($imgID)
			&& ($tagPresent = tagPresence($tagName));
		if($result){
			$result = $db->query("INSERT INTO REL(`NAME`, `ID`) VALUE(\"{$tagName}\", {$imgID})")["success"];
		}
		$infos = assJSON($tagName, $imgID);
		if($result){
			$json = makeJSON($infos);
		}else{
			if($tagPresent){
				$msg = "Error in adding tag";
				$code = 0;
			}else{
				$msg = "You need to define the tag first";
				$code = 1;
			}
			$json = makeErrorJSON($code, $msg, $infos);
		}
		return $json;
	}
	
	function generateNotFound(){
		return "<div class=\"invalid\">"
			. "The resource you are looking for is missing somehow..."
			. "</div>";
	}
	
	function generateSQLi(){
		return "<div class=\"invalid\">"
			. "Your last request had something wrong. Remember you can't "
			. "use any quote symbol in any form due to extreme paranoid sysadmin"
			. "</div>";
	}
	
	function checkHash($hash){
		global $db;
		return count($db->query("SELECT * FROM `IMAGE` WHERE HASH = \"{$hash}\"")["set"]) > 0;
	}
	
	function uploadFile($file, $desc = ""){
		if($file['error'] != UPLOAD_ERR_OK){
			$json = makeErrorJSON(1, "Server internal error");
		}else{
			global $db;
			$hash = hash_file("md5", $file['tmp_name']);
			$result = ($isFile = strstr($file['type'], "image"))
				&& accetableSQL($file['name'])
				&& accetableSQL($desc)
				&& ($original = !checkHash($hash))
				&& copy($file['tmp_name'], "./images/" . $fileName = microtime(true))
				&& unlink($file['tmp_name'])
				&& $id = $db->queryWithInsertId("INSERT INTO IMAGE(`ID`,`SRC`, `DESC`, `UPLOADER`, `HASH`) "
					. "VALUE(NULL, \"{$fileName}\", \"{$desc}\", \"{$_SERVER['REMOTE_ADDR']}\", \"{$hash}\")");
			$infos = imgJSON($id, $fileName, $desc);
			if($result){
				$json = makeJSON($infos);
			}else{
				if(!$isFile){
					$json = makeErrorJSON(2, "You need to uploading an image file", $infos);
				}elseif(!$original){
					$json = makeErrorJSON(3, "Your file is already in the database", $infos);
				}else{
					$json = makeErrorJSON(0, "Unable to loading your file", $infos);
				}
			}
		}
		return $json;
	}
	
	//JSON creating functions;
	function tagJSON($tagName, $tagDesc){
		return "{\"tag\":\"{$tagName}\",\"desc\":\"{$tagDesc}\"}";
	}
	function assJSON($tagName, $imgID){
		return "{\"tag\":\"{$tagName}\",\"img\":\"{$imgID}\"}";
	}
	function imgJSON($imgID, $src, $desc){
		return "{\"img\":\"{$imgID}\",\"src\":\"{$src}\",\"desc\":\"{$desc}\"}";
	}
	function makeJSON($param){
		return "{\"infos\":{$param}}";
	}
	function makeErrorJSON($code, $param, $infos){
		return "{\"error\":{\"code\":{$code},\"msg\":\"{$param}\"},\"infos\":{$infos}}";
	}
	
	function generateFileLoader(){
		$html = "<div id=\"uploader\">"
			. "<div class=\"fileText\">"
			. "Use this form to upload new images. Remember to add a valid description!"
			. "</div>"
			. "<div class=\"fileLoader\">"
			. "<div class=\"file\"><input class=\"inputBox\" name=\"file_name\" type=\"file\" id=\"file_name\"></input></div>"
			. "<div class=\"desc\"><textarea class=\"inputBox\" name=\"desc\" id=\"desc\"></textarea></div>"
			. "</div>"
			. "<div class=\"tagAdd\">"
			. "<div class=\"fileText\">And don't forget to add one tag too!</div>"
			. "<input id=\"tagName\"class=\"inputBox\" type=\"text\"></input>"
			. "<div class=\"tagError\" id=\"tagError\"></div>"
			. "</div>"
			. "<input class=\"submitButton\" type=\"button\" value=\"Submit\" id=\"fileSubmit\">"
			. "<div id=\"resp\">"
			. "</div>"
			. "</div>";
		return $html;
	}
	
	$db = new Database($host, $user, $password, $database);
	if(isset($_REQUEST['numId']) && !isset($_REQUEST['query'])){
		$numId = $_REQUEST['numId'];
		if(accetableSQL($numId)){
			$dbResult = $db->query("SELECT * FROM IMAGE WHERE ID = " . $numId)["set"][0];
			$id = $dbResult['SRC'];
			$desc = $dbResult['DESC'];
			if($id){
				$dbResult = $db->query("SELECT TAG.NAME"
							. " FROM TAG, REL"
							. " WHERE TAG.NAME = REL.NAME"
							. " AND REL.ID = "
							. $numId)["set"];
				foreach($dbResult as $tuple){
					$tag[] = $tuple['NAME'];
				}
				$html = formatPage($id, $desc, formatTags($tag)) . "<script>const numId = \"{$numId}\"</script>";
			}else{
				$html = generateNotFound();
			}
		}else{
			$html = generateSQLi();
		}
		$json = false;
	}elseif(isset($_GET['query'])){
		$tags = splitTag($_GET['query']);
		if(checkTags($tags)){
			$dbResult = $db->query(generateQuery($tags))["set"];
			if(isset($_GET['n'])){
				$html = formatSearchPage($dbResult, $_GET['n']);
			}else{
				$html = formatSearchPage($dbResult);
			}
			$html .= "<script>const query = \"{$_GET['query']}\"</script>";
		}else{
			$html = generateSQLi();
		}
		$json = false;
	}elseif(isset($_POST['query'])){
		$tags = splitTag($_POST['query']);
		if(checkTags($tags)){
			$dbResult = $db->query(generateQuery($tags))["set"];
			$html = searchToJSON($dbResult);
			}
		$json = true;
	}elseif(isset($_GET['file'])){
		$html = generateFileLoader();
		$json = false;
	}/*elseif(isset($_GET['tag'])){
		$html = "<div class=\"tagPost\">"
			. "<div class=\"tagName\"><input class=\"inputBox\" name=\"tagName\" id=\"tagName\"></div>"
			. "<div class=\"tagDesc\"><textarea class=\"inputBox\" name=\"tagDesc\" id=\"tagDesc\"></textarea></div>"
			. "<input class=\"submitButton\" type=\"button\" value=\"Send\" id=\"tagDSubmit\">"
			. "</div>";
		$json = false;
	}*/elseif(isset($_POST['tagName']) && isset($_POST['tagDesc'])){
		$html = addTag($_POST['tagName'], $_POST['tagDesc']);
		$json = true;
	}/*elseif(isset($_GET['tagAsso'])){
		$html = "<div class=\"tagAss\">"
			. "<div class=\"tagANam\"><input class=\"inputBox\" name=\"tagANam\"id=\"tagANam\"></div>"
			. "<div class=\"imgAsso\"><input class=\"inputBox\" name=\"imgAsso\" id=\"imgAsso\"></div>"
			. "<input class=\"submitButton\" type=\"button\" value=\"Send\" id=\"tagASubmit\">"
			. "</div>";
		$json = false;
	}*/elseif(isset($_POST['tagANam']) && isset($_POST['imgAsso'])){
		$html = linkTagToImg($_POST['tagANam'], $_POST['imgAsso']);
		$json = true;
	}elseif(isset($_FILES['file_name']) && isset($_POST['desc'])){
		$html = uploadFile($_FILES['file_name'], $_POST['desc']);
		$json = true;
	}else{
		$html = generateHomePage();
		$json = false;
	}
	if($json){
		print($html);
	}else{
		print("<html><head>"
			. "<link rel=\"stylesheet\" type=\"text/css\" href=\"search.css\"></link>"
			. "<title>T image collector</title>"
			. "<script src=\"source2.js\"></script>"
			. "</head>"
			. "<body onload=\"javascript:init();\">"
			. generateTopBar()
			. "<div class=\"page\" id=\"page\">"
			. $html
			. "</div>"
			. "</body></html>");
	}// onchange=\"javascript:init();\"
?>

