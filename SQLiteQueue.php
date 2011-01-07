<?php

class SQLiteQueue {

    protected $file_db = null;
    protected $dbh     = null;
    
    public function __construct($file_db = null) {
        if (!$file_db) {
            $this->file_db = dirname(__FILE__).'/queue.db';
        } else {
            $this->file_db = $file_db;
        }
    }
    
    protected function initQueue()
    {
        $db_exists = file_exists($this->file_db);

        try {
            $this->dbh = new PDO('sqlite:'.$this->file_db);
        } catch( PDOException $exception ) {
            die($exception->getMessage());
        }

        if (!$db_exists) {
            $this->dbh->exec('PRAGMA auto_vacuum = 1');
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

        // récupération de l'élément
        $stmt = $this->dbh->query('SELECT * FROM queue ORDER BY id LIMIT 1');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // destruction de l'élément
            $stmt = $this->dbh->prepare('DELETE FROM queue WHERE id = :id');
            $stmt->bindParam(':id', $result['id'], PDO::PARAM_INT);
            $stmt->execute();

            return unserialize($result['item']);
        } else {
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

        // récupération du nombre d'éléments
        $stmt = $this->dbh->query('SELECT count(id) as nb FROM queue');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($result['nb']) ? (integer)$result['nb'] : 0;
    }
}
