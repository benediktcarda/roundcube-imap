<?php

namespace bjc\roundcubeimap;

/**
 * Extends the rcube_imap_generic class with further imap specific code (to not touch the original from roundcube)
 * Object that holds the connection to the server and performs actions
 *
 */

class imap_generic extends \rcube_imap_generic {

    /**
     * Fetch the full MIME message of a given mailbox and uid
     *
     * @param string $mailbox   Name of mailbox in logged in account
     * @param int    $uid       UID
     * @param bool   $readOnly  Selecting read-only mode
     *
     * @return string message data
     */

    public function fetchMimemessage($mailbox, $uid, $readOnly = false) {

        if (!$this->select($mailbox, null, $readOnly)) {
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

        $replace_match = '/^(?:A[0-9]+ OK FETCH .*|\* [0-9]+ FETCH .*|\)\s*)$(?:\r\n|\n)?/im';
        $message_result = preg_replace($replace_match, '', $message);

        return $message_result;

    }

}

?>