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

            // handle UNKNOWN-CTE response - RFC 3516, try again with standard BODY request
            if (!$found && preg_match('/^' . $key . ' NO \[(UNKNOWN-CTE|PARSE)\]/i', $line)) {
                continue;
            }

            // skip first line
            if (preg_match('/^Message: \* ([0-9]+) FETCH (.*)$/', $line, $m)) {
                continue;
            }

            // skip last but one line
            if (preg_match('/^\* ([0-9]+) FETCH (.*)$/', $line, $m)) {
                continue;
            }

            // skip last line
            if (preg_match('/^($/', $line, $m)) {
                continue;
            }

            $message .= $line . "\r\n";
            
        }
        while (!$this->startsWith($line, $key, true));

        file_put_contents("/tmp/test.txt", "Message: $message", FILE_APPEND);

        return $message;

    }

    public function appendRawmessage($mailbox, $messagedata) {

        

    }

}

?>