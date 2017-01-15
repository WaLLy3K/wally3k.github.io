<?php
// Pi-hole Block Page: Show "Website Blocked" on blacklisted domains
// by WaLLy3K 06SEP16 for Pi-hole
$phbpVersion = "2.1.11";

// Retrieve local custom configuration
$phbpConfig = (is_file("/var/phbp.ini") ? "TRUE" : "FALSE");

// Strip HTTP/HTTPS/WWW and final / for URL matching
$strip = "/(https?:\/\/)|(www\.)|(\/$)/i";

if($phbpConfig == "TRUE") {
  $usrIni = parse_ini_file("/var/phbp.ini", true);
  
  // Retrieve custom flagTypes from custom config
  $ftGeneric_cust     = preg_replace("$strip", "", $usrIni["blocklists"]["suspicious"]);
  $ftAdvertising_cust = preg_replace("$strip", "", $usrIni["blocklists"]["advertising"]);
  $ftTracking_cust    = preg_replace("$strip", "", $usrIni["blocklists"]["tracking"]);
  $ftMalicious_cust   = preg_replace("$strip", "", $usrIni["blocklists"]["malicious"]);
}

// Default Config Options
$iniUrl             = (empty($usrIni["classFile"])        ? "https://raw.githubusercontent.com/WaLLy3K/wally3k.github.io/master/classification.ini" : $usrIni["classFile"]);
$iniUpdateTime      = (empty($usrIni["classUpdateTime"])  ? "172800" : $usrIni["classUpdateTime"]); // Default: 48 hours
$landPage           = (empty($usrIni["landpage"])         ? "FALSE" : $usrIni["landpage"]);
$adminEmail         = (empty($usrIni["adminEmail"])       ? "FALSE" : $usrIni["adminEmail"]);
$selfDomain         = (empty($usrIni["selfDomain"])       ? "FALSE" : $usrIni["selfDomain"]);
$customCss          = (empty($usrIni["customCss"])        ? "https://wally3k.github.io/style/pihole.css" : $usrIni["customCss"]);
$customIcon         = (empty($usrIni["customIcon"])       ? "/admin/img/favicon.png" : $usrIni["customIcon"]);
$customLogo         = (empty($usrIni["customLogo"])       ? "https://wally3k.github.io/style/phv.svg" : $usrIni["customLogo"]);
$blockImage         = (empty($usrIni["blockImage"])       ? "https://wally3k.github.io/style/blocked.svg" : $usrIni["blockImage"]);
$blankGif           = (empty($usrIni["blankGif"])         ? "TRUE" : "FALSE"); // Unset Default: Enabled
$blankGif           = ($blankGif == "FALSE" && in_array($usrIni["blankGif"]) ? "TRUE" : "FALSE"); // Default: Enabled
$allowWhitelisting  = (empty($usrIni["allowWhitelisting"])? "TRUE" : "FALSE"); // Unset Default: Enabled
$allowWhitelisting  = ($allowWhitelisting == "FALSE" && in_array($usrIni["allowWhitelisting"], array('false','FALSE','no','NO','0'), true) ? "FALSE" : "TRUE"); // Default: Enabled
$ignoreUpdate       = (empty($usrIni["ignoreUpdate"])? "FALSE" : "TRUE"); // Unset Default: Disabled
$ignoreUpdate       = ($ignoreUpdate == "TRUE" && in_array($usrIni["ignoreUpdate"], array('true','TRUE','yes','YES','1'), true) ? "TRUE" : "FALSE"); // Default: Disabled
$exeTime            = (empty($usrIni["exeTime"])? "FALSE" : "TRUE"); // Unset Default: Disabled
$exeTime            = ($exeTime == "TRUE" && in_array($usrIni["exeTime"], array('true','TRUE','yes','YES','1'), true) ? "TRUE" : "FALSE"); // Default: Disabled
$usrIni = NULL; // Unset

// Locally cache external definitions file
$iniFile = basename("$iniUrl");

