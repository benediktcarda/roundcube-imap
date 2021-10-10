<?php

namespace bjc\roundcubeimap;

class utils {
   
    static public function decodeAddresslist($addresslist) {
        
        $addressarray = \rcube_mime::decode_address_list($addresslist);
        
        $returnarray = array();
        
        foreach ($addressarray as $address_key => $address_item) {
            $name = $address_item["name"];
            $address = $address_item["mailto"];
            
            file_put_contents("/tmp/test.txt", "address: $address, name: $name\n", FILE_APPEND);
            
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
    
    static public function decodeMessageRanges($rangestring) {
        
        $uidarray = array();
        
        $rangearray = explode(",", $rangestring);
        
        foreach ($rangearray as $rangeitem) {
            if (is_int($rangeitem)) {
                $uidarray[] = $rangeitem;
            } else {
                $rangestartandend = explode(':', $rangeitem);
                $rangestart = $rangestartandend[0];
                $rangeend = $rangestartandend[1];
                
                $i = $rangestart;
                
                while ($i <= $rangeend) {
                    $uidarray[] = $i;
                    
                    $i++;
                }
                
            }
            
        }
        
        return $uidarray;
    }
    
}