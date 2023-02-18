<?php

namespace bjc\roundcubeimap;

/**
 * Extends the rcube_imap_generic class with further imap specific code (to not touch the original from roundcube)
 * Object that holds the connection to the server and performs actions
 * @param string       $mailboxname         name of mailbox that should be opened
 * @param object       $rcube_imap_generic  object that holds the connection to the server and performs actions
 * @param array        $connection_data     an array of values about the connection status and capabilities
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

        // $result = $this->execute($request);

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

        file_put_contents("/tmp/test.txt", "Message: $message", FILE_APPEND);

    }

    public function appendRawmessage($mailbox, $messagedata) {

        

    }

}

?>