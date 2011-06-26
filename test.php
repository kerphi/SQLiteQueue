#!/usr/bin/php
<?php

include_once 'SQLiteQueue.php';
$queuedb = dirname(__FILE__).'/test.db';
$queue = new SQLiteQueue($queuedb, 'lifo');

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

// fifo test
unlink($queuedb);
$queue = new SQLiteQueue($queuedb, 'fifo');
$item1 = 'XXX1';
$queue->offer($item1);
$item2 = 'XXX2';
$queue->offer($item2);
$fail = false;
if ($queue->poll() != $item2) $fail = true;
if ($queue->poll() != $item1) $fail = true;
 if (!$fail) {
    echo "[PASS] FIFO queue\n";
} else {
    echo "[FAIL] FIFO queue\n";
}

// push/pop one big item
$item = str_pad('_', 10000000, '_');
$queue->offer($item);
 if (strlen($queue->poll()) == strlen($item)) {
    echo "[PASS] Offer/poll with one big item\n";
} else {
    echo "[FAIL] Offer/poll with one big item\n";
}