#!/usr/bin/php
<?php

// Cloudflare auth info
$token = 'your-secret-token';
$email = 'your-cloudflare-account@email.com';

// Domain
$domain = 'yourwebsite.com';

// Hostname
$hostname = 'raspberry-pi.yourwebsite.com';

// Current external ip
$ip = trim(file_get_contents('http://ipinfo.io/ip'));

//  Api url
$apiUrl = 'https://www.cloudflare.com/api_json.html';

// Check if we have a real ip value
if (empty($ip)) {
	die('Unexpected value for IP. Not updating anything..');
}

// Create curl handle
$ch = curl_init();

// Configure curl
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);

// Payload
$curlPayload = [
	'a'     => 'rec_load_all',
	'tkn'   => $token,
	'email' => $email,
	'z'     => $domain,
];

// Set payload
curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPayload);

// Execute curl call & capture output, abort if failed
if (!$output = curl_exec($ch)) {
	// Close curl handle
	curl_close($ch);

	// Stop here
	die('Curl call failed!');
}

// Decode json response
$results = json_decode($output);

// If the result failed, abort here & tell us why
if ($results->result !== 'success') {
	die('Error adding ip address for hostname: ' . $results->msg);
}

// Loop over entries
$recordId = null;
foreach($results->response->recs->objs as $dnsEntry) {
	// If we find an entry that matches the hostname, abort
	if ($dnsEntry->name === $hostname) {
		$recordId = $dnsEntry->rec_id;
		break;
	}
}

// If we found a matching record, update
if ($recordId) {

	// New curl payload
	$curlPayload = [
		'a'     => 'rec_edit',
		'tkn'   => $token,
		'email' => $email,
		'z'     => $domain,
		'id'    => $recordId,
		'type'  => 'A',
		'name'  => $hostname,
		'content' => $ip,
		'ttl'   => '1',
	];

	// Set payload
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPayload);

	// Execute curl call & capture output, abort if failed
	if (!$output = curl_exec($ch)) {
		// Close curl handle
		curl_close($ch);

		// Stop here
		die('Error updating IP address (' . $ip . ') for: ' . $hostname . ' on ' . $domain);
	}

	// Decode json response
	$results = json_decode($output);

	// If the result failed, abort here & tell us why
	if ($results->result !== 'success') {
		// Close curl handle
		curl_close($ch);

		// Stop here
		die('Error updating IP address (' . $ip . ') for: ' . $hostname . ' on ' . $domain . ', api response: ' . $results->msg);
	}

	// Close curl handle
	curl_close($ch);

	// Stop here
	die('Updated IP address (' . $ip . ') for: ' . $hostname . ' on ' . $domain . ' successfully.');
}


///////////////////////////////////////////////////////////////////////////////////////////////
// If we reached this point, it means we didn't find any existing record and will create one //
///////////////////////////////////////////////////////////////////////////////////////////////


// New curl payload
$curlPayload = [
	'a'     => 'rec_new',
	'tkn'   => $token,
	'email' => $email,
	'z'     => $domain,
	'type'  => 'A',
	'name'  => $hostname,
	'content' => $ip,
	'ttl'   => '1',
];

// Set payload
curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPayload);

// Execute curl call & capture output, abort if failed
if (!$output = curl_exec($ch)) {
	// Close curl handle
	curl_close($ch);

	// Stop here
	die('Error adding IP address (' . $ip . ') for: ' . $hostname . ' on ' . $domain);
}

// Decode json response
$results = json_decode($output);

// If the result failed, abort here & tell us why
if ($results->result !== 'success') {
	// Close curl handle
	curl_close($ch);

	// Stop here
	die('Error adding IP address (' . $ip . ') for: ' . $hostname . ' on ' . $domain . ', api response: ' . $results->msg);
}

// Close curl handle
curl_close($ch);

// Stop here
die('Added IP address (' . $ip . ') for: ' . $hostname . ' on ' . $domain . ' successfully.');