function cache_ini($url) {
  global $iniUpdateTime, $iniFile;
  if (time() - filemtime("$iniFile") < $iniUpdateTime) return; // Recently updated, skip check
  
  $hostUrl = parse_url($url);
  $hostHeader = @get_headers($url, 1);
  $httpStatus = substr($hostHeader[0], 9, 3);
  $hostETag = (isset($hostHeader["ETag"]) ? $hostHeader["ETag"] : "FALSE");
  $hostLastmod = (isset($hostHeader["Last-Modified"]) ? strtotime($hostHeader["Last-Modified"]) : "FALSE");
  $hostHeader = NULL;
  $hostName = $hostUrl["scheme"]."://".$hostUrl["host"];
  
  if (empty($httpStatus))
    die("Unable to retrieve '$iniFile'. The server '$hostName' was not found");
  if (isset($httpStatus) && !in_array($httpStatus, array("200","301","302")))
    die("Unable to retrieve '$iniFile'. The server '$hostName' returned the error code: $httpStatus");
  if ($hostETag == "FALSE" && $hostLastmod == "FALSE")
    die("Unable to store '$iniFile'. The server '$hostName' does not provide adequate headers for version control");
  
  // Hash ETag or Last-Modified with $url
  $hostVersion = ($hostETag !== "FALSE" ? hash('crc32', '$hostETag.$url') : hash('crc32', '$hostLastmod.$url'));
  if (is_file("$iniFile")) {
    $cliVersion = substr(fgets(fopen($iniFile, 'r')), 2, -1);
    if (empty($cliVersion)) die("Unable to read from '$iniFile'");
    if ($cliVersion == $hostVersion) touch($iniFile); // Recently checked, skip future updates
  }
  
  $hostFile = file("$url");
  array_unshift($hostFile, "; $hostVersion\n"); // Place $hostVersion at top of config for version control
  file_put_contents("$iniFile", $hostFile);
}

cache_ini($iniUrl);
$ini = parse_ini_file("$iniFile", true);

$latestVersion = $ini["blocklist"]["version"];
$ftGeneric = preg_replace("$strip", "", $ini["blocklist"]["generic"]);
$ftAdvertising = preg_replace("$strip", "", $ini["blocklist"]["advertising"]);
$ftTracking = preg_replace("$strip", "", $ini["blocklist"]["tracking"]);
$ftMalicious = preg_replace("$strip", "", $ini["blocklist"]["malicious"]);
$ini = NULL; // Unset

if ($phbpConfig == "TRUE") {
  // Merge custom flagTypes with default flagTypes
  if (!empty($ftGeneric_cust))      $ftGeneric = array_merge($ftGeneric, $ftGeneric_cust);
  if (!empty($ftAdvertising_cust))  $ftAdvertising = array_merge($ftAdvertising, $ftAdvertising_cust);
  if (!empty($ftTracking_cust))     $ftTracking = array_merge($ftTracking, $ftTracking_cust);
  if (!empty($ftMalicious_cust))    $ftMalicious = array_merge($ftMalicious, $ftMalicious_cust);
  $ftGeneric_cust = NULL; $ftAdvertising_cust = NULL; $ftTracking_cust = NULL; $ftMalicious_cust = NULL;
}

// Sanitise URL input
$serverName = filter_var($_SERVER['SERVER_NAME'], FILTER_SANITIZE_SPECIAL_CHARS);

// Email address config option
if ($adminEmail !== "FALSE") {
  $noticeStr = "<a href='mailto:$adminEmail?subject=Site Blocked: $serverName'>ask to have it whitelisted</a>";
}else{
  $noticeStr = "ask the owner of the Pi-hole in your network to have it whitelisted";
}

// Define which URI extensions get rendered as "Website Blocked"
// Index files should always be rendered as "Website Blocked" anyway
$webRender = array("asp", "htm", "html", "php", "rss", "xml");

// Retrieve serverName URI extension (EG: jpg, exe, php)
$uriExt = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);

// Handle type of block page
if ($serverName == "pi.hole") {
  header("Location: admin");
}elseif ($landPage !== "FALSE" && $serverName == $_SERVER['SERVER_ADDR'] || $landPage !== "FALSE" && $serverName == $selfDomain) {
  // When browsing to RPi, redirect to custom landing page
  include $landPage;
  exit();
}elseif (in_array($uriExt, $webRender) || isset($_GET['debug'])) {
  // Valid URL extension to render as "Website Blocked"
}elseif (substr_count($_SERVER['REQUEST_URI'], "?") && isset($_SERVER['HTTP_REFERER']) && $blankGif == "TRUE") {
  // Serve a 1x1 blank gif to POTENTIAL iframe with query string
  die("<img src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'>");
}elseif (!empty($uriExt) || substr_count($_SERVER['REQUEST_URI'], "?")) {
  // Invalid URL extension or non-iframed query string
  die('<head><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/></head><img src="'.$blockImage.'"/>');
}

