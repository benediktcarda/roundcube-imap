<?php

namespace bjc\roundcubeimap;

class messageheaders {
    
    protected $rcube_message_header;

    private $obj_headers = ['date', 'from', 'to', 'subject', 'reply-to', 'cc', 'bcc', 'folder', 'content-transfer-encoding', 'in-reply-to', 'content-type', 'charset', 'references', 'disposition-notification-to', 'x-confirm-reading-to', 'message-id', 'x-priority'];
    
    public function __construct(\rcube_message_header $rcube_message_header) {
        $this->rcube_message_header = $rcube_message_header;
        
    }

    public function get($headername) {

        if ($headername == 'to' OR $headername == 'reply-to' OR $headername == 'cc' OR $headername == 'bcc') {
            $value = $this->rcube_message_header->get($headername);
                
            $returnvalue = \bjc\roundcubeimap\utils::decodeAddresslist($value);

        } elseif ($headername == 'from') {
            $value = $this->rcube_message_header->get($headername);
            
            $returnvalue = \bjc\roundcubeimap\utils::decodeAddress($value);

        } elseif ($headername == 'date') {
            $value = $this->rcube_message_header->get($headername);
            
            $returnvalue = \rcube_utils::anytodatetime($value);
            
        } else {
            
            $returnvalue = $this->rcube_message_header->get($headername);
                            
        }
        
        return $returnvalue;
        
    }
    
    
    public function getAll() {
        
        $returnarray = array();

        foreach ($this->obj_headers as $item) {            
            $returnarray["$item"] = $this->get($item);
        }
        
        foreach ($this->rcube_message_header->others as $key => $item) {
            $returnarray["$key"] = $this->get($key);
        }

        return $returnarray;
        
    }
 
    
}