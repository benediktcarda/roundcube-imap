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

        $key      = $this->nextTag();
        $cmd      = 'UID ' . 'FETCH';
        $request  = "$key $cmd $uid RFC822";

        if (!$this->putLine($request)) {
            $line   = $this->readReply();
            $result = $this->parseResult($line);

            return $result;
            
        }

    }

    public function appendRawmessage($mailbox, $messagedata) {

        

    }

}

?>