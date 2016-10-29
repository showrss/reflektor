<?php
// This is a single (string) or list (array) of cURL proxy strings to be used.
// The value 'null', an empty array, or an empty string will use no proxy.

/* This is an list of providers, defined as an array of arrays. Each provider
 * array(
 *     'url' => 'http://somedomain.tld/torrent/{info_hash}.torrent',
 *     // The text '{info_hash}' will automatically be replaced with the infohash
 *     // This is the only mandatory parameter.
 *     
 *     'cookie' => 'cooking string',
 *     // An optional string to send as the cookie jar. Empty or undefiend sends nothing.
 *     // The default is an empty cookie jar.
 *     
 *     'useragent' => 'Something/1.0',
 *     // An optional User-Agent string, or array of strings, to be used with this provider.
 *     // If an array is used, a User-Agent string will be picked at random.
 *     // The default is: Googlebot/2.1 (+http://www.google.com/bot.html)
 *     
 *     'proxy' => 'socks5h://user:pass@somehost:1080',
 *     // An optional proxy string, or array of proxy strings, for use when in
 *     // connecting to upstream providers. If an array is given, a proxy will
 *     // be selected at random. An empty string, empty array, or a 'null'
 *     // element will use no proxy at all for the connection.
 *     // The default is to use no proxy.
 */
$providers = array(
    array(	// itorrents.org
        'url' => 'http://itorrents.org/torrent/{info_hash}.torrent',
        'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.63 Safari/537.36',
        ),
    array(	// torrasave.site
        'url' => 'https://torrasave.site/torrent/{info_hash}.torrent',
        'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.63 Safari/537.36',
        ),
    array(	// thetorrent.org
        'url' => 'http://thetorrent.org/{info_hash}.torrent?_=',
        'cookie' => 'thetorrent=1',
        'useragent' => "Googlebot/2.1 (+http://www.google.com/bot.html)",
        ),
    array(	// torcache.net
        'url' => 'http://torcache.net/torrent/{info_hash}.torrent',
        'useragent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.63 Safari/537.36',
        ),
    );

