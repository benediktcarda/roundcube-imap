<?php

namespace bjc\roundcubeimap;

class attachment {
    
    protected $rcube_imap_generic;
    protected $mailboxname;
    protected $uid;
    protected $rcube_message_part;
    protected $filename;
    protected $default_charset = 'ISO-8859-1';
    
    public function __construct(\bjc\roundcubeimap\imap_generic $rcube_imap_generic, $mailboxname, $uid, \rcube_message_part $rcube_message_part) {
                
        $this->rcube_imap_generic = $rcube_imap_generic;
        $this->mailboxname = $mailboxname;
        $this->uid = $uid;
        $this->rcube_message_part = $rcube_message_part;
        $this->filename = $rcube_message_part->filename;
        
    }

    public function getFilename() {
        return $this->filename;
    }
    
    public function getCharset() {
        return $this->rcube_message_part->charset;
    }
    
    public function getContentId() {
        return $this->rcube_message_part->content_id;
    }
    
    public function getMimeId() {
        return $this->rcube_message_part->mime_id;
    }
    
    public function getData() {

        $o_part = $this->rcube_message_part;
                
        $body = '';
        
        // Note: multipart/* parts will have size=0, we don't want to ignore them
        if ($o_part && ($o_part->size || $o_part->ctype_primary == 'multipart')) {
            $formatted = $formatted && $o_part->ctype_primary == 'text';
            $body = $this->rcube_imap_generic->handlePartBody($this->mailboxname, $this->uid, true, $o_part->mime_id ? $o_part->mime_id : 'TEXT', $o_part->encoding, null, null, $formatted);
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
    
    
}
