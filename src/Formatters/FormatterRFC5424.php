<?php
namespace SyslogNet\Formatters;

use SyslogNet\SyslogMessage;

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
class FormatterRFC5424 extends AbstractFormatter
{
    const VERSION = 1;
    const SP = b"\x20";
    const NILVALUE = b"\x2D";
    const UTF8_BOM = b"\xEF\xBB\xBF";

    /**
     * @inheritdoc
     */
    public function format(SyslogMessage $message)
    {
        $header = $this->formatHeader($message);

        $msg = $message->getMsg();
        $this->formatMsg($msg);

        $sd = $this->formatStructuredData(
            $message->getSDElements()
        );

        return $header
            . static::SP
            . $sd
            . static::SP
            . $msg;
    }

    /**
     * Creates a header according to RFC5424 ABNF HEADER.
     *
     * @param \SyslogNet\SyslogMessage $message
     * @return string
     */
    protected function formatHeader(SyslogMessage $message)
    {
        $priority = $this->calculatePriority(
            $message->getFacility(),
            $message->getSeverity()
        );

        $timestamp = \date("Y-m-d\TH:i:sP");

        $hostName = $message->getHostName();
        $this->filterNullableASCIIStr($hostName, 255);

        $appName = $message->getAppName();
        $this->filterNullableASCIIStr($appName, 48);

        $procId = $message->getProcId();
        $this->filterNullableASCIIStr($procId, 128);

        $msgId = $message->getMsgId();
        $this->filterNullableASCIIStr($msgId, 32);

        return '<' . $priority . '>'
            . static::VERSION
            . static::SP
            . $timestamp
            . static::SP
            . $hostName
            . static::SP
            . $appName
            . static::SP
            . $procId
            . static::SP
            . $msgId;
    }

    /**
     * Formats the given message
     * according to the RFC5424 ABNF MSG.
     *
     * @param string &$msg
     * @return void
     */
    protected function formatMsg(&$msg)
    {
        $encoding = \mb_detect_encoding($msg);
        if (\strpos($encoding, 'ASCII') === false) {
            // Use UTF-8 with BOM
            $msg = \mb_convert_encoding($msg, 'UTF-8');
            $msg = static::UTF8_BOM . $msg;
        }
    }

    /**
     * Returns formated STRUCTURED-DATA.
     *
     * @param \SyslogNet\StructuredDataElement[] $sdElements
     * @return string
     */
    protected function formatStructuredData(array $sdElements)
    {
        if (\sizeof($sdElements) == 0) {
            return static::NILVALUE;
        }

        $sd = '';
        /** @var \SyslogNet\StructuredDataElement */
        foreach ($sdElements as $sdElement) {
            $id = $sdElement->getId();
            $this->filterSDName($id);
            $el = '[' . $id;

            foreach ($sdElement as $k => $v) {
                $el .= static::SP . $this->formatStructuredDataParam($k, $v);
            }

            $sd .= $el . ']';
        }

        return $sd;
    }

    /**
     * Returns formated SD-PARAM.
     *
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function formatStructuredDataParam($key, $value)
    {
        $this->filterSDName($key);
        $this->filterSDParamValue($value);

        return $key . '="' . $value . '"';
    }

    /**
     * Filters structured data param value,
     * according to the RFC5424 ABNF (PARAM-VALUE).
     *
     * @param string &$value
     * @return void
     */
    protected function filterSDParamValue(&$value)
    {
        $value = \str_replace(['"', '\\', ']'], ['\"', '\\\\', '\]'], $value);
        $value = \mb_convert_encoding($value, 'UTF-8');
    }

    /**
     * Filters structured data name value,
     * according to the RFC5424 ABNF (SD-NAME).
     *
     * @param string &$value
     * @return void
     */
    protected function filterSDName(&$value)
    {
        $value = \str_replace(['=', ']', '"'], '', $value);
        $this->filterPRINTUSASCII($value);
        $this->filterLength($value, 32);
    }

    /**
     * Filters the given ASCII string, that
     * may be empty, contains only printable ASCII characters
     * and should have a limited lenght.
     *
     * @param string &$str
     * @param int $maxLen
     * @return void
     */
    protected function filterNullableASCIIStr(&$str, $maxLen)
    {
        $this->filterEmptyValue($str);
        $this->filterLength($str, $maxLen);
        $this->filterPRINTUSASCII($str);
    }

    /**
     * @param string &$str
     * @param int $maxLen The maximum number
     * of characters.
     * @param string $encoding The encoding of the
     * given string.
     * @return string
     */
    protected function filterLength(&$str, $maxLen, $encoding = 'ASCII')
    {
        $maxLen = (int) $maxLen;
        if (\mb_strlen($str, $encoding) > $maxLen) {
            $str = \mb_substr($str, 0, $maxLen);
        }
    }

    /**
     * Checks if the string contains only
     * printable ASCII characters 33 through 126 inclusive.
     * Changes not printable characters to '?'.
     *
     * @param string &$str
     * @return void
     */
    protected function filterPRINTUSASCII(&$str)
    {
        if (!\ctype_print($str) || \strpos($str, \chr(32)) !== false) {
            $this->filterASCII($str, 33, 126);
        }
    }

    /**
     * Scans the given string `$str` for the ASCII characters
     * not in the given range (`$low` - `$high`) and replaces them with the
     * `$sub` character.
     *
     * @param string &$str
     * @param int $low The lowest acceptable ASCII code
     * @param int $high The highest acceptable ASCII code
     * @param string $sub The subsitution character for all
     * characters that are not in the given range ($low - $high).
     * @return void
     */
    protected function filterASCII(&$str, $low, $high, $sub = '?')
    {
        $l = \strlen($str);
        for ($i = 0; $i < $l; $i++) {
            $c = \ord($str[$i]);
            if ($c < $low || $c > $high) {
                $str[$i] = $sub;
            }
        }
    }

    /**
     * Checks if the given value is empty
     * and replaces it with the NILVALUE.
     *
     * @param mixed &$value
     * @return void
     */
    protected function filterEmptyValue(&$value)
    {
        if (empty($value)) {
            $value = static::NILVALUE;
        }
    }
}
