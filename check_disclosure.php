<?php

$html = file_get_contents(__DIR__ . '/dashboard_auth.html');

// Find first ui-disclosure
$start = strpos($html, '<ui-disclosure');
$end = strpos($html, '</ui-disclosure>', $start) + 16;
$disclosure = substr($html, $start, $end - $start);

// Parse it
$dom = new DOMDocument();
@$dom->loadHTML('<html><body>' . $disclosure . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

$uiDisclosure = $dom->getElementsByTagName('ui-disclosure')->item(0);

if ($uiDisclosure) {
    echo "ui-disclosure found!\n\n";
    echo "Children:\n";
    $i = 0;
    foreach ($uiDisclosure->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && $child instanceof DOMElement) {
            $i++;
            echo "$i. <{$child->tagName}";
            if ($child->hasAttribute('class')) {
                $class = substr($child->getAttribute('class'), 0, 50);
                echo " class=\"{$class}...\"";
            }
            echo ">\n";
        }
    }
    
    echo "\n\nLast element child tag: ";
    $lastChild = null;
    foreach ($uiDisclosure->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && $child instanceof DOMElement) {
            $lastChild = $child;
        }
    }
    if ($lastChild) {
        echo "<{$lastChild->tagName}>\n";
        echo "Is it button? " . ($lastChild->tagName === 'button' ? 'YES - PROBLEM!' : 'NO - OK') . "\n";
    }
}
