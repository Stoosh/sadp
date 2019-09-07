<?php


function get_url_signature($url)
{
    $secret = getenv('GOOGLE_API_SECRET');

    return hash_hmac('sha1', $url, $secret);
}

$noImageChecksum = 'cf6ac0c960582a23dc5da958454e7f9b';

$noKeyFound = !empty(getenv('GOOGLE_API_KEY'));
if (!empty($_GET)) {
    $term = $_GET['location'];

    $termsQueue = [$term];
    $termsQueue[] = $term . " s";
    $termsQueue[] = $term . " r";
    $termsQueue[] = $term . " l";

    $apiKey = getenv('GOOGLE_API_KEY');
    $baseUrl = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
    $images = [];
    $addressProcessed = [];
    $checksumProcessed = [];
    foreach($termsQueue as $term)
    {
        $autocompleteUrl = sprintf('%s?key=%s&input=%s', $baseUrl, $apiKey, urlencode($term));

        $jsonFile = sprintf('/tmp/json/%s.json', md5($term));
        if(!file_exists($jsonFile)) {
            file_put_contents($jsonFile, file_get_contents($autocompleteUrl));
        }

        $matches = json_decode(file_get_contents($jsonFile), true);

        foreach($matches['predictions'] as $prediction) {
            $key = urlencode($prediction['description']);

            if(isset($addressProcessed[$key])) {
                continue;
            }

            $imageUrl = sprintf('https://maps.googleapis.com/maps/api/streetview?location=%s&size=456x456&key=%s', $key, $apiKey);
            $signature = get_url_signature($imageUrl);
            $imageUrl = sprintf('%s&=%s', $imageUrl, $signature);
            $imageFile = sprintf('/tmp/images/%s.jpg', md5($key));


            if(!file_exists($imageFile)) {
                $contents = file_get_contents($imageUrl);

                if(md5($contents) == $noImageChecksum) {
                    continue;
                }

                $output = file_put_contents($imageFile, $contents);
            }

            $images[] = [
                'url' => $imageFile,
                'address' => $prediction['description']
            ];

            $addressProcessed[$key] = true;
        }
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
            font-size: 24px;
            padding:5px;
        }

        .item {
            float:left;
            margin:25px;
            width:456px;
        }

        .item h2 {
            word-wrap: break-word;
            min-height:60px;
        }

        .container {
            width: 100%;
        }

        .address-input {
            margin: auto;
            width: 50%;
        }

    </style>
</head>
<body>
    <div class="container">
        <?php if ($noKeyFound): ?>
            <div><h1>Google API KEY not found</h1></div>
        <?php endif; ?>
        <div class="address-input">
            <form method="GET" action="<?= $_SERVER['PHP_SELF']; ?>">
                <input name="location" type="text" value="<?= $_GET['location'] ?: ''; ?>" />
                <input type="submit" value="Submit" />
            </form>
        </div>
        <div class="item-list">
            <?php foreach($images as $image) : ?>
                <div class="item">
                    <h2><?= $image['address']; ?></h2>
                    <img src="<?= $image['url']; ?>" />
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

