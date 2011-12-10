<?php

class SQLiteQueue_Exception  extends Exception { }
class SQLiteQueue {

    protected $file_db = null;
    protected $type    = null;
    protected $dbh     = null;
    protected $usetransaction = true;

    public function __construct($file_db = null, $type = 'lifo') {
        // where is the queue database ?
        if (!$file_db) {
            $this->file_db = dirname(__FILE__).'/queue.db';
        } else {
            $this->file_db = $file_db;
        }
        
        // FIFO or LIFO queue ?
        $this->type = $type;
        $types = array('lifo', 'fifo');
        if (!in_array($this->type, $types)) {
            throw new SQLiteQueue_Exception('Unknown queue type. Only '.implode(' or ', $types).' are valid types.');
        }

        // if used on old php, transactions doesn't work (tested on debian lenny)
        if (version_compare(PHP_VERSION, '5.3.3') < 0) {
            $this->usetransaction = false;
        }
    }

    public function __destruct() {
        // to be sure the database used space is optimized
        if ($this->dbh) {
            $this->dbh->exec('VACUUM');
        }

        // to be sure that PDO instance is destroyed
        unset($this->dbh);
        $this->dbh = null;
    }

    protected function initQueue()
    {
        if (!$this->dbh) {
            $this->dbh = new PDO('sqlite:'.$this->file_db);
            $this->dbh->exec('CREATE TABLE queue(id INTEGER PRIMARY KEY AUTOINCREMENT, date TIMESTAMP NOT NULL default CURRENT_TIMESTAMP, item BLOB)');
        }
    }

    /**
     * Add an element to the queue
     */
    public function offer($item)
    {
        $this->initQueue();

        // convert $item to string
        $item = serialize($item);

        $stmt = $this->dbh->prepare('INSERT INTO queue (item) VALUES (:item)');
        $stmt->bindParam(':item', $item, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Return and remove an element from the queue
     */
    public function poll()
    {
        $this->initQueue();

        // get item using transaction to avoid concurrency problems
        if ($this->usetransaction) {
            $this->dbh->exec('BEGIN EXCLUSIVE TRANSACTION');
        }
        $stmt_del = $this->dbh->prepare('DELETE FROM queue WHERE id = :id');
        $stmt_sel = $this->dbh->query('SELECT id,item FROM queue ORDER BY id '.($this->type == 'lifo' ? 'ASC' : 'DESC').' LIMIT 1');
        if ($result = $stmt_sel->fetch(PDO::FETCH_ASSOC)) {
            // destroy item from the queue and return it
            $stmt_del->bindParam(':id', $result['id'], PDO::PARAM_INT);
            $stmt_del->execute();
            if ($this->usetransaction) {
                $this->dbh->exec('COMMIT');
            }
            return unserialize($result['item']);
        } else {
            if ($this->usetransaction) {
                $this->dbh->exec('ROLLBACK');
            }
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
        $this->initQueue();

        // get the total number of items
        $stmt = $this->dbh->query('SELECT count(id) as nb FROM queue');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['nb']) ? (integer)$result['nb'] : 0;
    }

}
