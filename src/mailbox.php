<?php

namespace bjc\roundcubeimap;

class mailbox {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
    protected $qresync = false;
    protected $condstore = false;
       
    public function __construct($mailboxname, \rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        
        $this->rcube_imap_generic->select($mailboxname);
        $this->qresync   = $this->rcube_imap_generic->getCapability('QRESYNC');
        $this->condstore = $this->qresync ? true : $this->rcube_imap_generic->getCapability('CONDSTORE');
        $res_enable = $this->rcube_imap_generic->enable($this->qresync ? 'QRESYNC' : 'CONDSTORE');
        
        file_put_contents("/tmp/test.txt", "$mailboxname: $this->qresync, $this->condstore - res_enable: $res_enable\n", FILE_APPEND);
        
        
    }
    
    public function getName() {
        return $this->mailboxname;
    }

    public function getMessagehigherthan($lastfetcheduid) {
        
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
        
        $qresync   = $this->rcube_imap_generic->get_capability('QRESYNC');
        $condstore = $qresync ? true : $this->rcube_imap_generic->get_capability('CONDSTORE');
        
        $uidvalidity = $this->getStatus->uidvalidity;
        
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
            
        } else {

            // Enable Qresync
            $enable_result = $this->rcube_imap_generic->enable($qresync ? 'QRESYNC' : 'CONDSTORE');
            
            if ($enable_result === false) {
                
                $returnarray["statusmessage"] = 'Could not enable QRESYNC in connection to mailbox';
                $returnarray["status"] = 0;
                
            } else {

                // Close mailbox if already selected to get most recent data                
                if ($this->rcube_imap_generic->selected == $this->mailboxname) {
                    $this->rcube_imap_generic->close();
                }
                
                $this->rcube_imap_generic->select($this->mailboxname);
                
                $returnarray = $this->rcube_imap_generic->data;
                
            }
            
            $uids    = [];
            $removed = [];
            
            
        }
        
        return $returnarray;
    }
    
    public function getStatus() {
        
        $requestarray = array('UIDNEXT', 'UIDVALIDITY', 'RECENT');
        
        if ($this->qresync == true && $this->condstore == true) {
            $requestarray[] = 'HIGHESTMODSEQ';
            $requestarray[] = 'NOMODSEQ';
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

    public function getData() {
        
        $data = $this->rcube_imap_generic->data;
        
        return $data;
        
    }
    
}