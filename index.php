<?php


function get_url_signature($url)
{
    $secret = getenv('GOOGLE_API_SECRET');
    $decoded_key = base64_decode($secret);

    return hash_hmac('sha1', $url, $secret);
}

if (!empty($_GET)) {
    $term = $_GET['location'];
    $apiKey = getenv('GOOGLE_API_KEY');
    $baseUrl = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    $autocompleteUrl = sprintf('%s?key=%s&input=%s', $baseUrl, $apiKey, urlencode($term));

    $jsonFile = sprintf('cache/json/%s.json', md5($term));
    if(!file_exists($jsonFile)) {
        file_put_contents($jsonFile, file_get_contents($autocompleteUrl));
    }

    $matches = json_decode(file_get_contents($jsonFile), true);

    $images = [];
    foreach($matches['predictions'] as $prediction) {
        $key = urlencode($prediction['description']);
        $imageUrl = sprintf('https://maps.googleapis.com/maps/api/streetview?location=%s&size=456x456&key=%s', $key, $apiKey);
        $signature = get_url_signature($imageUrl);
        $imageUrl = sprintf('%s&=%s', $imageUrl, $signature);
        $imageFile = sprintf('cache/images/%s.jpg', md5($key));


        if(!file_exists($imageFile)) {
            $output = file_put_contents($imageFile, file_get_contents($imageUrl));
        }

        $images[] = $imageFile;
    }
}

?>

<DOCTYPE! html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Basic html layout example</title>

    <style>
        input[type=text] {
            width: 500px;
            font-size: 30px;
            padding:5px;
        }
    </style>
</head>
<body>
    <div>
        <form method="GET" action="<?= $_SERVER['PHP_SELF']; ?>">
            <input name="location" type="text" value="<?= $_GET['location'] ?: ''; ?>" />
            <input type="submit" value="Submit" />
        </form>
    </div>
    <div>
        <?php foreach($images as $image) : ?>
            <img src="<?= $image; ?>" />
        <?php endforeach; ?>
    </div>
</body>
</html>

