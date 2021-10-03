<?php

namespace bjc\roundcubeimap;

class mailbox {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
       
    public function __construct($mailboxname, \rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        
    }
    
    public function getName() {
        return $this->mailboxname;
    }

    public function getMessageSequence($message_set) {
    
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $message_set, true);
        
        return $result;
        
    }
    
    public function getStatus() {
        
        $result = $this->rcube_imap_generic->status($this->mailboxname, array('UIDNEXT', 'UIDVALIDITY', 'RECENT'));
        
        $obj = new \stdClass();
        
        foreach ($result as $key => $item) {
            $key_to_lower = strtolower($key);
            $obj->$key_to_lower = $item;
        }
        
        return $obj;
        
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