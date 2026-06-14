<?php

$html = file_get_contents('nesco_response.html');

$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
$xpath = new DOMXPath($dom);

$label = 'গ্রাহকের নাম';
$query = "//label[contains(text(), '{$label}')]/following-sibling::div//input | //label[contains(text(), '{$label}')]/following::input[1]";
$nodes = $xpath->query($query);

echo "Nodes found for label '{$label}': " . $nodes->length . "\n";
if ($nodes->length > 0) {
    echo "Value: " . $nodes->item(0)->getAttribute('value') . "\n";
} else {
    // Let's print out all labels to see how they are represented in the DOM
    echo "Listing all labels:\n";
    foreach ($xpath->query("//label") as $lbl) {
        echo "- " . trim($lbl->textContent) . "\n";
    }
}
