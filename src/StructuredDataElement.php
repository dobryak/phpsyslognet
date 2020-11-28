<?php
namespace SyslogNet;

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
class StructuredDataElement implements \ArrayAccess, \Iterator
{
    /**
     * @var string
     */
    protected $id = '';

    /**
     * An associative array of
     * string => string pairs.
     *
     * @var array
     */
    protected $params = [];

    /**
     * Used for iteration
     *
     * @var array
     */
    protected $keys = [];

    /**
     * Used for iteration
     *
     * @var int
     */
    protected $pos = 0;

    /**
     * @param string $id
     * @return void
     */
    public function __construct($id)
    {
        $this->id = (string) $id;
    }

    /**
     * @var string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Creates a new instance from the
     * given associative array.
     *
     * @param string $id
     * @param array $params
     * @return \SyslogNet\StructuredDataElement
     */
    public static function fromArray($id, array $params)
    {
        $sdelement = new static($id);

        foreach ($params as $k => $v) {
            $sdelement[(string) $k] = (string) $v;
        }

        return $sdelement;
    }

    /**
     * Returns true if the offset $offset exists.
     * @see \ArrayAccess
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->params[(string) $offset]);
    }

    /**
     * Returns the value by the given offset.
     * @see \ArrayAccess
     *
     * @param string $offset
     * @return string
     */
    public function offsetGet($offset)
    {
        return $this->params[(string) $offset];
    }

    /**
     * Assigns the value to the
     * specified offset.
     * @see \ArrayAccess
     *
     * @param string $offset
     * @param string $value
     * @return void
     */
    public function offsetSet($offset , $value)
    {
        $this->params[(string) $offset] = (string) $value;
    }

    /**
     * Unsets the value by offset.
     * @see \ArrayAccess
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->params[(string) $offset]);
    }

    /**
     * Returns the current value.
     * @see \Iterator
     *
     * @return string
     */
    public function current()
    {
        return $this->params[$this->keys[$this->pos]];
    }

    /**
     * Returns the key of the current element.
     * @see \Iterator
     *
     * @return string
     */
    public function key()
    {
        return $this->keys[$this->pos];
    }

    /**
     * Moves forward to next element.
     * @see \Iterator
     *
     * @return void
     */
    public function next()
    {
        ++$this->pos;
    }

    /**
     * Rewinds the iterator to the
     * first element.
     * @see \Iterator
     *
     * @return void
     */
    public function rewind()
    {
        $this->keys = array_keys($this->params);
        $this->pos = 0;
    }

    /**
     * Returns true if the current position
     * is valid.
     * @see \Iterator
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->keys[$this->pos]);
    }
}