// Some error handling
if (empty(glob("/etc/pihole/*domains"))) die("[ERROR]: There are no blacklists in the Pi-hole folder! Please update the list of ad-serving domains.");
$adlist = (is_file("/etc/pihole/adlists.list") ? "/etc/pihole/adlists.list" : "adlists.default");

// Check for update
function checkUpdate() {
  global $ignoreUpdate, $phbpVersion, $latestVersion;
  if ($ignoreUpdate == "FALSE" && str_replace(".","", $latestVersion) > str_replace(".","", $phbpVersion)) {
    echo " (Update available)";
  }
}

// Get all URLs starting with "http" from $adlist
// $urlList array key expected to match .domains list # in $listMatches!!
// This may not work if admin updates gravity, and later inserts a new hosts URL at anywhere but the end
$urlList = array_values(preg_grep("/(^http)|(^www)/i", file($adlist, FILE_IGNORE_NEW_LINES)));
$urlListCount = count($urlList);

// Strip any combo of HTTP, HTTPS and WWW
$urlList_match = preg_replace("/https?\:\/\/(www.)?/i", "", $urlList);

// Exact search, returning a numerically sorted array of matching .domains
// Returns "list" if manually blacklisted
$listMatches = preg_grep("/(\.domains|blacklist\.txt).*\([1-9]/", file("http://pi.hole/admin/scripts/pi-hole/php/queryads.php?domain=$serverName&exact"));

if (isset($_GET["debug"])) {
	echo "<pre>Initial output:\n";
	print_r($listMatches);
	echo "\n";
}

$listMatches = preg_replace("/(data: ::: \/etc\/pihole\/.....)|(\.(.*)\s)/i", "", $listMatches);

if (isset($_GET["debug"])) {
	echo "Cleaned output:\n";
	print_r($listMatches);
	echo "\n";
}

sort($listMatches, SORT_NUMERIC);

// Return how many lists serverName is featured in
if ($listMatches[0] == "list") {
  $featuredTotal = "-1";
}else{
  $featuredTotal = count($listMatches);
  if(empty($featuredTotal)) $featuredTotal = 0;
}

// Error correction (EG: If gravity has been updated and adlists.list has been removed)
if ($featuredTotal > $urlListCount) $featuredTotal = "0";

if (isset($_GET["debug"])) {
	echo "Featured Total: $featuredTotal";
	die();
}

if ($featuredTotal == "-1") {
    $notableFlag = "Blacklisted manually";
}elseif (!isset($listMatches) && $featuredTotal == "0") {
    $notableFlag = "Unable to retrieve or parse query results from Pi-hole API";
}elseif ($landPage == "FALSE" && $featuredTotal == "0") {
    $notableFlag = "No landing page specified within PHBP config";
}elseif ($landPage !== "FALSE" && $featuredTotal == "0") {
    $notableFlag = "No domain specified within PHBP config";
}elseif ($featuredTotal >= "1") {
  $in = NULL;
  // Define "Featured Flag"
  foreach ($listMatches as $num) {
    // Create a string of flags for serverName
    if(in_array(strtolower($urlList_match[$num]), array_map('strtolower', $ftGeneric))) $in .= "sus ";
    if(in_array(strtolower($urlList_match[$num]), array_map('strtolower', $ftAdvertising))) $in .= "ads ";
    if(in_array(strtolower($urlList_match[$num]), array_map('strtolower', $ftTracking))) $in .= "trc ";
    if(in_array(strtolower($urlList_match[$num]), array_map('strtolower', $ftMalicious))) $in .= "mal ";
    
    // Return value of worst flag to user (EG: Malicious more notable than Suspicious)
    if (substr_count($in, "sus")) $notableFlag = "Suspicious";
    if (substr_count($in, "ads")) $notableFlag = "Advertising";
    if (substr_count($in, "trc")) $notableFlag = "Tracking & Telemetry";
    if (substr_count($in, "mal")) $notableFlag = "Malicious";
  }
} else {
  // Do not show primary flag if we are unable to find one
  $notableFlag = "-1";
}
?>
<!DOCTYPE html><head>
  <meta charset='UTF-8'/>
  <title>Website Blocked</title>
  <link rel='stylesheet' href='<?php echo $customCss; ?>'/>
  <link rel='shortcut icon' href='<?php echo $customIcon; ?>'/>
  <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'/>
  <meta name='robots' content='noindex,nofollow'/>
  <script src="http://pi.hole/admin/scripts/vendor/jquery.min.js"></script>
  <script>
    function tgVis(id) {
      var e = document.getElementById('querylist');
      if(e.style.display == 'block') {
        e.style.display = 'none';
        document.getElementById("info").innerHTML = "More Info";
      }else{
        e.style.display = 'block';
        document.getElementById("info").innerHTML = "Less Info";
      }
    }
  </script>
  <style>
    header h1:before, header h1:after { background-image: url('<?php echo $customLogo; ?>'); }
  </style>
  <noscript><style>
    #querylist { display: block; }
    .buttons { display: none; }
  </style></noscript>
