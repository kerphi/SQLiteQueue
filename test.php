#!/usr/bin/php
<?php

include_once 'SQLiteQueue.php';
$queue = new SQLiteQueue(dirname(__FILE__).'/test.db');

// push/pop one item
$item = 'XXX';
$queue->offer($item);
 if ($queue->poll() == $item) {
    echo "[PASS] Offer/poll with one item\n";
} else {
    echo "[FAIL] Offer/poll with one item\n";
}
 

// push/pop 3 items
$item1 = 'XXX1';
$queue->offer($item1);
$item2 = 'XXX2';
$queue->offer($item2);
$item3 = 'XXX3';
$queue->offer($item3);
$fail = false;
if ($queue->poll() != $item1) $fail = true;
if ($queue->poll() != $item2) $fail = true;
if ($queue->poll() != $item3) $fail = true;
 if (!$fail) {
    echo "[PASS] Offer/poll with 3 item\n";
} else {
    echo "[FAIL] Offer/poll with 3 item\n";
}