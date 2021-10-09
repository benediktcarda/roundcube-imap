<?php

namespace bjc\roundcubeimap;

class connection {
    
    protected $rcube_imap_generic;
    protected $connection_data = array();
       
    public function __construct(\rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        
        $this->connection_data["capabilities"]["qresync"] = $this->rcube_imap_generic->getCapability('QRESYNC');
        $this->connection_data["capabilities"]["condstore"] = $this->qresync ? true : $this->rcube_imap_generic->getCapability('CONDSTORE');
        
        if ($this->connection_data["capabilities"]["qresync"] OR $this->connection_data["capabilities"]["condstore"]) {
            $result_enable = $this->rcube_imap_generic->enable($this->connection_data["capabilities"]["qresync"] ? 'QRESYNC' : 'CONDSTORE');
            
            if ($result_enable === false) {
                $this->connection_data["qresync_enable_failed"] = 1;
            }
            
        }
        
    }
    
    public function getMailboxes() {
        
        $mailboxes = $this->rcube_imap_generic->listMailboxes('', '*');

        $returnarray = array();
        
        foreach ($mailboxes as $mailboxname) {
            $returnarray[] = new \bjc\roundcubeimap\mailbox($mailboxname, $this->rcube_imap_generic, $this->connection_data);
        }
        
        return $returnarray;
        
    }

    public function getMailbox($mailboxname) {
        
        $mailbox_obj = new \bjc\roundcubeimap\mailbox($mailboxname, $this->rcube_imap_generic, $this->connection_data);

        return $mailbox_obj;
        
    }
 
    public function deleteMailbox($mailboxname) {
        $this->rcube_imap_generic->deleteFolder($mailboxname);
    }
        
}