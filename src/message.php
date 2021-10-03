<?php

namespace bjc\roundcubeimap;

class message {
    
    protected $rcube_imap_generic;
    protected $rcube_message_header;
       
    public function __construct(\rcube_imap_generic $rcube_imap_generic, \rcube_message_header $rcube_message_header) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->rcube_message_header = $rcube_message_header;
        
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
        
        $date = $this->rcube_message_header->get("date");
        
        $datetime_object = \rcube_utils::anytodatetime($date);
        
        return $datetime_object;
        
    }

    public function getSubject() {
        
        $returnvalue = $this->rcube_message_header->get('subject');
     
        return $returnvalue;
        
    }

    public function getFrom() {
        
        $input = $this->rcube_message_header->get('from');
        
        $returnarray = \rcube_mime::decode_address_list($input);
        $name = $returnarray[1]["name"];
        $address = $returnarray[1]["mailto"];
        
        $emailaddress = new \bjc\roundcubeimap\emailaddress($address, null, null, $name); 
        
        return $emailaddress;
        
    }
    
    public function getTo() {
        
        $input = $this->rcube_message_header->get('to');
        
        $addressarray = \rcube_mime::decode_address_list($input);
   
        $returnarray = array();
        
        foreach ($returnarray as $key => $item) {
            $name = $item["name"];
            $address = $item["mailto"];
        
            $emailaddress = new \bjc\roundcubeimap\emailaddress($address, null, null, $name);
            
            $returnarray[] = $emailaddress;
            
        }
        
        return $returnarray;
        
    }
    
    public function getCC() {
        
        $input = $this->rcube_message_header->get('cc');
        
        $addressarray = \rcube_mime::decode_address_list($input);
        
        $returnarray = array();
        
        foreach ($returnarray as $key => $item) {
            $name = $item["name"];
            $address = $item["mailto"];
            
            $emailaddress = new \bjc\roundcubeimap\emailaddress($address, null, null, $name);
            
            $returnarray[] = $emailaddress;
            
        }
        
        return $returnarray;
        
    }
    
    
    public function isAnswered() {
        
        $flags = $this->rcube_message_header->flags;
        
        return $flags;
        
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