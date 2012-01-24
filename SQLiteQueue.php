<?php

class SQLiteQueue_Exception  extends Exception { }
class SQLiteQueue {

    protected $type           = null;
    protected $file_db        = null;
    protected $file_db_lock   = null;
    protected $lock_fp        = null;

    public function __construct($file_db = null, $type = 'lifo') {
        // where is the queue database ?
        if (!$file_db) {
            $this->file_db = dirname(__FILE__).'/queue.db';
        } else {
            $this->file_db = $file_db;
        }
        $this->file_db_lock = $file_db.'.lock';
        $this->file_db      = $this->file_db.'.notrans';

        // FIFO or LIFO queue ?
        $this->type = $type;
        $types = array('lifo', 'fifo');
        if (!in_array($this->type, $types)) {
            throw new SQLiteQueue_Exception('Unknown queue type. Only '.implode(' or ', $types).' are valid types.');
        }
    }

    public function __destruct() {
        @unlink($this->file_db_lock);
    }
    
    protected function lockQueue()
    {
        $this->lock_fp = fopen($this->file_db_lock, "w");
        flock($this->lock_fp, LOCK_EX);
    }
    
    protected function unlockQueue()
    {
        flock($this->lock_fp, LOCK_UN);
    }
    
    public function getQueueFile()
    {
        return $this->file_db;
    }

    protected function initQueue()
    {
        touch($this->file_db);
    }

    /**
     * Add an element to the queue
     */
    public function offer($item)
    {
        $this->lockQueue();
        
        $this->initQueue();

        // convert $item to string
        $item = serialize($item);

        $item = base64_encode($item);
        file_put_contents($this->file_db, "\n".$item, FILE_APPEND | LOCK_EX);
        $ret = true;

        $this->unlockQueue();

        return $ret;
    }

    /**
     * Return and remove an element from the queue
     */
    public function poll()
    {
        $this->lockQueue();
        
        $this->initQueue();

        $items = explode("\n", file_get_contents($this->file_db));
        if (!$items[0]) array_shift($items);
        if (count($items) > 0) {
            $item = ($this->type == 'lifo') ? array_shift($items) : array_pop($items);
            $item = unserialize(base64_decode($item));
            file_put_contents($this->file_db, implode("\n", $items), LOCK_EX);
            $this->unlockQueue();
            return $item;
        } else {
            $this->unlockQueue();
            return NULL;
        }
    }

    /**
     * Check if the queue is empty
     */
    public function isEmpty()
    {
        return $this->countItem() == 0;
    }

    /**
     * Count number of items in the queue
     */
    public function countItem()
    {
        $this->lockQueue();

        $this->initQueue();
        
        $items = explode("\n", file_get_contents($this->file_db));
        if (!$items[0]) array_shift($items);
        $ret = count($items);
        
        $this->unlockQueue();

        return $ret;
    }

}
