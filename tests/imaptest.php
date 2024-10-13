<?php
// tests/imaptest.php

use PHPUnit\Framework\TestCase;

class imaptest extends TestCase
{

    public function testAttachment() {

        $config = require __DIR__ . '/../config/testconfig.php';

        $server = new \bjc\roundcubeimap\server($config["imaphost"]);
        $connection = $server->authenticate($config["username"], $config["password"]);
        $mailbox = $connection->getMailbox($config["mailbox"]);
        $message = $mailbox->getMessage($config["messageuid"]);
        $attachments = $message->getAttachments();

        print_r($attachments);

        $this->assertTrue(true);

    }
}