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

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/choice_lists/$id");
} else {
    curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/choice_lists");
}
$json = curl_exec($ch);
echo curl_error($ch);
curl_close($ch);
header('Content-type: application/json');
echo $json;
?>