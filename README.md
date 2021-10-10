# roundcube-imap

A PHP IMAP library to read and process e-mails over IMAP protocol. It works independently of PHP IMAP extension and supports newer additions to the IMAP protocol like CONDSTORE and QRESYNC which is not supported by the standard PHP IMAP extension.

The code is nearly entirely extracted from the Roundcubemail project.

## Installation

The recommended way to install this package is through [Composer](https://getcomposer.org). Please note that this package is only available in Composer 2. If you still run Composer 1 you will have to update to Composer 2.

```bash
$ composer require bjc/roundcube-imap
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

## Usage

### Connect and Authenticate

```php

// $ssl_mode is optional. It can take values "tls", "ssl" and "plain". Default value is "tls".
$server = new \bjc\roundcubeimap\server($host, $ssl_mode);

// $connection is an object of \bjc\roundcubeimap\connection.
$connection = $server->authenticate($username, $password);
```


### Mailboxes

Retrieve mailboxes (also known as mail folders) from the mail server and iterate over them:

```php
$mailboxes = $connection->getMailboxes();

foreach ($mailboxes as $mailbox) {

  // $mailbox is an object of \bjc\roundcubeimap\mailbox

}

```

Or retrieve a specific mailbox:

```php
$mailbox = $connection->getMailbox('INBOX');
```

Delete a mailbox:

```php
$connection->deleteMailbox($mailbox);
```

#### Status of a mailbox

To retrieve the status of a mailbox run:

```php
$status = $mailbox->getStatus();
// $status is a stdClass object that contains the values uidnext, uidvalidity, recent and if available highestmodseq
```

### Messages

For retrieving messages it is important to understand the concept of UIDs and uidvalidity. UID is a numeric unique identifier of a message WITHIN the mailbox (not to confuse with the message ID which is an alphanumeric identifier that identifies a message "world wide"). When a new message is being put into a mailbox, it will always get a higher UID than all the messages before. As a result, when your application knows which UID it fetched last, it can easily ask the IMAP server to just send the messages that were newly received. This concept works as long as the UIDs of messages don't change and new messages always get higher UIDs than existing ones. There are scenarios were this is not the case and this is the reason for uidvalidity. Uidvalidity is a parameter maintained by the IMAP server for each mailbox. As long as this parameter stays the same, the UIDs assigned to the existing mails did not change and new messages always received higher UIDs than the ones that existed.

Therefore your application has to store two parameters for each mailbox:
- uidvalidity
- last fetched uid

If you check the mailbox, check first whether uidvalidity changed compared to which uidvalidity you have stored in your application for the respective mailbox. If it changed, delete the stored email data in your application (for this mailbox) and fetch everything again as UIDs for messages could have changed. If uidvalidity stayed the same, then just fetch the new messages higher than your last fetched uid.

To get the current uidvalidity value run:

```php
$status = $mailbox->getStatus();
$uidvalidity = $status->uidvalidity;
```


#### Get messages higher than a certain UID (normally the one you fetched at last)

```php
$messagearray = $mailbox->getMessageshigherthan($lastfetcheduid);

foreach ($messagearray as $message) {
  // $message is an object of \bjc\roundcubeimap\message
}

```

#### Get messages of a certain message set (e.g. if you want to fetch all messages again because uidvalidity changed)

```php

$message_set = '1:*';

$messagearray = $mailbox->getMessageSequence($message_set);

foreach ($messagearray as $message) {
  // $message is an object of \bjc\roundcubeimap\message
}

```


#### If your server supports CONDSTORE and QRESYNC extension then you can synchronize your data

'Synchronize' means you can get just the messages that changed, came in new or got deleted since your last fetch.
For this you have to store another parameter (beside uidvalidity) for each mailbox in your persistence (e.g. your database):
Highestmodsequence

Highestmodsequence is a numeric value that increases each time something changed in the mailbox. The IMAP server keeps track of which changes happened at which modsequence and therefore can tell which changes it needs to tell you when you ask for changes since a certain modsequence.




#### If your server does not support CONDSTORE and QRESYNC

If your server does not support CONDSTORE and QRESYNC then you have to fetch at least the flags and UIDs of all messages in the mailbox (from time to time or always) to keep track of flag changes and deleted messages




