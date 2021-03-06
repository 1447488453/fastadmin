--TEST--
Test stomp_read_frame() - Test the body binary safety
--SKIPIF--
<?php
$require_connection = true;
include dirname(__DIR__) . "/skipif.inc";
?>
--FILE--
<?php

include dirname(__DIR__) . "/config.inc";

$link = stomp_connect(STOMP_ADDRESS);
stomp_send($link, '/queue/test-09', "A test Message\0Foo");
stomp_subscribe($link, '/queue/test-09', array('ack' => 'auto'));
$result = stomp_read_frame($link);
var_dump($result['body']);

?>
--EXPECTF--
string(18) "A test Message Foo"
