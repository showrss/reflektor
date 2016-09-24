<?php
/* reflektor: torrent cache */
set_time_limit(15);
header("X-Served-By: ".php_uname('n'));

function serve_log($message) {
	// Easily toggle on/off
	//error_log(trim($message), 4);
	//error_log(trim($message).PHP_EOL, 3, "/tmp/reflektor-serve.log");
}

//$cacheurl = "/torrentsp";
$cacheurl = "/torrents";

// This is a list of providers, defined as an array of arrays.
$providers = array();
include('providers.inc.php');

$cachedir = realpath(dirname(__FILE__).'/../cache');

if($_GET['ih']) {
	$ih = trim(strtoupper($_GET['ih']));
	$ih = preg_replace('/[^A-Z0-9]/', '', $ih);
	$dir0 = substr($ih, 0, 2);
	
	if(strlen($ih) == 40) {
		$loc = $cachedir.'/'.$dir0.'/'.$ih.'.torrent';
		$exists = (@file_exists($loc))?true:false;
		$notnull = (@filesize($loc)>0)?true:false;
		
		if($exists) {
			$diff = time()-filemtime($loc);
			$mature = ($diff>3600)?true:false;
		} else {
			$mature = false;
			$diff = 0;
		}
		
		if(isset($_REQUEST['force']))
			$exists = false;
		
		if($exists and $notnull) {
			serve_log("[$ih] Cache hit ($loc) of an upstream success. Age=$diff. Mature=$mature.");
			if($diff > 86400) {
				serve_log("[$ih] Touched cachefile ($loc).");
				touch($loc);
			}
			
			header('Content-type: application/x-bittorrent');
			header('X-Accel-Redirect: '.$cacheurl.'/'.$dir0.'/'.$ih.'.torrent');
			header('Content-Disposition: attachment; filename="'.$ih.'.torrent"');
			header("X-Cache: HIT");
			header("X-Cache-Age: $diff");
		
		} elseif($exists and !$notnull and !$mature) {
			// error: 
			serve_log("[$ih] Cache hit ($loc) of an upstream miss. Age=$diff.");
			header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found', true, 404);
			header("X-Cache: CACHED_MISS");
			header("X-Cache-Age: $diff");
			echo '<h1>Torrent not found</h1><p>Torrent not found in any of the ' .
			    'providers. Retrying later.<br /><small>Cached miss.</small></p>';
		
		} else {
			serve_log("[$ih] Cache miss ($loc) Age=$diff. Mature=$mature. ".
			    "Exists=$exists. Notnull=$notnull.");
			// not exists, null or not, mature
			@mkdir(dirname($loc), 0755, true);
			touch($loc);
			$hit = false;
			$data = null;
			
			// XXX Do NOT shuffle, because torcache.net sucks. Always try the sites in-order.
			// shuffle($providers);
			foreach($providers as $provider) {
				$location = str_replace('{info_hash}', $ih, $provider['url']);
				serve_log("[$ih] Using location: $location");
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $location);
				curl_setopt($ch, CURLOPT_REFERER, dirname($location));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($ch, CURLOPT_BUFFERSIZE, 512);
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				curl_setopt($ch, CURLINFO_HEADER_OUT, true);
				if(isset($provider['proxy']) && sizeof($provider['proxy'])) {
					if(is_array($provider['proxy']))
						curl_setopt($ch, CURLOPT_PROXY,
						    $proxy = $provider['proxy'][array_rand($provider['proxy'])]);
					else
						curl_setopt($ch, CURLOPT_PROXY, $proxy = $provider['proxy']);
					serve_log("[$ih] Using proxy: $proxy");
				}
				if(isset($provider['useragent']) && sizeof($provider['useragent'])) {
					if(is_array($provider['useragent']))
						curl_setopt($ch, CURLOPT_USERAGENT,
						    $useragent = $provider['useragent'][array_rand($provider['useragent'])]);
					else
						curl_setopt($ch, CURLOPT_USERAGENT, $useragent = $provider['useragent']);
					serve_log("[$ih] Using user-agent: $useragent");
				} else
					curl_setopt($ch, CURLOPT_USERAGENT,
					    "Googlebot/2.1 (+http://www.google.com/bot.html)");
				if(isset($provider['cookie']))
					curl_setopt($ch, CURLOPT_COOKIE, $provider['cookie']);
				// Aborts downloads larger than 2MB
				curl_setopt($ch, CURLOPT_HEADERFUNCTION,
				    function($ch, $headers) {
				    	serve_log("Returned Headers: $headers");
				    	return strlen($headers);
				    });
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION,
				    function($dls, $dl, $uls, $ul) {
				    	if($dl > (2048 * 1024))
				    		serve_log("Aborting too-large download after ${dl}b.");
				    	return ($dl > (2048 * 1024)) ? 1 : 0;
				    });
				$data = curl_exec($ch);
				serve_log("[$ih] Curl returned: ".curl_error($ch)." Fetched ".
				    strlen($data)." bytes: ".print_r(curl_getinfo($ch), true));
				curl_close($ch);
				
				if(stristr($data, '</html>')) {
					serve_log("[$ih] Got HTML back. Skipping to next provider.");
					$data = false;
				}
				
				if($data) break;
			}
			
			if($data) {
				serve_log("[$ih] Successful fetch! Caching to $loc.");
				file_put_contents($loc, $data);
				touch($loc);
				
				header('Content-type: application/x-bittorrent');
				header('X-Accel-Redirect: '.$cacheurl.'/'.$dir0.'/'.$ih.'.torrent');
				header('Content-Disposition: attachment; filename="'.$ih.'.torrent"');
				header('X-Cache: MISS');
				header('X-Cache-Age: 0');
			} else {
				serve_log("[$ih] Fetch failed.");
				header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found', true, 404);
				header('X-Cache: MISS');
				header('X-Cache-Age: 0');
				echo '<h1>Torrent not found</h1><p>Torrent not found in any of ' .
				    'the providers. Retrying later.<br /><small>Try and miss.' .
				    '</small></p>';
			}
		}
	} else {
		serve_log("[$ih] Invalid hash. Exiting 404.");
		header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found', true, 404);
		echo '<h1>Invalid info_hash</h1><p>Please verify it and try again.</p>';
	}
}