</head><body><header>
  <h1><a href='/'>Website Blocked</a></h1>
</header><main>
  <div class="url">
    Access to the following site has been blocked:
    <span class="msg"><?php echo $serverName; ?></span>
  </div>
  <?php if ($notableFlag !== "-1") { ?>
  <div class="flag">
    This is primarily due to being flagged as:
    <span class='msg'><?php echo $notableFlag; ?></span>
  </div>
  <?php } ?>
  <div class="notice">
    If you have an ongoing use for this website, please <?php echo $noticeStr; ?>.
  </div>
  <div class='buttons'>
    <a id='back' href='javascript:history.back()'>Back to safety</a>
    <?php if ($featuredTotal > "0") echo "<a id='info' onclick='tgVis(\"querylist\");'>More Info</a>"; ?>
  </div> 
  <div id='querylist'>This site is found in <?php echo "$featuredTotal of $urlListCount"; ?> lists:
    <pre id='output'><?php foreach ($listMatches as $num) { echo "<span>[$num]:</span><a href='$urlList[$num]'>$urlList[$num]</a><br/>"; } ?></pre>
    <?php if ($allowWhitelisting == "TRUE") { ?>
    <form class='buttons'>
      <input id='domain' value='<?php echo $serverName; ?>' disabled>
      <input type='password' id='pw' name='pw' placeholder='Pi-hole Password'/>
      <button id='whitelist' type='button'>Whitelist</button>
     </form>
     <pre id='notification' hidden='true'></pre>
     <?php } ?>
  </div>
</main>
<footer>Generated <?php echo date("D g:i A"); ?> by <a href='https://github.com/WaLLy3K/Pi-hole-Block-Page'>Pi-hole Block Page</a> <?php checkUpdate(); if($exeTime == "TRUE") printf("<br/>Execution time: %.2fs\n", microtime(true)-$_SERVER["REQUEST_TIME_FLOAT"]); ?></footer>
<script>
  function add() {
    var domain = $("#domain");
    var pw = $("#pw");
    if(domain.val().length === 0){
      return;
    }

    $.ajax({
      url: "admin/scripts/pi-hole/php/add.php",
      method: "post",
      data: {"domain":domain.val(), "list":"white", "pw":pw.val()},
      success: function(response) {
        $( "#notification" ).removeAttr( "hidden" );
        if(response.indexOf("Pi-hole blocking") !== -1){
          // Reload page after 5 seconds
          setTimeout(function(){window.location.reload(1);}, 5000);
          $( "#notification" ).html("Success! You may have to flush your DNS cache");
        }else{
          $( "#notification" ).html(""+response+"");
        }

      },
      error: function(jqXHR, exception) {
        $( "#notification" ).removeAttr( "hidden" );
        // Assume javascript is enabled, but external files are being blocked (EG: Noscript/Scriptsafe)
        $( "#notification" ).html("Unable to load external jQuery script");
      }
    });
  }

  // Handle enter button for adding domains
  $(document).keypress(function(e) {
      if(e.which === 13 && $("#pw").is(":focus")) {
          add();
      }
  });

  // Handle buttons
  $("#whitelist").on("click", function() {
      add();
  });
</script>
</body></html>
