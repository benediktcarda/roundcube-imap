<?php

namespace bjc\roundcubeimap;

class mailbox {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
       
    public function __construct($mailboxname, \bjc\roundcubeimap\rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        
    }

    public function getMessages($message_set) {
    
        $result = $this->rcube_imap_generic->fetch($this->mailboxname, $message_set, $is_uid = true);
        
        file_put_contents("/tmp/test.txt", "GET MESSAGES: \n" . print_r($result, true), FILE_APPEND);
        
    }
    
    
    
        
}