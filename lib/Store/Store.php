<?php
/**
 * This file is part of the simplesamlphp-module-mongo.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Chris Beaton <c.beaton@prolificinteractive.com>
 * @package prolificinteractive/simplesamlphp-module-mongo
 */

use \SimpleSAML\Store;

/**
 * Class sspmod_mongo_Store_Store
 *
 */
class sspmod_mongo_Store_Store extends Store
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
     * Builds the connection URI from the specified connection details.
     *
     * @param array $connectionDetails An array of arguments to the database connection URI.
     * @return string The connection URI.
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
                $collection->remove(array(
                    'session_id' => $key
                ));

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
            $collection->update(array(
                'session_id' => $key
            ), $document);
        } else {
            $collection->insert(array(
                'session_id' => $key,
                'payload' => serialize($value),
                'expire_at' => $expire
            ));
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
        $collection->remove(array(
            'session_id' => $key
        ));
    }

    /**
     * Returns a new database connection object.
     *
     * @return \MongoClient The database connection object.
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Sets the database connection for this store.
     *
     * @param \MongoClient $connection A database connection object.
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }
}