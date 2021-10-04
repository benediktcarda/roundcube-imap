<?php

namespace bjc\roundcubeimap;

class message {
    
    protected $rcube_imap_generic;
    protected $rcube_message_header;
    protected $messageheaders;
       
    public function __construct(\rcube_imap_generic $rcube_imap_generic, \rcube_message_header $rcube_message_header) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->rcube_message_header = $rcube_message_header;
        
        $this->messageheaders = new \bjc\roundcubeimap\messageheaders($rcube_message_header);
        
    }

    public function getUID() {
        $returnvalue = $this->rcube_message_header->uid;
        
        return $returnvalue;
    }
    
    public function getID() {
        $returnvalue = $this->rcube_message_header->get('message-id');
        
        return $returnvalue;
    }
    
    public function getDate() {
        
        $returnvalue = $this->messageheaders->get('date');
        
        return $returnvalue;
    }
    
    public function getTimestamp() {
        
        $timestamp = $this->rcube_message_header->get("timestamp");

        return $timestamp;
    }

    public function getSubject() {
        $returnvalue = $this->messageheaders->get("subject");

        return $returnvalue;
    }

    public function getFrom() {
        $returnvalue = $this->messageheaders->get('from');
        
        return $returnvalue;        
    }
    
    public function getTo() {
        $returnvalue = $this->messageheaders->get('to');
        
        return $returnvalue;        
    }
    
    public function getCC() {
        $returnvalue = $this->messageheaders->get('cc');
        
        return $returnvalue;
    }
    
    
    public function isAnswered() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["ANSWERED"])) {
            return true;
        } else {
            return false;
        }
        
    }

    public function isDeleted() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["DELETED"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    public function isDraft() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["DRAFT"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    public function isSeen() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["SEEN"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    public function isFlagged() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["FLAGGED"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    public function getHeader($field) {
        
        $returnvalue = $this->rcube_message_header->get($field);

        if (is_array($returnvalue)) {
            $returnvalue = (object) $returnvalue;
        } 
        
        return $returnvalue;
        
    }
    
    public function getHeaders() {
        
        
        
    }
        
}