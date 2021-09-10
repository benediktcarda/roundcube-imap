<?php

namespace bjc\roundcubeimap;

class connection {
    
    protected $rcube_imap_generic;
       
    public function __construct(\bjc\roundcubeimap\rcube_imap_generic $rcube_imap_generic) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        
    }
    
    public function getMailboxes() {
        
        $mailboxes = $this->rcube_imap_generic->listMailboxes('', '*');

        file_put_contents("/tmp/test.txt", "MAILBOXES: \n" . print_r($mailboxes, true), FILE_APPEND);
        
        $returnarray = array();
        
        foreach ($mailboxes as $mailbox) {
//            $returnarray[] = new \bjc\roundcubeimap\mailbox($mailbox, $this->rcube_imap_generic);
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
    
}