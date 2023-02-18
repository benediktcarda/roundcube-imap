<?php

namespace bjc\roundcubeimap;

class message {
    
    protected $rcube_imap_generic;
    protected $rcube_message_header;
    protected $messageheaders;
    protected $mailboxname;
    protected $uid;
    protected $default_charset = 'ISO-8859-1';
    protected $attachments = array();
    protected $embeddedmessages = array();
    protected $inlineobjects = array();
    protected $bodystructure_evaluated = false;
    protected $textplain;
    protected $texthtml;
       
    public function __construct(\bjc\roundcubeimap\imap_generic $rcube_imap_generic, \rcube_message_header $rcube_message_header, $mailboxname) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->rcube_message_header = $rcube_message_header;
        $this->mailboxname = $mailboxname;
        $this->uid = $this->rcube_message_header->uid;
        
        $this->messageheaders = new \bjc\roundcubeimap\messageheaders($rcube_message_header);
        
    }

    
    /**
     * Get UID of message
     *
     * @return int UID of message
     *
     */
    
    public function getUID() {
        return $this->uid;
    }

    
    /**
     * Get alphanumeric id of message
     *
     * @return string ID of message
     *
     */
    
    public function getID() {
        $returnvalue = $this->rcube_message_header->get('message-id');
        
        return $returnvalue;
    }

    
    /**
     * Get message date
     *
     * @return obj A datetime object
     *
     */
    
    public function getDate() {
        
        $returnvalue = $this->messageheaders->get('date');
        
        return $returnvalue;
    }

    
    /**
     * Get timestamp of message
     *
     * @return int Timestamp of message
     *
     */
    
    public function getTimestamp() {
        
        $timestamp = $this->rcube_message_header->get("timestamp");

        return $timestamp;
    }

    
    /**
     * Get subject of message
     *
     * @return string subject of message
     *
     */
    
    public function getSubject() {
        $returnvalue = $this->messageheaders->get("subject");

        return $returnvalue;
    }

    
    /**
     * Get sender of message
     *
     * @return obj object of \bjc\roundcubeimap\emailaddress
     *
     */
    
    public function getFrom() {
        $returnvalue = $this->messageheaders->get('from');
        
        return $returnvalue;        
    }

    
    /**
     * Get recipients of message
     *
     * @return array array of objects of \bjc\roundcubeimap\emailaddress
     *
     */
    
    public function getTo() {
        $returnvalue = $this->messageheaders->get('to');
        
        return $returnvalue;        
    }

    
    /**
     * Get cc recipients of message
     *
     * @return array array of objects of \bjc\roundcubeimap\emailaddress
     *
     */
    
    public function getCC() {
        $returnvalue = $this->messageheaders->get('cc');
        
        return $returnvalue;
    }
    
    
    /**
     * Get Answered flag of message
     *
     * @return bool Returns true if answered flag is set otherwise false
     *
     */
    
    public function isAnswered() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["ANSWERED"])) {
            return true;
        } else {
            return false;
        }
        
    }

    
    /**
     * Get Deleted flag of message
     *
     * @return bool Returns true if deleted flag is set otherwise false
     *
     */
    
    public function isDeleted() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["DELETED"])) {
            return true;
        } else {
            return false;
        }
        
    }

    
    /**
     * Get Draft flag of message
     *
     * @return bool Returns true if draft flag is set otherwise false
     *
     */
    
    public function isDraft() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["DRAFT"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    
    /**
     * Get Seen flag of message
     *
     * @return bool Returns true if seen flag is set otherwise false
     *
     */
    
    public function isSeen() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["SEEN"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    
    /**
     * Get Flagged flag of message
     *
     * @return bool Returns true if flagged flag is set otherwise false
     *
     */    
    
    public function isFlagged() {
        
        $flags = $this->rcube_message_header->flags;
        
        if (isset($flags["FLAGGED"])) {
            return true;
        } else {
            return false;
        }
        
    }
    
    
    /**
     * Get Flags of message in an array
     *
     * @return array Returns an array of set flags
     *
     */
    
    public function getFlags() {
        
        $flags = $this->rcube_message_header->flags;
        
        return $flags;
        
    }
    
    
    /**
     * Get specific header field of message
     * 
     * @param string Name of header
     *
     * @return string Value of header
     *
     */
    
    public function getHeader($field) {
        
        $returnvalue = $this->rcube_message_header->get($field);

        if (is_array($returnvalue)) {
            $returnvalue = (object) $returnvalue;
        } 
        
        return $returnvalue;
        
    }

    
    /**
     * Get all headers of message
     *
     * @return array Array of key value pairs all header fields
     *
     */
    
    public function getHeaders() {
        
        return $this->messageheaders->getAll();
        
    }


    
    /**
     * Get body part of message
     * 
     * @param $part  Body part number (mime_id) that should be retrieved
     *
     * @return string Bodypart value
     *
     */
    
    public function getPart($part = 1) {
        
        // get part data
        $structure = $this->getBodystructure();
        $part_data = $this->rcube_imap_generic->getStructurePartData($structure, $part);
            
        $o_part = new \rcube_message_part;
        $o_part->ctype_primary   = $part_data['type'];
        $o_part->ctype_secondary = $part_data['subtype'];
        $o_part->encoding        = $part_data['encoding'];
        $o_part->charset         = $part_data['charset'];
        $o_part->size            = $part_data['size'];
        
        $body = '';
        
        // Note: multipart/* parts will have size=0, we don't want to ignore them
        if ($o_part && ($o_part->size || $o_part->ctype_primary == 'multipart')) {
            $formatted = $formatted && $o_part->ctype_primary == 'text';
            $body = $this->rcube_imap_generic->handlePartBody($this->mailboxname, $this->rcube_message_header->uid, true, $part ? $part : 'TEXT', $o_part->encoding, null, null, $formatted);
        }
        
        // convert charset (if text or message part)
        if ($body && preg_match('/^(text|message)$/', $o_part->ctype_primary)) {
            // Remove NULL characters if any (#1486189)
            if ($formatted && strpos($body, "\x00") !== false) {
                $body = str_replace("\x00", '', $body);
            }
            
            if (!$skip_charset_conv) {
                if (!$o_part->charset || strtoupper($o_part->charset) == 'US-ASCII') {
                    // try to extract charset information from HTML meta tag (#1488125)
                    if ($o_part->ctype_secondary == 'html' && preg_match('/<meta[^>]+charset=([a-z0-9-_]+)/i', $body, $m)) {
                        $o_part->charset = strtoupper($m[1]);
                    }
                    else {
                        $o_part->charset = $this->default_charset;
                    }
                }
                $body = \rcube_charset::convert($body, $o_part->charset, 'UTF-8');
            }
        }
        
        return $body;
    }
    
    
    /**
     * Get html bodypart of message if available
     *
     * @return string Bodypart value
     *
     */
    
    public function getBodyHtml() {

        $this->evaluateBodystructure();
        
        return $this->texthtml;
        
    }
    
    
    /**
     * Get plain text bodypart of message if available
     *
     * @return string Bodypart value
     *
     */
    
    public function getBodyText() {
        
        $this->evaluateBodystructure();
        
        return $this->textplain;
    }
    
    
    /**
     * Get Attachments of message
     *
     * @return array of objects of \bjc\roundcubeimap\attachment
     *
     */
    
    public function getAttachments() {
        
        $this->evaluateBodystructure();
        
        return $this->attachments;
        
    }
 
    /**
     * Get Attachment of message by mime_id (this is not content id)
     *
     * @param $part  Body part number (mime_id) that should be retrieved
     * @return object of \bjc\roundcubeimap\attachment
     *
     */
    
    public function getAttachment($part) {
        
        $this->evaluateBodystructure();
        
        return $this->attachments[$part];
        
    }
    
    
    /**
     * NOT IMPLEMENTED YET
     *
     */
    
    public function getEmbeddedmessages() {

        $this->evaluateBodystructure();
        
        return $this->embeddedmessages;
        
    }
    
    
    /**
     * Get Inline objects of message
     *
     * @return array of objects of \bjc\roundcubeimap\attachment
     *
     */
    
    public function getInlineobjects() {
        
        $this->evaluateBodystructure();
        
        return $this->inlineobjects;
        
    }
    
    
    /**
     * Copies message from the current mailbox to another mailbox of the same account
     * Throws exception on error
     *
     * @param string          $to_mailboxname Mailbox the messages should be copied to
     *
     * @return bool true on success
     *
     */
    
    public function copyMessage($to_mailboxname) {

        $result = $this->rcube_imap_generic->copy($this->uid, $this->mailboxname, $to_mailboxname);
        
        if ($result == false) {
            throw new \Exception('Copying message failed.');
        }
        
        return true;
        
    }

    
    /**
     * Move message from this mailbox to another mailbox of the same account
     * Throws exception on error
     *
     * @param string          $to_mailboxname Mailbox the messages should be copied to
     *
     * @return bool true on success
     *
     */
    
    public function moveMessage($to_mailboxname) {

        $result = $this->rcube_imap_generic->move($this->uid, $this->mailboxname, $to_mailboxname);
        
        if ($result == false) {
            throw new \Exception('Moving message failed.');
        }
        
        return true;
        
    }
    
    
    /**
     * Delete (expunge) this message from mailbox
     * Throws exception on error
     *
     * @return bool true on success
     *
     */
    
    public function deleteMessage() {
        
        $this->setFlag(array("DELETED"));
        $result = $this->rcube_imap_generic->expunge($this->mailboxname);
        
        if ($result == false) {
            throw new \Exception('Deleting message failed.');
        }
        
        return true;
        
    }
    
    
    /**
     * Sets flags to this message
     * Throws exception on error
     *
     * @param array  $flags             Array of strings with flags that should be set
     *
     * @return bool true on success
     */
    
    public function setFlag(array $flags) {
        
        foreach ($flags as $flag) {
            $result = $this->rcube_imap_generic->flag($this->mailboxname, $this->uid, $flag);
        }
        
        if ($result == false) {
            throw new \Exception('Setting flags failed.');
        }
        
        return true;
        
    }
    
    
    /**
     * Unsets flags to this message
     * Throws exception on error
     *
     * @param array  $flags             Array of strings with flags that should be set
     *
     * @return bool true on success
     *
     */
    
    public function clearFlag(array $flags) {
        
        foreach ($flags as $flag) {
            $result = $this->rcube_imap_generic->unflag($this->mailboxname, $this->uid, $flag);
        }
        
        if ($result == false) {
            throw new \Exception('Unsetting flags failed.');
        }
        
        return true;
        
    }

     /**
     * Attempt to get a raw version of the whole email - NOT WORKING YET
     */

     public function getRawmessage() {

        $data = $this->rcube_imap_generic->fetchRawmessage($this->mailboxname, $this->uid);

        return $data;

    }

    
    /**
     * Evaluates bodystructure and saves results in this object
     */
    
    protected function evaluateBodystructure() {
        
        if ($this->bodystructure_evaluated == false) {
            $bodystructure = $this->getBodystructure();
            $rcube_message_part = $this->structure_part($bodystructure);
            
            $parts = array($rcube_message_part);
            
            $this->iteratethroughParts($parts, false);

            $this->bodystructure_evaluated = true;
            
        }
        
    }
    
    
    /**
     * Iterates through bodyparts and saves the body parts in this object
     *
     * @param array $parts Array of rcube_message_part objects
     *
     */
    
    protected function iteratethroughParts(array $parts, bool $childofembeddedmessage) {
        
        foreach ($parts as $rcube_message_part) {
            $mime_id = $rcube_message_part->mime_id;
            $subparts = $rcube_message_part->parts;
            $disposition = $rcube_message_part->disposition;
            $filename = $rcube_message_part->filename;
            $ctype_primary = $rcube_message_part->ctype_primary;
            $ctype_secondary = $rcube_message_part->ctype_secondary;
            
            if ($ctype_primary == 'message') {
                $childofembeddedmessage = true;
            }
            
            if (!empty($subparts) AND is_array($subparts)) {
                $this->iteratethroughParts($subparts, $childofembeddedmessage);
            }
            
            if ($disposition == 'attachment' AND !empty($filename) AND $ctype_primary != 'message') {
                $this->attachments[$mime_id] = new \bjc\roundcubeimap\attachment($this->rcube_imap_generic, $this->mailboxname, $this->uid, $rcube_message_part);
            }
            
            if (empty($this->textplain) AND $ctype_primary == 'text' AND $ctype_secondary == 'plain' AND $childofembeddedmessage == false) {
                $this->textplain = $this->getPart($mime_id);
            }

            if (empty($this->texthtml) AND $ctype_primary == 'text' AND $ctype_secondary == 'html' AND $childofembeddedmessage == false) {
                $this->texthtml = $this->getPart($mime_id);
            }
            
            if ($disposition == 'inline' AND !empty($filename) AND $ctype_primary != 'message' AND $childofembeddedmessage == false) {
                $this->inlineobjects[$mime_id] = new \bjc\roundcubeimap\attachment($this->rcube_imap_generic, $this->mailboxname, $this->uid, $rcube_message_part);
            }
            
            // Klasse embeddedmessage funktioniert noch nicht. Die Frage ist, wie kann man der Klasse den Message-Header rcube_message_header liefern
            
            // if ($disposition == 'attachment' AND $ctype_primary == 'message') {
            //    $this->embeddedmessages[] = new \bjc\roundcubeimap\embeddedmessage($this->rcube_imap_generic, $mailboxname, $uid, $rcube_message_part, $filename);
            // }
            
        }
        
    }
    
    
    /**
     * Returns bodystructure of message
     * If not fetched yet, bodystructure is fetched from the server
     *
     * @return bodystructure of message
     *
     */
    
    protected function getBodystructure() {
        
        // if bodystructure is not available in rcube_message_header get it from the imap server
        
        if (empty($this->rcube_message_header->bodystructure)) {
            $this->rcube_message_header->bodystructure = $this->rcube_imap_generic->getStructure($this->mailboxname, $this->rcube_message_header->uid, true);
        }
        
        return $this->rcube_message_header->bodystructure;
        
    }
    
    
    /**
     * Build message part object
     *
     * @param array  $part  An array of the bodystructure that should be analysed, e.g. the result of method getBodystructure()
     * @param int    $count
     * @param string $parent
     */
    
    protected function structure_part($part, $count = 0, $parent = '', $mime_headers = null)
    {
        $struct = new \rcube_message_part;
        $struct->mime_id = empty($parent) ? (string)$count : "$parent.$count";
        
        // multipart
        if (is_array($part[0])) {
            $struct->ctype_primary = 'multipart';
            
            /* RFC3501: BODYSTRUCTURE fields of multipart part
             part1 array
             part2 array
             part3 array
             ....
             1. subtype
             2. parameters (optional)
             3. description (optional)
             4. language (optional)
             5. location (optional)
             */
            
            // find first non-array entry
            for ($i=1; $i<count($part); $i++) {
                if (!is_array($part[$i])) {
                    $struct->ctype_secondary = strtolower($part[$i]);
                    
                    // read content type parameters
                    if (is_array($part[$i+1])) {
                        $struct->ctype_parameters = [];
                        for ($j=0; $j<count($part[$i+1]); $j+=2) {
                            $param = strtolower($part[$i+1][$j]);
                            $struct->ctype_parameters[$param] = $part[$i+1][$j+1];
                        }
                    }
                    
                    break;
                }
            }
            
            $struct->mimetype = 'multipart/'.$struct->ctype_secondary;
            
            // build parts list for headers pre-fetching
            for ($i=0; $i<count($part); $i++) {
                // fetch message headers if message/rfc822 or named part
                if (is_array($part[$i]) && !is_array($part[$i][0])) {
                    $tmp_part_id = $struct->mime_id ? $struct->mime_id.'.'.($i+1) : $i+1;
                    if (strtolower($part[$i][0]) == 'message' && strtolower($part[$i][1]) == 'rfc822') {
                        $mime_part_headers[] = $tmp_part_id;
                    }
                    else if (!empty($part[$i][2]) && empty($part[$i][3])) {
                        $params = array_map('strtolower', (array) $part[$i][2]);
                        $find   = ['name', 'filename', 'name*', 'filename*', 'name*0', 'filename*0', 'name*0*', 'filename*0*'];
                        
                        // In case of malformed header check disposition. E.g. some servers for
                        // "Content-Type: PDF; name=test.pdf" may return text/plain and ignore name argument
                        if (count(array_intersect($params, $find)) > 0
                            || (isset($part[$i][9]) && is_array($part[$i][9]) && stripos($part[$i][9][0], 'attachment') === 0)
                            ) {
                                $mime_part_headers[] = $tmp_part_id;
                            }
                    }
                }
            }
            
            // pre-fetch headers of all parts (in one command for better performance)
            // @TODO: we could do this before _structure_part() call, to fetch
            // headers for parts on all levels
            if (!empty($mime_part_headers)) {
                $mime_part_headers = $this->rcube_imap_generic->fetchMIMEHeaders($this->mailboxname, $this->uid, $mime_part_headers);
            }
            
            $struct->parts = [];
            for ($i=0, $count=0; $i<count($part); $i++) {
                if (!is_array($part[$i])) {
                    break;
                }
                $tmp_part_id = $struct->mime_id ? $struct->mime_id.'.'.($i+1) : $i+1;
                $struct->parts[] = $this->structure_part($part[$i], ++$count, $struct->mime_id,
                    !empty($mime_part_headers[$tmp_part_id]) ? $mime_part_headers[$tmp_part_id] : null);
            }
            
            return $struct;
        }
        
        /* RFC3501: BODYSTRUCTURE fields of non-multipart part
         0. type
         1. subtype
         2. parameters
         3. id
         4. description
         5. encoding
         6. size
         -- text
         7. lines
         -- message/rfc822
         7. envelope structure
         8. body structure
         9. lines
         --
         x. md5 (optional)
         x. disposition (optional)
         x. language (optional)
         x. location (optional)
         */
        
        // regular part
        $struct->ctype_primary   = strtolower($part[0]);
        $struct->ctype_secondary = strtolower($part[1]);
        $struct->mimetype        = $struct->ctype_primary.'/'.$struct->ctype_secondary;
        
        // read content type parameters
        if (is_array($part[2])) {
            $struct->ctype_parameters = [];
            for ($i=0; $i<count($part[2]); $i+=2) {
                $struct->ctype_parameters[strtolower($part[2][$i])] = $part[2][$i+1];
            }
            
            if (isset($struct->ctype_parameters['charset'])) {
                $struct->charset = $struct->ctype_parameters['charset'];
            }
        }
        
        // #1487700: workaround for lack of charset in malformed structure
        if (empty($struct->charset) && !empty($mime_headers) && !empty($mime_headers->charset)) {
            $struct->charset = $mime_headers->charset;
        }
        
        // read content encoding
        if (!empty($part[5])) {
            $struct->encoding = strtolower($part[5]);
            $struct->headers['content-transfer-encoding'] = $struct->encoding;
        }
        
        // get part size
        if (!empty($part[6])) {
            $struct->size = intval($part[6]);
        }
        
        // read part disposition
        $di = 8;
        if ($struct->ctype_primary == 'text') {
            $di += 1;
        }
        else if ($struct->mimetype == 'message/rfc822') {
            $di += 3;
        }
        
        if (isset($part[$di]) && is_array($part[$di]) && count($part[$di]) == 2) {
            $struct->disposition = strtolower($part[$di][0]);
            if ($struct->disposition && $struct->disposition !== 'inline' && $struct->disposition !== 'attachment') {
                // RFC2183, Section 2.8 - unrecognized type should be treated as "attachment"
                $struct->disposition = 'attachment';
            }
            if (is_array($part[$di][1])) {
                for ($n=0; $n<count($part[$di][1]); $n+=2) {
                    $struct->d_parameters[strtolower($part[$di][1][$n])] = $part[$di][1][$n+1];
                }
            }
        }
        
        // get message/rfc822's child-parts
        if (isset($part[8]) && is_array($part[8]) && $di != 8) {
            $struct->parts = [];
            for ($i=0, $count=0; $i<count($part[8]); $i++) {
                if (!is_array($part[8][$i])) {
                    break;
                }
                $struct->parts[] = $this->structure_part($part[8][$i], ++$count, $struct->mime_id);
            }
        }
        
        // get part ID
        if (!empty($part[3])) {
            $struct->content_id = $struct->headers['content-id'] = trim($part[3]);
            
            if (empty($struct->disposition)) {
                $struct->disposition = 'inline';
            }
        }
        
        // fetch message headers if message/rfc822 or named part (could contain Content-Location header)
        if (
            $struct->ctype_primary == 'message'
            || (!empty($struct->ctype_parameters['name']) && !empty($struct->content_id))
            ) {
                if (empty($mime_headers)) {
                    $mime_headers = $this->rcube_imap_generic->fetchPartHeader(
                        $this->mailboxname, $this->uid, true, $struct->mime_id);
                }
                
                if (is_string($mime_headers)) {
                    $struct->headers = \rcube_mime::parse_headers($mime_headers) + $struct->headers;
                }
                else if (is_object($mime_headers)) {
                    $struct->headers = get_object_vars($mime_headers) + $struct->headers;
                }
                
                // get real content-type of message/rfc822
                if ($struct->mimetype == 'message/rfc822') {
                    // single-part
                    if (!is_array($part[8][0])) {
                        $struct->real_mimetype = strtolower($part[8][0] . '/' . $part[8][1]);
                    }
                    // multi-part
                    else {
                        for ($n=0; $n<count($part[8]); $n++) {
                            if (!is_array($part[8][$n])) {
                                break;
                            }
                        }
                        $struct->real_mimetype = 'multipart/' . strtolower($part[8][$n]);
                    }
                }
                
                if ($struct->ctype_primary == 'message' && empty($struct->parts)) {
                    if (is_array($part[8]) && $di != 8) {
                        $struct->parts[] = $this->structure_part($part[8], ++$count, $struct->mime_id);
                    }
                }
            }
            
            // normalize filename property
            $this->set_part_filename($struct, $mime_headers);
            
            return $struct;
    }
    
    
    
    /**
     * Set attachment filename from message part structure
     *
     * @param rcube_message_part $part    Part object
     * @param string             $headers Part's raw headers
     */
    protected function set_part_filename(&$part, $headers = null)
    {
        // Some IMAP servers do not support RFC2231, if we have
        // part headers we'll get attachment name from them, not the BODYSTRUCTURE
        $rfc2231_params = [];
        if (!empty($headers) || !empty($part->headers)) {
            if (is_object($headers)) {
                $headers = get_object_vars($headers);
            }
            else {
                $headers = !empty($headers) ? \rcube_mime::parse_headers($headers) : $part->headers;
            }
            
            $ctype       = $headers['content-type'] ?? '';
            $disposition = $headers['content-disposition'] ?? '';
            $tokens      = preg_split('/;[\s\r\n\t]*/',  $ctype. ';' . $disposition);
            
            foreach ($tokens as $token) {
                // TODO: Use order defined by the parameter name not order of occurrence in the header
                if (preg_match('/^(name|filename)\*([0-9]*)\*?="*([^"]+)"*/i', $token, $matches)) {
                    $key = strtolower($matches[1]);
                    $rfc2231_params[$key] = ($rfc2231_params[$key] ?? '') . $matches[3];
                }
            }
        }
        
        if (isset($rfc2231_params['name'])) {
            $filename_encoded = $rfc2231_params['name'];
        }
        else if (isset($rfc2231_params['filename'])) {
            $filename_encoded = $rfc2231_params['filename'];
        }
        else if (!empty($part->d_parameters['filename'])) {
            $filename_mime = $part->d_parameters['filename'];
        }
        // read 'name' after rfc2231 parameters as it may contain truncated filename (from Thunderbird)
        else if (!empty($part->ctype_parameters['name'])) {
            $filename_mime = $part->ctype_parameters['name'];
        }
        // Content-Disposition
        else if (!empty($part->headers['content-description'])) {
            $filename_mime = $part->headers['content-description'];
        }
        else {
            return;
        }
        
        // decode filename
        if (isset($filename_mime)) {
            if (!empty($part->charset)) {
                $charset = $part->charset;
            }
            else if (!empty($this->struct_charset)) {
                $charset = $this->struct_charset;
            }
            else {
                $charset = \rcube_charset::detect($filename_mime, $this->default_charset);
            }
            
            $part->filename = \rcube_mime::decode_mime_string($filename_mime, $charset);
        }
        else if (isset($filename_encoded)) {
            // decode filename according to RFC 2231, Section 4
            if (preg_match("/^([^']*)'[^']*'(.*)$/", $filename_encoded, $fmatches)) {
                $filename_charset = $fmatches[1];
                $filename_encoded = $fmatches[2];
            }
            
            $part->filename = rawurldecode($filename_encoded);
            
            if (!empty($filename_charset)) {
                $part->filename = \rcube_charset::convert($part->filename, $filename_charset);
            }
        }
        
        // Workaround for invalid Content-Type (#6816)
        // Some servers for "Content-Type: PDF; name=test.pdf" may return text/plain and ignore name argument
        if ($part->mimetype == 'text/plain' && !empty($headers['content-type'])) {
            $tokens = preg_split('/;[\s\r\n\t]*/', $headers['content-type']);
            $type   = \rcube_mime::fix_mimetype($tokens[0]);
            
            if ($type != $part->mimetype) {
                $part->mimetype = $type;
                list($part->ctype_primary, $part->ctype_secondary) = explode('/', $part->mimetype);
            }
        }
    }
    
    
}
