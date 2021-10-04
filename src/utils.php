<?php

namespace bjc\roundcubeimap;

class utils {
   
    static public function decodeAddresslist($addresslist) {
        
        $addressarray = \rcube_mime::decode_address_list($addresslist);
        
        $returnarray = array();
        
        foreach ($addressarray as $address_key => $address_item) {
            $name = $address_item["name"];
            $address = $address_item["mailto"];
            
            $emailaddress = new \bjc\roundcubeimap\emailaddress($address, null, null, $name);
            
            $returnarray[] = $emailaddress;
            
        }
        
        return $returnarray;
        
    }
    
    static public function decodeAddress($addressinput) {
        
        $addressarray = \rcube_mime::decode_address_list($addressinput);
        
        $returnarray = array();
        
        $address_item = reset($addressarray);
        $name = $address_item["name"];
        $address = $address_item["mailto"];
            
        $emailaddress = new \bjc\roundcubeimap\emailaddress($address, null, null, $name);
            
        return $emailaddress;
        
    }
    
    
}