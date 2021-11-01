<?php

namespace bjc\roundcubeimap;

class message {
    
    protected $rcube_imap_generic;
    protected $rcube_message_header;
    protected $messageheaders;
    protected $mailboxname;
       
    public function __construct(\rcube_imap_generic $rcube_imap_generic, \rcube_message_header $rcube_message_header, $mailboxname) {
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->rcube_message_header = $rcube_message_header;
        $this->mailboxname = $mailboxname;
        
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
        
        return $this->messageheaders->getAll();
        
    }

    public function getBodystructure() {
        
        // if bodystructure is not available in rcube_message_header get it from the imap server
        
        if (empty($this->rcube_message_header->bodystructure)) {
            $this->rcube_message_header->bodystructure = $this->rcube_imap_generic->getStructure($this->mailboxname, $this->rcube_message_header->uid, true);
        }
        
        return $this->rcube_message_header->bodystructure;
        
    }
    
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
    
    
    public function getAllparts() {
        
        $bodystructure = $this->getBodystructure();
        
        $bodyparts = array();
        
        foreach ($bodystructure as $partno => $partitem) {
            $bodyparts[] = $this->getPart($partno);
        }

        return $bodyparts;
        
    }
    
    
    
    
    /**
     * Build message part object
     *
     * @param array  $part
     * @param int    $count
     * @param string $parent
     */
    public function structure_part($part, $count = 0, $parent = '', $mime_headers = null)
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
