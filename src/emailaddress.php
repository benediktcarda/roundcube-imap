<?php

namespace bjc\roundcubeimap;

/**
 * An e-mail address.
*/

class EmailAddress {
    
    private $mailbox;
    private $hostname;
    private $name;
    private $address;
    
    public function __construct(string $address = null, string $mailbox = null, string $hostname = null, string $name = null) {
        
        if (!empty($address)) {
            $this->address  = $address;
            
            $arr = explode('@', $address);
            $this->mailbox = $arr[0];
            $this->hostname = $arr[1];
            
        } elseif (!empty($mailbox) aND !empty($hostname)) {
            $this->mailbox  = $mailbox;
            $this->hostname = $hostname;
            $this->address = $mailbox . '@' . $hostname;
        }
        
        $this->name = $name;
        
    }
    
    /**
     * @return null|string
     */
    public function getAddress()
    {
        return $this->address;
    }
    
    /**
     * Returns address with person name.
     */
    public function getFullAddress(): string
    {
        $address = \sprintf('%s@%s', $this->mailbox, $this->hostname);
        if (null !== $this->name) {
            $address = \sprintf('"%s" <%s>', \addcslashes($this->name, '"'), $address);
        }
        
        return $address;
    }
    
    public function getMailbox(): string
    {
        return $this->mailbox;
    }
    
    /**
     * @return null|string
     */
    public function getHostname()
    {
        return $this->hostname;
    }
    
    /**
     * @return null|string
     */
    public function getName()
    {
        return $this->name;
    }

}
