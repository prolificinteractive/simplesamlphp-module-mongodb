<?php

/**
 * Class sspmod_mongo_Store_Store
 */
class sspmod_mongo_Store_Store extends SimpleSAML_Store
{
    protected $connection;
    protected $db;

    /**
     * sspmod_mongo_Store_Store constructor.
     *
     * @param array $connectionDetails
     */
    public function __construct($connectionDetails = array())
    {
        $config = SimpleSAML_Configuration::getConfig('module_mongo.php');
        $connectionDetails = array_merge($config->toArray(), $connectionDetails);
        $this->connection = new MongoClient($this->createConnectionURI($connectionDetails));
        $this->db = $this->connection->{$connectionDetails['database']};
    }

    /**
     * @param array $connectionDetails
     * @return string
     */
    static function createConnectionURI($connectionDetails = array()) {
        $port = $connectionDetails['port'];
        $host = $connectionDetails['host'];
        $seedList = implode(',', array_map(function($host) use ($port) {
            return "$host:$port";
        }, is_array($host) ? $host : explode(',', $host)));


        $connectionURI = "mongodb://"
            .((!empty($connectionDetails['username']) && !empty($connectionDetails['password']))
                ? "${connectionDetails['username']}:${connectionDetails['password']}@"
                : '')
            ."${seedList}"
            ."/${connectionDetails['database']}";
        if(!empty($connectionDetails['replicaSet'])) {
            $connectionURI .= "?replicaSet=${connectionDetails['replicaSet']}";
            if(!empty($connectionDetails['readPreference'])) {
                $connectionURI .= "&readPreference=${connectionDetails['readPreference']}";
            }
        }

        return $connectionURI;
    }

    /**
     * Retrieve a value from the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     *
     * @return mixed|null The value.
     */
    public function get($type, $key)
    {
        assert('is_string($type)');
        assert('is_string($key)');

        $collection = $this->db->{$type};
        $document = $collection->findOne(array(
            'session_id' => $key
        ));

        if(isset($document['expire_at'])) {
            $expireAt = $document['expire_at'];
            if($expireAt <= time()) {
                $status = $collection->remove(array(
                    'session_id' => $key
                ));
                if(!$status) {
                    SimpleSAML_Logger::error("Failed to remove expired document $type.$key");
                } else {
                    SimpleSAML_Logger::info("Removed expired document: $type:$key");
                }

                return NULL;
            }
        }

        if(!empty($document['payload'])) {
            $payload = unserialize($document['payload']);
            return $payload;
        }

        return $document;
    }

    /**
     * Save a value to the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     * @param mixed $value The value.
     * @param int|null $expire The expiration time (unix timestamp), or null if it never expires.
     * @return array|bool
     */
    public function set($type, $key, $value, $expire = null)
    {
        assert('is_string($type)');
        assert('is_string($key)');
        assert('is_null($expire) || is_int($expire)');

        $collection = $this->db->{$type};
        $document = $collection->findOne(array(
            'session_id' => $key
        ));

        if($document) {
            $document['payload'] = serialize($value);
            $document['expire_at'] = $expire;
            $status = $collection->update(array(
                'session_id' => $key
            ), $document);
            if(!$status) {
                SimpleSAML_Logger::error("Failed to update document $type.$key with value: " . var_export($value, 1));
            } else {
                SimpleSAML_Logger::info("Updated document: $type:$key");
            }
        } else {
            $status = $collection->insert(array(
                'session_id' => $key,
                'payload' => serialize($value),
                'expire_at' => $expire
            ));
            if(!$status) {
                SimpleSAML_Logger::error("Failed to create document $type.$key with value: " . var_export($value, 1));
            } else {
                SimpleSAML_Logger::info("Created document: $type:$key");
            }
        }

        return $expire;
    }

    /**
     * Delete a value from the data store.
     *
     * @param string $type The data type.
     * @param string $key The key.
     */
    public function delete($type, $key)
    {
        assert('is_string($type)');
        assert('is_string($key)');

        $collection = $this->db->{$type};
        $status = $collection->remove(array(
            'session_id' => $key
        ));
        if(!$status) {
            SimpleSAML_Logger::error("Failed to delete document: $type.$key");
        } else {
            SimpleSAML_Logger::info("Deleted document: $type:$key");
        }
    }

    /**
     * @return \MongoClient
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param \MongoClient $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return \MongoDB
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @param \MongoDB $db
     */
    public function setDb($db)
    {
        $this->db = $db;
    }
}