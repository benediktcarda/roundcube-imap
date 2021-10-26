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

    public function getMessageshigherthan($lastfetcheduid, $add_headers = []) {
        
        $nextuid = $lastfetcheduid + 1;
        $messages = $this->getMessageSequence("$nextuid" . ":*", $add_headers);
        
        return $messages;
        
    }
    
    public function getMessageupdate($lastfetcheduid, $add_headers = []) {
        
        $query_items = ['UID', 'FLAGS'];
        
        $headers = ["message-id"];
        
        if (!empty($add_headers)) {
            $add_headers = array_map('strtoupper', $add_headers);
            $headers     = array_unique(array_merge($headers, $add_headers));
        }
        
        $query_items[] = 'BODY.PEEK[HEADER.FIELDS (' . implode(' ', $headers) . ')]';
        
        
        $message_set = "1:" . $lastfetcheduid;
        
        $result = $this->rcube_imap_generic->fetch($this->mailboxname, $message_set, true, $query_items);
        
        $resultarray = array();
        
        foreach ($result as $rcube_message_header) {
            
            $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header);
            
            $resultarray[] = $message;
            
        }
        
        return $resultarray;
        
    }
    
    public function getMessageSequence($message_set, $add_headers = []) {

        $headers = ['message-id', 'uid', 'references'];
        
        $headers = array_unique(array_merge($headers, $add_headers));
        
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $message_set, true, false, $headers);

        $resultarray = array();
        
        foreach ($result as $rcube_message_header) {
            
            $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header);
            
            $resultarray[] = $message;
            
        }
        
        return $resultarray;
        
    }
    
    public function getMessage($uid, $add_headers = []) {
        
        $headers = ['message-id', 'uid', 'references'];
        $headers = array_unique(array_merge($headers, $add_headers));
        
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $uid, true, false, $add_headers);
        
        $message = reset($result);
        
        return $message;
        
    }
    
    public function synchronize($stored_highestmodseq, $stored_uidvalidity, $add_headers = []) {
        
        $qresync   = $this->connection_data["capabilities"]["qresync"];
        $condstore = $this->connection_data["capabilities"]["condstore"];
        $qresync_enable_failed = $this->connection_data["qresync_enable_failed"];
        
        $status_object = $this->getStatus();
        
        $uidvalidity = (int) $status_object->uidvalidity;
        $highestmodseq = (int) $status_object->highestmodseq;
        
        $stored_highestmodseq = (int) $stored_highestmodseq;
        $stored_uidvalidity = (int) $stored_uidvalidity;
        
        $returnarray = array();
        
        if (!$qresync && !$condstore) {
            
            $returnarray["statusmessage"] = 'Mailbox does not support synchronization';
            $returnarray["status"] = 0;
            
        } elseif ($uidvalidity <> $stored_uidvalidity) {
            
            $returnarray["statusmessage"] = 'UID validity changed, cannot synchronize';
            $returnarray["status"] = 0;
            
        } elseif (empty($stored_highestmodseq)) {
            
            $returnarray["statusmessage"] = 'No stored highestmodseq, cannot synchronize';
            $returnarray["status"] = 0;
            
        } elseif (empty($highestmodseq)) {
            
            $returnarray["statusmessage"] = 'QRESYNC not supported on specified mailbox';
            $returnarray["status"] = 0;
            
        } elseif (!empty($qresync_enable_failed)) {

            $returnarray["statusmessage"] = 'Failed to enable QRESYNC on this connection';
            $returnarray["status"] = 0;            
               
        } else {
                                      
            $query_items = ['UID', 'RFC822.SIZE', 'FLAGS', 'INTERNALDATE'];
            $headers     = ['DATE', 'FROM', 'TO', 'SUBJECT', 'CONTENT-TYPE', 'CC', 'REPLY-TO', 'LIST-POST', 'DISPOSITION-NOTIFICATION-TO', 'X-PRIORITY', 'MESSAGE-ID', 'UID', 'REFERENCES'];

            if (!empty($add_headers)) {
                $add_headers = array_map('strtoupper', $add_headers);
                $headers     = array_unique(array_merge($headers, $add_headers));
            }
            
            $query_items[] = 'BODY.PEEK[HEADER.FIELDS (' . implode(' ', $headers) . ')]';
            
            $message_set = "1" . ":*";
            
            $result = $this->rcube_imap_generic->fetch($this->mailboxname, $message_set, true, $query_items, $stored_highestmodseq, true);
            
            $messagearray = array();
            
            foreach ($result as $rcube_message_header) {
                
                $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header);
                
                $messagearray[] = $message;
                
            }

            $returnarray["messagearray"] = $messagearray;
            $returnarray["vanishedarray"] = \bjc\roundcubeimap\utils::decodeMessageRanges($this->rcube_imap_generic->data["VANISHED"]);
            $returnarray["vanishedrange"] = $this->rcube_imap_generic->data["VANISHED"];
            $returnarray["status"] = 1;
            
        }
        
        return $returnarray;
    }
        
    public function getStatus() {
        
        $requestarray = array('UIDNEXT', 'UIDVALIDITY', 'RECENT');
        
        if ($this->connection_data["capabilities"]["qresync"] == true && $this->connection_data["capabilities"]["condstore"] == true) {
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
    
    
    
    public function checkCondstore() {
        
        $returnvalue = true;
        
        $condstore = $this->connection_data["capabilities"]["condstore"];
        
        if (!$condstore) {
            $returnvalue = false;
        }
        
        return $returnvalue;
        
    }
    
    
    public function checkQresync() {
        
        $qresync   = $this->connection_data["capabilities"]["qresync"];
        $condstore = $this->connection_data["capabilities"]["condstore"];
        $qresync_enable_failed = $this->connection_data["qresync_enable_failed"];
        
        $status_object = $this->getStatus();
        
        $uidvalidity = (int) $status_object->uidvalidity;
        $highestmodseq = (int) $status_object->highestmodseq;
        
        $stored_highestmodseq = (int) $stored_highestmodseq;
        $stored_uidvalidity = (int) $stored_uidvalidity;

        $returnvalue = true;
        
        if (!$qresync && !$condstore) {
            $returnvalue = false;
        } elseif (empty($highestmodseq)) {
            $returnvalue = false;
        } elseif (!empty($qresync_enable_failed)) {
            $returnvalue = false;
        }

        return $returnvalue;
        
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