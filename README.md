PHPSYSLOGNET
============

PHP syslog clinet that conforms to the [RFC 5424](https://tools.ietf.org/html/rfc5424) syslog standard and supports UDP
and TCP transports as well as UNIX domain sockets.

USAGE
=====

Simple way:  

```php
use SyslogNet\SyslogNet;
use SyslogNet\Severity;
use SyslogNet\Facility;
use SyslogNet\Formatters\FormatterRFC5424;
use SyslogNet\Transports\Socket;

$syslog = new SyslogNet(
    Socket::createUDP('127.0.0.1', 514),
    new FormatterRFC5424(),
    Facility::DAEMON,
    'myService'
);

$syslog->send(Severity::CRIT, 'My test message');
```

Advanced way:  

```php
use SyslogNet\SyslogNet;
use SyslogNet\Severity;
use SyslogNet\Transports\Socket;
use SyslogNet\StructuredDataElement;

$sys = new SyslogNet(Socket::createUDP('127.0.0.1', 514));

$message = $sys->createMessage(Severity::CRIT, 'My test message');
$message->setAppName('myService');
$message->setHostName('myHostName');
$message->setMsgId("m1");

$sd1 = new StructuredDataElement('id1');
$sd1['key1'] = 'value1';
$sd1['key2'] = 'value2';

$sd2 = new StructuredDataElement('id2');
$sd2['key1'] = 'value1';
$sd2['key2'] = 'value2';

$message->addSDElement($sd1);
$message->addSDElement($sd2);

$sys->sendMessage($message);
```
