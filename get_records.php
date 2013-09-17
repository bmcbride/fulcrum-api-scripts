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
    curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/records/$id");
} else {
    # Fetch records with query params
    $url = "https://api.fulcrumapp.com/api/v2/records";
    if (isset($_GET['form_id'])) {
        $param['form_id'] = $_GET['form_id'];
    }
    if (isset($_GET['project_id'])) {
        $param['project_id'] = $_GET['project_id'];
    }
    if (isset($_GET['bounding_box'])) {
        $param['bounding_box'] = $_GET['bounding_box'];
    }
    if (isset($_GET['updated_since'])) {
        $param['updated_since'] = $_GET['updated_since'];
    }
    if (isset($_GET['page'])) {
        $param['page'] = $_GET['page'];
    }
    if (isset($_GET['per_page'])) {
        $param['per_page'] = $_GET['per_page'];
    }
    $params = '';
    if (isset($param)) {
        foreach ($param AS $key => $val) {
            $params .= ($params == '' ? '?' : '&') . $key . '=' . $val;
        }
        $url .= $params;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
}
$json = curl_exec($ch);
echo curl_error($ch);
curl_close($ch);
header('Content-type: application/json');
echo $json;
?>