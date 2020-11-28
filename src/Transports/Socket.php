<?php
namespace SyslogNet\Transports;

use SyslogNet\Transports\Exceptions\MessageSizeExceeded;

/**
 * @author Dobriakov A.
 * @copyright Copyright (c) 2020 Dobriakov Anton
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
class Socket implements SenderInterface
{
    // Desired the maximum message size for
    // the datagram sockets in octets.
    const DESIRED_DGRAM_MSG_SIZE = 480;

    /**
     * @var resource
     */
    protected $sock = null;

    /**
     * @var int
     */
    protected $sockType = false;

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var int
     */
    protected $port = 0;

    /**
     * @var string
     */
    protected $unixFile = '';

    /**
     * @var int
     */
    protected $maxMsgSize = self::DESIRED_DGRAM_MSG_SIZE;

    /**
     * @param resource $sock
     * @return void
     */
    protected function __construct($sock)
    {
        $this->sock = $sock;
        $this->sockType = \socket_get_option($sock, \SOL_SOCKET, \SO_TYPE);

        if ($this->sockType === false) {
            throw new \RuntimeException('Failed to determine the socket type.');
        }
    }

    /**
     * @param string $host
     * @param string $port
     * @throws \RuntimeException if an error occurred during
     * the socket creation.
     * @return \SyslogNet\Transports\Socket
     */
    public static function createTCP($host, $port)
    {
        $sock = new static(
            static::createSocket(\AF_INET, \SOCK_STREAM, \SOL_TCP)
        );

        $sock->connect($host, $port);
        return $sock;
    }

    /**
     * @param string $host
     * @param string $port
     * @param int $maxMsgSize The maximum size of the message in octets
     * that the transport will accept. You recommended to use the default one
     * to avoid the fragmentations. Increase this value only if you sure that
     * your network has appropriate MTU size.
     * Null indicates the default value = 480.
     * @throws \RuntimeException if an error occurred during
     * the socket creation.
     * @return \SyslogNet\Transports\Socket
     */
    public static function createUDP(
        $host,
        $port,
        $maxMsgSize = null
    ) {
        $sock = new static(
            static::createSocket(\AF_INET, \SOCK_DGRAM, \SOL_UDP)
        );

        if ($maxMsgSize !== null) {
            $sock->maxMsgSize = \abs((int) $maxMsgSize);
        }

        $sock->connect($host, $port);
        return $sock;
    }

    /**
     * @param string $file
     * @throws \RuntimeException if an error occurred during
     * the socket creation.
     * @return \SyslogNet\Transports\Socket
     */
    public static function createUnix($file)
    {
        $sock = new static(
            static::createSocket(\AF_UNIX, \SOCK_STREAM)
        );

        $sock->connect($file);
        return $sock;
    }

    /**
     * @param string $file
     * @throws \RuntimeException if an error occurred during
     * the socket creation.
     * @return \SyslogNet\Transports\Socket
     */
    public static function createUnixDGRAM($file)
    {
        $sock = new static(
            static::createSocket(\AF_UNIX, \SOCK_DGRAM)
        );

        $sock->connect($file);
        $sock->maxMsgSize = 2048;
        return $sock;
    }

    /**
     * Creates a new socket.
     *
     * @param int $domain @see `\socket_create`
     * @param int $type   @see `\socket_create`
     * @param int $proto  @see `\socket_create`
     * @throws \RuntimeException if an error occurred during
     * the socket creation.
     * @return resource
     */
    protected static function createSocket($domain, $type, $proto = 0)
    {
        $sock = \socket_create($domain, $type, $proto);
        if ($sock === false) {
            throw new \RuntimeException('Failed to create a socket.');
        }

        return $sock;
    }

    /**
     * Tries to connect to the given endpoint.
     *
     * @param string $hostOrUnixFile
     * @param int $port [optional]
     * @throws \RuntimeException if the connection failed.
     * @return void
     */
    public function connect(...$args)
    {
        $sz = sizeof($args);
        if ($sz == 2) {
            $this->host = $args[0];
            $this->port = $args[1];
        } elseif ($sz == 1) {
            $this->unixFile = $args[0];
        } else {
            throw new \InvalidArgumentException(
                'Connection failed. Invalid number of arguments.'
            );
        }

        if ($this->sockType !== \SOCK_STREAM) {
            return;
        }

        \array_unshift($args, $this->sock);
        if (!\socket_connect(...$args)) {
            throw new \RuntimeException(
                'Failed to connect to the given endpoint!'
            );
        }
    }

    /**
     * @param string $msg
     * @throws \UnexpectedValueException if the socket's type is
     * not supported.
     * @throws \SyslogNet\Transports\Exceptions\MessageSizeExceeded
     * @return bool
     */
    public function send($msg)
    {
        if ($this->sockType == \SOCK_STREAM) {
            return $this->writeStream($msg);
        } elseif ($this->sockType == \SOCK_DGRAM) {
            return $this->writeDatagram($msg);
        } else {
            throw new \UnexpectedValueException(
                'The socket type ' . $this->sockType . ' is not supported.'
            );
        }
    }

    /**
     * Writes the given buffer $buf
     * to the socket's stream.
     *
     * @param string $buf
     * @return bool
     */
    protected function writeStream($buf)
    {
        $bufLen = \strlen($buf);
        $r = false;
        while (true) {
            $r = \socket_write($this->sock, $buf, $bufLen);
            if ($r === false) {
                return false;
            }

            if ($r == $bufLen) {
                break;
            }

            $buf = \substr($buf, $r);
            $bufLen -= $r;
        }

        return true;
    }

    /**
     * Writes the given message $msg
     * to the socket as a datagram.
     *
     * @param string $msg
     * @throws \SyslogNet\Transports\Exceptions\MessageSizeExceeded
     * if the given message has the size that exceeds the configured one.
     * @return void
     */
    protected function writeDatagram($msg)
    {
        if (\strlen($msg) > $this->maxMsgSize) {
            throw new MessageSizeExceeded(
                'The message size is exceeded for the datagram socket!'
            );
        }

        $args = [
            $this->sock,
            $msg,
            \strlen($msg),
            0
        ];

        if (!empty($this->unixFile)) {
            $args[] = $this->unixFile;
        } else {
            $args[] = $this->host;
            $args[] = $this->port;
        }

        return \socket_sendto(...$args);
    }
}
