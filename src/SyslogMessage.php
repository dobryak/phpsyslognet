<?php
namespace SyslogNet;

use SyslogNet\Exceptions\DuplicateSDElementException;

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
class SyslogMessage
{
    /**
     * @var int
     */
    protected $facility;

    /**
     * @var int
     */
    protected $severity;

    /**
     * null indicates the special NILVALUE
     * in the syslog protocol.
     * @var string
     */
    protected $appName = '';

    /**
     * null indicates the special NILVALUE
     * in the syslog protocol.
     * @var string
     */
    protected $hostName = '';

    /**
     * null indicates the special NILVALUE
     * in the syslog protocol.
     * @var string
     */
    protected $procId = '';

    /**
     * null indicates the special NILVALUE
     * in the syslog protocol.
     * @var string
     */
    protected $msgId = '';

    /**
     * @var string
     */
    protected $msg = '';

    /**
     * @var \SyslogNet\StructuredDataElement[]
     */
    protected $sdElements = [];

    /**
     * @param int $facility
     * @param int $severity
     * @param string $msg
     * @param string $appName
     * @param string $hostName
     * @param string $procId
     * @param string $msgId
     * @return void
     */
    public function __construct(
        $facility,
        $severity,
        $msg,
        $appName = '',
        $hostName = '',
        $procId = '',
        $msgId = ''
    ) {
        $this->setFacility($facility);
        $this->setSeverity($severity);
        $this->setAppName($appName);
        $this->setHostName($hostName);
        $this->setProcId($procId);
        $this->setMsgId($msgId);
        $this->setMsg($msg);
    }

    /**
     * Adds a list of structured data elements.
     *
     * @throws \InvalidArgumentException if the given list
     * contains at least one wrong element.
     * @throws \SyslogNet\Exceptions\DuplicateSDElementException
     * if one of the given SD-ELEMENT already exists.
     * @return void
     */
    public function addSDElements(array $sdElements)
    {
        foreach ($sdElements as $sdElement) {
            if (!$sdElement instanceof StructuredDataElement) {
                throw new \InvalidArgumentException(
                    '$sdElements array should contain only instance of ' .
                    StructuredDataElement::class
                );
            }

            $this->addSDElement($sdElement);
        }
    }

    /**
     * Adds a structured data element.
     *
     * @param \SyslogNet\StructuredDataElement $sdElement
     * @throws \SyslogNet\Exceptions\DuplicateSDElementException
     * if given SD-ELEMENT already exists.
     * @return void
     */
    public function addSDElement(StructuredDataElement $sdElement)
    {
        if ($this->SDIDExists($sdElement->getId())) {
            throw new DuplicateSDElementException(
                'SD-ELEMENT with SD-ID ' .
                $sdElement->getId() .
                ' already exists!'
            );
        }

        $this->sdElements[] = $sdElement;
    }

    /**
     * Returns true if SD-ELEMENT with SD-ID
     * `$sdid` already exists.
     *
     * @param string $sdid
     * @return bool
     */
    public function SDIDExists($sdid)
    {
        foreach ($this->sdElements as $sdElement) {
            if ($sdElement->getId() == $sdid) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \SyslogNet\StructuredDataElement[]
     */
    public function getSDElements()
    {
        return $this->sdElements;
    }

    /**
     * @param int $facility
     * @return void
     */
    public function setFacility($facility)
    {
        $this->facility = (int) $facility;
    }

    /**
     * @return int
     */
    public function getFacility()
    {
        return $this->facility;
    }

    /**
     * @param int $severity
     * @return void
     */
    public function setSeverity($severity)
    {
        $this->severity = (int) $severity;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return $this->severity;
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
     * @param string $procId
     * @return void
     */
    public function setProcId($procId)
    {
        $this->procId = (string) $procId;
    }

    /**
     * @return string
     */
    public function getProcId()
    {
        return $this->procId;
    }

    /**
     * @param string $msgId
     * @return void
     */
    public function setMsgId($msgId)
    {
        $this->msgId = (string) $msgId;
    }

    /**
     * @return string
     */
    public function getMsgId()
    {
        return $this->msgId;
    }

    /**
     * @param string $msg
     * @return void
     */
    public function setMsg($msg)
    {
        $this->msg = (string) $msg;
    }

    /**
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }
}
