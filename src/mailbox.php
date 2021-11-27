<?php

namespace bjc\roundcubeimap;

/**
 * Represents a mailbox (folder) of an email account of the submitted connection 
 *
 * @param string       $mailboxname         name of mailbox that should be opened
 * @param object       $rcube_imap_generic  object that holds the connection to the server and performs actions
 * @param array        $connection_data     an array of values about the connection status and capabilities
 *
 */

class mailbox {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
    protected $connection_data;
       
    public function __construct($mailboxname, \rcube_imap_generic $rcube_imap_generic, array $connection_data) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        $this->connection_data = $connection_data;
        
    }
    
    /**
     * Returns the name of the mailbox
     *
     * @return string     name of mailbox
     */
    
    public function getName() {
        return $this->mailboxname;
    }

    
    /**
     * Returns an array of objects that represent messages with a uid higher than first param
     * 
     * @param int    $lastfetcheduid    All messages having a uid higher than this value will be returned
     * @param bool   $bodystr           On true fetches the bodystructure for the messages and saves it in the message object
     * @param array  $add_headers       An array of strings containing names of additional headers (more than standard) that should be fetched
     * 
     * @return array containing objects of class \bjc\roundcubeimap\message
     */
    
    public function getMessageshigherthan($lastfetcheduid, $bodystr = false, array $add_headers = []) {
        
        $nextuid = $lastfetcheduid + 1;
        $messages = $this->getMessageSequence("$nextuid" . ":*", $bodystr, $add_headers);
        
        return $messages;
        
    }

    
    /**
     * Returns an array of objects that represent messages lower than the first input param
     * This function by default returns a minimum set of headers to only get updates on flags of the messages
     *
     * @param int    $lastfetcheduid    All messages having a uid lower or equal than this value will be returned
     * @param bool   $bodystr           On true fetches the bodystructure for the messages and saves it in the message object
     * @param array  $add_headers       An array of strings containing names of additional headers (more than standard) that should be fetched
     *
     * @return array containing objects of class \bjc\roundcubeimap\message
     */
    
    public function getMessageupdate($lastfetcheduid, $bodystr = false, $add_headers = []) {
        
        $query_items = ['UID', 'FLAGS'];
        
        $headers = ["message-id"];
        
        if (!empty($add_headers)) {
            $add_headers = array_map('strtoupper', $add_headers);
            $headers     = array_unique(array_merge($headers, $add_headers));
        }
        
        $query_items[] = 'BODY.PEEK[HEADER.FIELDS (' . implode(' ', $headers) . ')]';
        
        if ($bodystr == true) {
            $query_items[] = 'BODYSTRUCTURE';
        }
        
        
        $message_set = "1:" . $lastfetcheduid;
        
        $result = $this->rcube_imap_generic->fetch($this->mailboxname, $message_set, true, $query_items);
        
        $resultarray = array();
        
        foreach ($result as $rcube_message_header) {
            
            $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header, $this->mailboxname);
            
            $resultarray[] = $message;
            
        }
        
        return $resultarray;
        
    }
    
    
    /**
     * Returns an array of objects that represent messages according to the given message set
     *
     * @param string|array    $message_set    Comma separated list or array of message uids or message ranges 
     * @param bool   $bodystr           On true fetches the bodystructure for the messages and saves it in the message object
     * @param array  $add_headers       An array of strings containing names of additional headers (more than standard) that should be fetched
     *
     * @return array containing objects of class \bjc\roundcubeimap\message
     */
    
    public function getMessageSequence($message_set, $bodystr = false, $add_headers = []) {

        $headers = ['message-id', 'uid', 'references'];
        
        $headers = array_unique(array_merge($headers, $add_headers));
        
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $message_set, true, $bodystr, $headers);

        $resultarray = array();
        
        foreach ($result as $rcube_message_header) {
            
            $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header, $this->mailboxname);
            
            $resultarray[] = $message;
            
        }
        
        return $resultarray;
        
    }
    
    /**
     * Returns an object that represents the message having the given uid
     *
     * @param int    $uid               UID of the message that should be retrieved    
     * @param bool   $bodystr           On true fetches the bodystructure for the message and saves it in the message object
     * @param array  $add_headers       An array of strings containing names of additional headers (more than standard) that should be fetched
     *
     * @return object of class \bjc\roundcubeimap\message
     */
    
    public function getMessage($uid, $bodystr = false, $add_headers = []) {
        
        $headers = ['message-id', 'uid', 'references'];
        $headers = array_unique(array_merge($headers, $add_headers));
        
        $result = $this->rcube_imap_generic->fetchHeaders($this->mailboxname, $uid, true, $bodystr, $add_headers);
        
        $rcube_message_header = reset($result);
        
        if ($rcube_message_header instanceof \rcube_message_header) {
            $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header, $this->mailboxname);
        } else {
            $message = null;
        }
        
        return $message;
        
    }
    
    
    /**
     * Returns an array of objects that represent messages changed compared to the given highestmodseq
     *
     * @param int    $stored_highestmodseq   Last known modseq number. All changed messages since that modseq will be retunred
     * @param int    $stored_uidvalidity     Last known uidvalidity value
     * @param bool   $bodystr           On true fetches the bodystructure for the message and saves it in the message object
     * @param array  $add_headers       An array of strings containing names of additional headers (more than standard) that should be fetched
     *
     * @return array   Array having the following keys:
     *                 messagearray - array containing objects of class \bjc\roundcubeimap\message
     *                 vanishedarray - array containing all uids that have been deleted since last known modseq
     *                 vanishedrange - comma seperated list of range of messages that have been deleted since last known modseq
     *                 statusmessage - error message
     *                 status - 1 if ok, 0 if not ok
     */

    public function synchronize($stored_highestmodseq, $stored_uidvalidity, $bodystr = false, $add_headers = []) {
        
        $qresync   = $this->connection_data["capabilities"]["qresync"];
        $condstore = $this->connection_data["capabilities"]["condstore"];
        $qresync_enable_failed = $this->connection_data["qresync_enable_failed"];
        
        $status_object = $this->getStatus();
        
        $uidvalidity = (int) $status_object->uidvalidity;
        $highestmodseq = (int) $status_object->highestmodseq;
        
        $stored_highestmodseq = (int) $stored_highestmodseq;
        $stored_uidvalidity = (int) $stored_uidvalidity;
        
        $returnarray = array();
        
        if (!$qresync && !$condstore) {
            
            $returnarray["statusmessage"] = 'Mailbox does not support synchronization';
            $returnarray["status"] = 0;
            
        } elseif ($uidvalidity <> $stored_uidvalidity) {
            
            $returnarray["statusmessage"] = 'UID validity changed, cannot synchronize';
            $returnarray["status"] = 0;
            
        } elseif (empty($stored_highestmodseq)) {
            
            $returnarray["statusmessage"] = 'No stored highestmodseq, cannot synchronize';
            $returnarray["status"] = 0;
            
        } elseif (empty($highestmodseq)) {
            
            $returnarray["statusmessage"] = 'QRESYNC not supported on specified mailbox';
            $returnarray["status"] = 0;
            
        } elseif (!empty($qresync_enable_failed)) {

            $returnarray["statusmessage"] = 'Failed to enable QRESYNC on this connection';
            $returnarray["status"] = 0;            
               
        } else {
                                      
            $query_items = ['UID', 'RFC822.SIZE', 'FLAGS', 'INTERNALDATE'];
            $headers     = ['DATE', 'FROM', 'TO', 'SUBJECT', 'CONTENT-TYPE', 'CC', 'REPLY-TO', 'LIST-POST', 'DISPOSITION-NOTIFICATION-TO', 'X-PRIORITY', 'MESSAGE-ID', 'UID', 'REFERENCES'];

            if (!empty($add_headers)) {
                $add_headers = array_map('strtoupper', $add_headers);
                $headers     = array_unique(array_merge($headers, $add_headers));
            }
            
            $query_items[] = 'BODY.PEEK[HEADER.FIELDS (' . implode(' ', $headers) . ')]';
            
            if ($bodystr == true) {
                $query_items[] = 'BODYSTRUCTURE';
            }
            
            $message_set = "1" . ":*";
            
            $result = $this->rcube_imap_generic->fetch($this->mailboxname, $message_set, true, $query_items, $stored_highestmodseq, true);
            
            $messagearray = array();
            
            foreach ($result as $rcube_message_header) {
                
                $message = new \bjc\roundcubeimap\message($this->rcube_imap_generic, $rcube_message_header, $this->mailboxname);
                
                $messagearray[] = $message;
                
            }

            $returnarray["messagearray"] = $messagearray;
            $returnarray["vanishedarray"] = \bjc\roundcubeimap\utils::decodeMessageRanges($this->rcube_imap_generic->data["VANISHED"]);
            $returnarray["vanishedrange"] = $this->rcube_imap_generic->data["VANISHED"];
            $returnarray["status"] = 1;
            
        }
        
        return $returnarray;
    }

    /**
     * Returns a standard class object that contains the values uidnext, uidvalidity, recent and if available highestmodseq
     *
     * @return obj   standard class object that contains the values uidnext, uidvalidity, recent and if available highestmodseq
     */
    
    public function getStatus() {
        
        $requestarray = array('UIDNEXT', 'UIDVALIDITY', 'RECENT');
        
        if ($this->connection_data["capabilities"]["qresync"] == true && $this->connection_data["capabilities"]["condstore"] == true) {
            $requestarray[] = 'HIGHESTMODSEQ';
        }
        
        $result = $this->rcube_imap_generic->status($this->mailboxname, $requestarray);
        
        $obj = new \stdClass();
        
        foreach ($result as $key => $item) {
            $key_to_lower = strtolower($key);
            $obj->$key_to_lower = $item;
        }
        
        return $obj;
        
    }
    
    
    /**
     * Returns true if condstore is available for this mailbox, false if not
     *
     * @return bool   true = condstore available, false = condstore is not available
     */
    
    public function checkCondstore() {
        
        $returnvalue = true;
        
        $condstore = $this->connection_data["capabilities"]["condstore"];
        
        if (!$condstore) {
            $returnvalue = false;
        }
        
        return $returnvalue;
        
    }
    
    /**
     * Returns true if qresync is available for this mailbox, false if not
     *
     * @return bool   true = qresync available, false = qresync is not available
     */    
    
    public function checkQresync() {
        
        $qresync   = $this->connection_data["capabilities"]["qresync"];
        $condstore = $this->connection_data["capabilities"]["condstore"];
        $qresync_enable_failed = $this->connection_data["qresync_enable_failed"];
        
        $status_object = $this->getStatus();
        
        $uidvalidity = (int) $status_object->uidvalidity;
        $highestmodseq = (int) $status_object->highestmodseq;
        
        $stored_highestmodseq = (int) $stored_highestmodseq;
        $stored_uidvalidity = (int) $stored_uidvalidity;

        $returnvalue = true;
        
        if (!$qresync && !$condstore) {
            $returnvalue = false;
        } elseif (empty($highestmodseq)) {
            $returnvalue = false;
        } elseif (!empty($qresync_enable_failed)) {
            $returnvalue = false;
        }

        return $returnvalue;
        
    }
    
    
    /**
     * Sets flags to a given message set
     * Throws exception on error
     *
     * @param array  $flags             Array of strings with flags that should be set
     * @param string|array    $message_set    Comma separated list or array of message uids or message ranges
     *
     * @return bool true on success
     */
    
    public function setFlag(array $flags, $messageset) {

        foreach ($flags as $flag) {
            $result = $this->rcube_imap_generic->flag($this->mailboxname, $messageset, $flag);
        }
        
        if ($result == false) {
            throw new \Exception('Setting flags failed.');
        }

        return true;
        
    }
    
    
    /**
     * Unsets flags to a given message set
     * Throws exception on error
     *
     * @param array  $flags             Array of strings with flags that should be set
     * @param string|array    $message_set    Comma separated list or array of message uids or message ranges
     *
     * @return bool true on success
     *
     */
    
    public function clearFlag(array $flags, $messageset) {

        foreach ($flags as $flag) {
            $result = $this->rcube_imap_generic->unflag($this->mailboxname, $messageset, $flag);
        }

        if ($result == false) {
            throw new \Exception('Unsetting flags failed.');
        }
        
        return true;
        
    }

    
    /**
     * Copies messages from this mailbox to another mailbox of the same account
     * Throws exception on error
     *
     * @param string|array    $message_set    Comma separated list or array of message uids or message ranges
     * @param string          $to_mailboxname Mailbox the messages should be copied to
     *
     * @return bool true on success
     *
     */
    
    public function copyMessages($messageset, $to_mailboxname) {
    
        $result = $this->rcube_imap_generic->copy($messageset, $this->mailboxname, $to_mailboxname);
        
        if ($result == false) {
            throw new \Exception('Copying messages failed.');
        }
        
        return true;
        
    }
    
    
    /**
     * Move messages from this mailbox to another mailbox of the same account
     * Throws exception on error
     *
     * @param string|array    $message_set    Comma separated list or array of message uids or message ranges
     * @param string          $to_mailboxname Mailbox the messages should be copied to
     *
     * @return bool true on success
     *
     */
    
    public function moveMessages($messageset, $to_mailboxname) {
        
        $result = $this->rcube_imap_generic->move($messageset, $this->mailboxname, $to_mailboxname);
        
        if ($result == false) {
            throw new \Exception('Move messages failed.');
        }
        
        return true;
        
    }
    
    
    /**
     * Delete (expunge) messages from this mailbox
     * Throws exception on error
     *
     * @param string|array    $message_set    Comma separated list or array of message uids or message ranges
     *
     * @return bool true on success
     *
     */
    
    public function deleteMessages($messageset) {
        
        $this->setFlag(array("DELETED"), $messageset);
        $result = $this->rcube_imap_generic->expunge($this->mailboxname);
        
        if ($result == false) {
            throw new \Exception('Deleting messages failed.');
        }
        
        return true;
        
    }
    
    
    /**
     * Counts all messages in this mailbox
     * Throws exception on error
     *
     * @return int number of messages
     *
     */
    
    public function countMessages() {
        
        $result = $this->rcube_imap_generic->countMessages($this->mailboxname);
        
        if ($result === false) {
            throw new \Exception('Counting messages failed.');
        }
        
        return $result;
        
    }
    
    
    /**
     * Counts recent messages in this mailbox
     * Throws exception on error
     *
     * @return int number of messages
     *
     */
    
    public function countRecent() {
        
        $result = $this->rcube_imap_generic->countRecent($this->mailboxname);
        
        if ($result === false) {
            throw new \Exception('Counting messages failed.');
        }
        
        return $result;
        
    }
    
    
    /**
     * Counts unseen messages in this mailbox
     * Throws exception on error
     *
     * @return int number of messages
     *
     */
    
    public function countUnseen() {
        
        $result = $this->rcube_imap_generic->countUnseen($this->mailboxname);
        
        if ($result === false) {
            throw new \Exception('Counting messages failed.');
        }
        
        return $result;
        
    }
    
    
    /**
     * Counts messages in this mailbox
     * Throws exception on error
     *
     * @return int number of messages
     *
     */
    
    public function count($flag) {

        $result = $this->rcube_imap_generic->search($this->mailboxname, $flag, false, ['COUNT']);
        
        if ($result === false) {
            throw new \Exception('Counting messages failed.');
        }
        
        return $result;
        
    }
    
    /**
     * Appends message from mime mailstring to the mailbox
     * Throws exception on error
     * 
     * @param string $mailstring  mime mailstring that should be appended
     * @param array $flags array of flags that should be set to the message
     *
     * @return int uid of message
     */
    
    public function appendMessage($mailstring, $flags = []) {
        
        $result =  $this->rcube_imap_generic->append($this->mailboxname, $mailstring, $flags);
     
        if ($result === false) {
            throw new \Exception('Appending message to mailbox failed.');
        }
        
        return $result;
        
    }
        
}