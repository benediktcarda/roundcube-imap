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
            
        $o_part = new rcube_message_part;
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
                $body = rcube_charset::convert($body, $o_part->charset);
            }
        }
        
        return $body;
    }
    
    
}