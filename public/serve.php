<?php
/* reflektor: torrent cache */

$GLOBALS['CACHES'] = array( 'torrage.com' 	=>	'http://torrage.com/torrent/{info_hash}.torrent',
							'torcache.net' 	=> 	'http://torcache.net/torrent/{info_hash}.torrent');


$GLOBALS['LOCATION'] = dirname(__FILE__).'/../cache/';

if($_GET['ih']) {
	$ih = trim(strtoupper($_GET['ih']));
	$ih = preg_replace('/[^A-Z0-9]/', '', $ih);

	if(strlen($ih) == 40) {
		$loc = $GLOBALS['LOCATION'].$ih.'.torrent';
		$exists = (@file_exists($loc))?true:false;
		$notnull = (@filesize($loc)>0)?true:false;

		if($exists) {
			$diff = time()-filemtime($loc);
			$mature = ($diff>3600)?true:false;
		} else {
			$mature = false;
			$diff = 0;
		}

		if($exists and $notnull) {
			if($diff > 86400) {
				touch($loc);
			}

			header('Content-type: application/x-bittorrent');
			header('X-Accel-Redirect: /torrents/'.$ih.'.torrent');
			header('Content-Disposition: attachment; filename="'.$ih.'.torrent"');
		} elseif($exists and !$notnull and !$mature) {
			// error: 
			header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found', true, 404);
			echo '<h1>Torrent not found</h1><p>Torrent not found in any of the providers. Retrying later.<br /><small>Cached miss.</small></p>';
		} else {
			// not exists, null or not, mature
			touch($loc);
			$hit = false;

			foreach($GLOBALS['CACHES'] as $provider) {
				$location = str_replace('{info_hash}', $ih, $provider);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $location);
				curl_setopt($ch, CURLOPT_REFERER, $location);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
				curl_setopt($ch, CURLOPT_BUFFERSIZE, 512);
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($dls, $dl, $uls, $ul){
						return ($dl > (2048 * 1024)) ? 1 : 0;
				});
				$data = curl_exec($ch);
				curl_close($ch);

				if(stristr($data, '</html>')) $data = false;

				if($data) break;
			}

			if($data) {
				file_put_contents($loc, $data);
				touch($loc);
				
				header('Content-type: application/x-bittorrent');
				header('X-Accel-Redirect: /torrents/'.$ih.'.torrent');
				header('Content-Disposition: attachment; filename="'.$ih.'.torrent"');
			} else {
				header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found', true, 404);
				echo '<h1>Torrent not found</h1><p>Torrent not found in any of the providers. Retrying later.<br /><small>Try and miss.</small></p>';
			}
		}
	} else {
		header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found', true, 404);
		echo '<h1>Invalid info_hash</h1><p>Please verify it and try again.</p>';
	}
}
