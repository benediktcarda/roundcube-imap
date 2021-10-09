<?php

namespace bjc\roundcubeimap;

class connection {
    
    protected $rcube_imap_generic;
       
    public function __construct(\rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        
    }
    
    public function getMailboxes() {
        
        $mailboxes = $this->rcube_imap_generic->listMailboxes('', '*');

        $returnarray = array();
        
        foreach ($mailboxes as $mailboxname) {
            $returnarray[] = new \bjc\roundcubeimap\mailbox($mailboxname, $this->rcube_imap_generic);
        }
        
        return $returnarray;
        
    }

    public function getMailbox($mailboxname) {
        
        $mailbox_obj = new \bjc\roundcubeimap\mailbox($mailboxname, $this->rcube_imap_generic);

        return $mailbox_obj;
        
    }
 
    public function deleteMailbox($mailboxname) {
        $this->rcube_imap_generic->deleteFolder($mailboxname);
    }
    
    
    public function test($capabilities_array) {
        
        foreach ($capabilities_array as $capability) {
            $result = $this->rcube_imap_generic->getCapability($capability);
            
            if ($result == true) {
                $return .= "<div>$capability: TRUE</div>";
            } else {
                $return .= "<div>$capability: FALSE</div>";
            }
            
            return $return;
            
        }
        
    }
    
}