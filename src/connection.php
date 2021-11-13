<?php

namespace bjc\roundcubeimap;

/**
 * Handles established connections to the IMAP server
 *
 * @param object       $rcube_imap_generic  object that holds the connection to the server and performs actions
 * 
 */

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
    
    /**
     * Get all mailboxes of the account the connection is established to
     *
     * @return array containing objects of class \bjc\roundcubeimap\mailbox
     */
    
    public function getMailboxes() {
        
        $mailboxes = $this->rcube_imap_generic->listMailboxes('', '*');

        $returnarray = array();
        
        foreach ($mailboxes as $mailboxname) {
            $returnarray[] = new \bjc\roundcubeimap\mailbox($mailboxname, $this->rcube_imap_generic, $this->connection_data);
        }
        
        return $returnarray;
        
    }

    /**
     * Get all mailboxes of the account the connection is established to
     *
     * @param string $mailboxname Name of mailbox the method should return
     *
     * @return object of class \bjc\roundcubeimap\mailbox
     */
    
    public function getMailbox($mailboxname) {
        
        $mailbox_obj = new \bjc\roundcubeimap\mailbox($mailboxname, $this->rcube_imap_generic, $this->connection_data);

        return $mailbox_obj;
        
    }
 
    /**
     * Create mailbox in the account the connection is established to
     * Throws exception on error
     *
     * @param string $mailboxname Name of mailbox the method should create
     *
     * @return bool true on success
     * 
     */
    
    public function createMailbox($mailboxname) {
        
        $result = $this->rcube_imap_generic->createFolder($mailboxname);
        
        if ($result == false) {
            throw new \Exception('Mailbox creation failed.');
        }
        
        return true;
        
    }
    
    /**
     * Delete mailbox in the account the connection is established to
     * Throws exception on error
     *
     * @param string $mailboxname Name of mailbox the method should delete
     *
     * @return bool true on success
     *
     */
    
    public function deleteMailbox($mailboxname) {
        $result = $this->rcube_imap_generic->deleteFolder($mailboxname);
        
        if ($result == false) {
            throw new \Exception('Mailbox deletion failed.');
        }

        return true;
        
    }

    /**
     * Rename mailbox in the account the connection is established to
     * Throws exception on error
     *
     * @param string $mailboxname Name of mailbox the method should rename
     * @param string $new_mailboxname New name the mailbox should get
     *
     * @return bool true on success
     *
     */
    
    public function renameMailbox($mailboxname, $new_mailboxname) {
        $result = $this->rcube_imap_generic->renameFolder($mailboxname, $new_mailboxname);
        
        if ($result == false) {
            throw new \Exception('Rename mailbox failed.');
        }
        
        return true;
        
    }
    
    /**
     * Clear all messages from mailbox in the account the connection is established to
     * Throws exception on error
     *
     * @param string $mailboxname Name of mailbox the method should clear
     *
     * @return bool true on success
     *
     */
    
    public function clearMailbox($mailboxname) {
        $result = $this->rcube_imap_generic->clearFolder($mailboxname);
        
        if ($result == false) {
            throw new \Exception('Clearing mailbox failed.');
        }
        
        return true;
        
    }

}