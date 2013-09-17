<?php
# Your API Key goes here...
$api_key = '';

# API key required
if (!isset($api_key) || $api_key === '') {
    echo 'Missing API Key! The API key is available from your <a href="https://web.fulcrumapp.com/settings/api">profile page</a>.';
    exit;
}

# CURL options
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-ApiToken: ' . $api_key
));

# Photo ID required
if (!isset($_GET['id']) || $_GET['id'] === '') {
    echo 'Missing URL parameter <i>id</i>';
    exit;
} else {
    $id = $_GET['id'];
}

# Format required
if (!isset($_GET['format'])) {
	echo 'Missing URL parameter <i>format</i>. Options are <b>json</b> or <b>jpg</b>';
    exit;
} else {
	$format = $_GET['format'];
	curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/photos/$id.$format");
}

# Size optional
if (isset($_GET['size'])) {
	$size = $_GET['size'];
	if ($size === 'thumbnail') {
		curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/photos/$id/thumbnail.$format");
	}
	if ($size === 'large') {
		curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/photos/$id/large.$format");
	}
}

# Set header according to format
if ($format === 'json') {
	header('Content-type: application/json');
}
if ($format === 'jpg') {
	header('Content-Type: image/jpeg');
}

$photo = curl_exec($ch);
echo curl_error($ch);
curl_close($ch);
echo $photo;
?>