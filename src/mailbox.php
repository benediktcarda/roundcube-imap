<?php

namespace bjc\roundcubeimap;

class mailbox {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
    protected $connection_data;
       
    public function __construct($mailboxname, \rcube_imap_generic $rcube_imap_generic, $connection_data) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        $this->connection_data = $connection_data;
        
    }
    
    public function getName() {
        return $this->mailboxname;
    }

    public function getMessageshigherthan($lastfetcheduid) {
        
        $nextuid = $lastfetcheduid + 1;
        $messages = $this->getMessageSequence("$nextuid" . ":*");
        
        return $messages;
        
    }
    
    public function getMessageSequence($message_set) {
    
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $message_set, true, false, ['message-id', 'uid', 'references']);
        
        $resultarray = array();
        
        foreach ($result as $rcube_message_header) {
            
            $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header);
            
            $resultarray[] = $message;
            
        }
        
        return $resultarray;
        
    }
    
    public function synchronize($stored_highestmodseq, $stored_uidvalidity) {
        
        $qresync   = $this->connection_data["capabilities"]["qresync"];
        $condstore = $this->connection_data["capabilities"]["condstore"];
        $qresyn_enable_failed = $this->connection_data["qresync_enable_failed"];
        
        $status_object = $this->getStatus;
        $uidvalidity = $status_object->uidvalidity;
        $highestmodseq = $status_object->highestmodseq;
        
        $returnarray = array();
        
        if (!$qresync && !$condstore) {
            
            $returnarray["statusmessage"] = 'Mailbox does not support synchronization';
            $returnarray["status"] = 0;
            
        } elseif ($uidvalidity != $stored_uidvalidity) {
            
            $returnarray["statusmessage"] = 'UID validity changed, cannot synchronize';
            $returnarray["status"] = 0;
            
        } elseif (empty($stored_highestmodseq)) {
            
            $returnarray["statusmessage"] = 'No stored highestmodseq, cannot synchronize';
            $returnarray["status"] = 0;
            
        } elseif (empty($highestmodseq)) {
            
            $returnarray["statusmessage"] = 'QRESYNC not supported on specified mailbox';
            $returnarray["status"] = 0;
            
        } elseif (!empty($qresyn_enable_failed)) {

            $returnarray["statusmessage"] = 'Failed to enable QRESYNC on this connection';
            $returnarray["status"] = 0;            
               
        } else {
                                      
            $query_items = ['UID', 'RFC822.SIZE', 'FLAGS', 'INTERNALDATE'];
            $headers     = ['DATE', 'FROM', 'TO', 'SUBJECT', 'CONTENT-TYPE', 'CC', 'REPLY-TO', 'LIST-POST', 'DISPOSITION-NOTIFICATION-TO', 'X-PRIORITY', 'MESSAGE-ID', 'UID', 'REFERENCES'];
                
            $query_items[] = 'BODY.PEEK[HEADER.FIELDS (' . implode(' ', $headers) . ')]';
            
            $message_set = "1" . ":*";
            
            $result = fetch($this->mailboxname, $message_set, true, $query_items, $stored_highestmodseq, true);

            $resultarray = array();
            
            foreach ($result as $rcube_message_header) {
                
                $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header);
                
                $resultarray[] = $message;
                
            }
            
            return $resultarray;
            
        }
                
        
        return $returnarray;
    }
    
    public function getStatus() {
        
        $requestarray = array('UIDNEXT', 'UIDVALIDITY', 'RECENT');
        
        if ($this->capabilities["qresync"] == true && $this->capabilities["condstore"] == true) {
            $requestarray[] = 'HIGHESTMODSEQ';
        }
        
        $result = $this->rcube_imap_generic->status($this->mailboxname, $requestarray);
        
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