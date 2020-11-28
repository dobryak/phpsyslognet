<?php
namespace SyslogNet;

use SyslogNet\Transports\SenderInterface;
use SyslogNet\Formatters\FormatterInterface;

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
class SyslogNet
{
    /**
     * @var string
     */
    protected $appName = '';

    /**
     * @var string
     */
    protected $hostName = '';

    /**
     * @var \SyslogNet\Transports\SenderInterface
     */
    protected $sender = '';

    /**
     * @var \SyslogNet\Formatters\FormatterInterface
     */
    protected $formatter = '';

    /**
     * @var int
     * @see `openlog` `$facility` parameter.
     */
    protected $facility = Facility::LOCAL_0;

    /**
     * @param SenderInterface $sender
     * @param FormatterInterface $formatter [optional]
     * @param int $facility [optional]
     * @param string $appName [optional]
     * @return void
     */
    public function __construct(
        SenderInterface $sender,
        FormatterInterface $formatter = null,
        $facility = Facility::LOCAL_0,
        $appName = '',
        $hostName = null
    ) {
        $this->sender = $sender;
        $this->formatter = $formatter !== null ?
            $formatter : $this->getDefaultFormatter();

        $this->setAppName($appName);
        $this->setFacility($facility);

        if ($hostName === null) {
            $hostName = $this->getLocalHostName();
        }

        $this->setHostName($hostName);
    }

    /**
     * Tries to obtain the host name
     * for the local machine.
     *
     * @return string
     */
    protected function getLocalHostName()
    {
        return ($hn = \gethostname()) === false ? '' : $hn;
    }

    /**
     * Returns the default formatter.
     *
     * @return \SyslogNet\Formatters\FormatterInterface
     */
    protected function getDefaultFormatter()
    {
        return new \SyslogNet\Formatters\FormatterRFC5424();
    }

    /**
     * Returns a new instance
     * that is the same as the current one
     * but with the provided formatter.
     *
     * @param FormatterInterface $formatter
     * @return \SyslogNet\SyslogNet
     */
    public function withFormatter(FormatterInterface $formatter)
    {
        $s = clone $this;
        $s->formatter = $formatter;
        return $s;
    }

    /**
     * Returns a new instance
     * that is the same as the current one
     * but with the provided sender.
     *
     * @param SenderInterface $sender
     * @return \SyslogNet\SyslogNet
     */
    public function withSender(SenderInterface $sender)
    {
        $s = clone $this;
        $s->sender = $sender;
        return $s;
    }

    /**
     * @param int $facility
     * @return void
     */
    public function setFacility($facility)
    {
        $this->facility = $this->filterFacility($facility);
    }

    /**
     * Checks if a `$facility` parameter is a valid facility.
     * and returns the default facility (Facility::LOCAL_0) if not.
     *
     * @param int $facility
     * @return int
     */
    protected function filterFacility($facility)
    {
        $facility = (int) $facility;
        if (
            $facility < Facility::KERN_MSG ||
            $facility > Facility::LOCAL_7
        ) {
            return Facility::LOCAL_0;
        }

        return $facility;
    }

    /**
     * @return int
     */
    public function getFacility()
    {
        return $this->facility;
    }

    /**
     * @param string $appName
     * @return void
     */
    public function setAppName($appName)
    {
        $this->appName = (string) $appName;
    }

    /**
     * @return string
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * @param string $hostName
     * @return void
     */
    public function setHostName($hostName)
    {
        $this->hostName = (string) $hostName;
    }

    /**
     * @return string
     */
    public function getHostName()
    {
        return $this->hostName;
    }

    /**
     * Tries to send the given $msg string.
     *
     * @param int $severity
     * @param string $msg
     * @throws \SyslogNet\Transports\Exceptions\MessageSizeExceeded
     * @return bool
     */
    public function send($severity, $msg)
    {
        $severity = $this->filterSeverity($severity);
        $msg = $this->createMessage($severity, $msg);
        return $this->sendMessage($msg);
    }

    /**
     * Tries to send the given message $message.
     *
     * @param \SyslogNet\SyslogMessage $message
     * @throws \SyslogNet\Transports\Exceptions\MessageSizeExceeded
     * @return bool
     */
    public function sendMessage(SyslogMessage $message)
    {
        return $this->sender->send(
            $this->formatter->format($message)
        );
    }

    /**
     * Checks if a `$severity` parameter is a valid severity level
     * and returns the default severity level (Severity::EMERG) if not.
     * @param int $severity
     * @return int
     */
    protected function filterSeverity($severity)
    {
        $severity = (int) $severity;
        if (
            $severity < Severity::EMERG ||
            $severity > Severity::DEBUG
        ) {
            return Severity::EMERG;
        }

        return $severity;
    }

    /**
     * Creates a new syslog message.
     *
     * @param int $severity
     * @param string $msg
     * @return \SyslogNet\SyslogMessage
     */
    public function createMessage($severity, $msg)
    {
        return new SyslogMessage(
            $this->getFacility(),
            $severity,
            $msg,
            $this->getAppName(),
            $this->getHostName(),
            $this->getProcId()
        );
    }

    /**
     * Returns the current process id.
     *
     * @return int
     */
    public function getProcId()
    {
        return \getmypid();
    }
}
