<?php

namespace bjc\roundcubeimap;

class mailbox {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
       
    public function __construct($mailboxname, \bjc\roundcubeimap\rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        
    }
    
    public function getName() {
        return $this->mailboxname;
    }

    public function getMessages($message_set) {
    
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $message_set, true);
        
        file_put_contents("/tmp/test.txt", "\nGET MESSAGES: \n" . print_r($result, true), FILE_APPEND);
        
    }
    
    public function getStatus() {
        
        $result = $this->rcube_imap_generic->status($this->mailboxname, array('UIDNEXT', 'UIDVALIDITY', 'RECENT'));
        
        file_put_contents("/tmp/test.txt", "\nGET STATUS: \n" . print_r($result, true), FILE_APPEND);
        
    }
    
    public function setFlag(array $flags, array $messageUIDs) {

        foreach ($flags as $flag) {
            $this->rcube_imap_generic->flag($this->mailboxname, $messageUIDs, $flag);
        }
        
    }
    
    public function clearFlag(array $flags, array $messageUIDs) {

        foreach ($flags as $flag) {
            $this->rcube_imap_generic->flag($this->mailboxname, $messageUIDs, $flag);
        }

    }
    
    

    
    
        
}