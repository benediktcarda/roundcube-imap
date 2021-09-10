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

// $ssl_mode is optional. It can take values "tls", "ssl" and "plain"
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

