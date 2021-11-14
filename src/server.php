<?php

namespace bjc\roundcubeimap;

class server {
    
    protected $host;
    protected $ssl_mode;
    
    /**
     * Factory to establish connections to the IMAP server and return the connections as objects
     *
     * @param string $host
     * @param string $ssl_mode  SSL mode can be 'ssl', 'tls' or 'plain'
     *
     */
    
    public function __construct($host, $ssl_mode = 'tls') {
        $this->host = $host;
        $this->ssl_mode = $ssl_mode;
        
        require_once __DIR__ . '/../lib/Roundcube/bootstrap.php';
        
    }
    
    /**
     * Connects and authenticates to an IMAP server
     *
     * @param string $username 
     * @param string $password 
     *
     * @return object instance of \bjc\roundcubeimap\connection
     */
    
    public function authenticate($username, $password) {

        $rcube_imap_generic = new \rcube_imap_generic();
        
        $result_connect = $rcube_imap_generic->connect($this->host, $username, $password, array('ssl_mode' => $this->ssl_mode));
        
        if ($result_connect == false) {
            $errormsg = $rcube_imap_generic->error;
        
            throw new \Exception("Connection failed: $errormsg");
            
        } else {
            
            $connection = new \bjc\roundcubeimap\connection($rcube_imap_generic);
            
        }
        
        return $connection;
        
    }
    
}