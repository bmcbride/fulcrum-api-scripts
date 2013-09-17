<?php
# Your API Key goes here...
$api_key = '';

# CURL options
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'X-ApiToken: ' . $api_key
));

# API key required
if (!isset($api_key) || $api_key === '') {
    echo 'Missing API Key! The API key is available from your <a href="https://web.fulcrumapp.com/settings/api">profile page</a>.';
    exit;
}

if (isset($_GET['photo_id'])) {
    $photo_id = $_GET['photo_id'];
    curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/photos/$photo_id.jpg");
    if (isset($_GET['size'])) {
        if ($_GET['size'] === 'thumbnail') {
            curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/photos/$photo_id/thumbnail.jpg");
        }
        if ($_GET['size'] === 'large') {
            curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/photos/$photo_id/large.jpg");
        }
    }
    $photo = curl_exec($ch);
    header("Content-Type: image/jpeg");
    echo $photo;
    exit;
}

# form_id required
if (!isset($_GET['form_id'])) {
    echo '<p>Missing required URL parameter: <i>form_id</i></p>';
    echo '<p>Available forms for this API key: </p>';
    curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/forms");
    $forms_json = curl_exec($ch);
    $forms = json_decode($forms_json, TRUE);
    foreach ($forms as $key => $value) {
        if ($key === 'forms') {
            foreach ($value as $formKey => $formValue) {
                echo '<a href="?form_id=' . $formValue['id'] . '">' . $formValue['name'] . '</a><br>';
            }
        }
    }
    exit;
} else {
    $form_id = $_GET['form_id'];
}

# Fetch form details
curl_setopt($ch, CURLOPT_URL, "https://api.fulcrumapp.com/api/v2/forms/$form_id");
$form_json = curl_exec($ch);
$form = json_decode($form_json, TRUE);

# Check for valid form_id
if (empty($form) || $form_id === '') {
    echo 'Invalid form_id. <a href="' . $_SERVER['PHP_SELF'] . '">View available forms for this API key</a>';
    exit;
}
# Get form elements and send to findLabel function
foreach ($form['form'] as $key => $value) {
    if ($key === 'elements') {
        findLabel($value);
    }
}
# Loop through nested elements arrays for non Section types and build label/key lookup array
function findLabel($array) {
    global $label;
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (isset($value['key']) && $value['type'] !== 'Section') {
                # This is the important part! Change 'data_name' to 'label' for labels.
                $label[$value['key']] = $value['data_name'];
            } else {
                findLabel($value);
            }
        }
    }
}
# Fetch records
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
$records_json = curl_exec($ch);
curl_close($ch);
$records = json_decode($records_json, TRUE);

# Assemble GeoJSON feature collection array
$geojson = array(
    'type' => 'FeatureCollection',
    'features' => array()
);
# Loop through records to build feature arrays
foreach ($records['records'] as $recordKey => $recordValue) {
    # Get GeoJSON "properties" objects
    $properties = $recordValue['form_values'];
    # Loop through properties and replace key with label
    foreach ($properties as $key => $value) {
        if (isset($label[$key])) {
            $properties[$label[$key]] = $properties[$key];
            unset($properties[$key]);
        }
    }
    # Loop through properties again to make choice value & photo fields strings
    foreach ($properties as $propertyKey => &$propertyValue) {
        # Join choice values & other values and convert to string
        if (isset($propertyValue['choice_values']) && is_array($propertyValue['choice_values'])) {
            if (isset($propertyValue['other_values'])) {
                $propertyValue = array_merge($propertyValue['choice_values'], $propertyValue['other_values']);
            }
            $propertyValue = implode(', ', $propertyValue);
        }
        # If it's an array of photo objects, just give us a string of the photo id's
        if (is_array($propertyValue)) {
            $photoArray = array();
            foreach ($propertyValue as $photoKey => $photoValue) {
                $photo['id'] = $photoValue['photo_id'];
                array_push($photoArray, $photo['id']);
            }
            $propertyValue = implode(', ', $photoArray);
        }
    }
    # Add additional record values to properties
    $properties['fulcrum_id'] = $recordValue['id'];
    $properties['fulcrum_status'] = $recordValue['status'];
    # Assemble feature arrays
    $feature = array(
        'type' => 'Feature',
        'geometry' => array(
            'type' => 'Point',
            'coordinates' => array(
                $recordValue['longitude'],
                $recordValue['latitude']
            )
        ),
        'properties' => $properties
    );
    # Add feature arrays to feature collection array
    array_push($geojson['features'], $feature);
}
header('Content-type: application/json');
echo json_encode($geojson, JSON_NUMERIC_CHECK);
?>