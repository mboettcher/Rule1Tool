<?php
class Group_Thread_Set implements SeekableIterator, Countable
{
	protected $_data = array();
	protected $_threads = array();
	
   	/**
     * Iterator pointer.
     *
     * @var integer
     */
    protected $_pointer = 0;

    /**
     * How many data rows there are.
     *
     * @var integer
     */
    protected $_count;
	
    
    public function __construct($data)
    {
    	$this->_data = $data;
    	$this->_count = count($this->_data);
    }
	
    /**
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return Group_Thread_Set Fluent interface.
     */
    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }

    /**
     * Return the current element.
     * Similar to the current() function for arrays in PHP
     * Required by interface Iterator.
     *
     * @return Group_Thread current element from the collection
     */
    public function current()
    {
        if ($this->valid() === false) {
            return null;
        }

        // do we already have a row object for this position?
        if (empty($this->_threads[$this->_pointer])) {
            $this->_threads[$this->_pointer] = new Group_Thread(null, $this->_data[$this->_pointer]);
        }

        // return the row object
        return $this->_threads[$this->_pointer];
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->_pointer;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return void
     */
    public function next()
    {
        ++$this->_pointer;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     * Used to check if we've iterated to the end of the collection.
     * Required by interface Iterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->_pointer < $this->_count;
    }

    /**
     * Returns the number of elements in the collection.
     *
     * Implements Countable::count()
     *
     * @return int
     */
    public function count()
    {
        return $this->_count;
    }
    
    /**
     * Take the Iterator to position $position
     * Required by interface SeekableIterator.
     *
     * @param int $position the position to seek to
     * @return Group_Thread_Set
     * @throws Zend_Exception
     */
    public function seek($position)
    {
        $position = (int) $position;
        if ($position < 0 || $position > $this->_count) {
            throw new Zend_Exception("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;        
    }
    	
	
}