<?php

namespace bjc\roundcubeimap;

/**
 * Extends the rcube_imap_generic class with further imap specific code (to not touch the original from roundcube)
 * Object that holds the connection to the server and performs actions
 *
 */

class imap_generic extends \rcube_imap_generic {

    public function fetchRawmessage($mailbox, $uid) {

        if (!$this->select($mailbox)) {
            return false;
        }

        $key      = $this->nextTag();
        $cmd      = 'UID ' . 'FETCH';
        $request  = "$key $cmd $uid RFC822";

        if (!$this->putLine($request)) {
            $this->setError(self::ERROR_COMMAND, "Failed to send $cmd command");
            return false;
        }

        $message = "";

        do {
            $line = $this->readFullLine(4096);
            
            if (!$line) {
                break;
            }

            $message .= $line . "\r\n";
            
        }
        while (!$this->startsWith($line, $key, true));

        $replace_match = '/^(?:A[0-9]+ OK FETCH .*|\* [0-9]+ FETCH .*|\))$(?:\r\n|\n)?/gim';
        $message_result = preg_replace($replace_match, '', $message);

        file_put_contents("/tmp/test.txt", "$message_result", FILE_APPEND);

        return $message;

    }

    public function appendRawmessage($mailbox, $messagedata) {

        

    }

}

?>