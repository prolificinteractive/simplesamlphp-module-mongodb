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
use MongoDb\Driver\Manager;
use MongoDb\Driver\Query;
use MongoDb\Driver\BulkWrite;

/**
 * Class sspmod_mongo_Store_Store
 *
 */
class sspmod_mongo_Store_Store extends Store
{
    protected $manager;
    protected $dbName;

    /**
     * sspmod_mongo_Store_Store constructor.
     *
     * @param array $connectionDetails
     */
    public function __construct($connectionDetails = array())
    {
	    $options = [];
        $config = SimpleSAML_Configuration::getConfig('module_mongo.php');
        $connectionDetails = array_merge($config->toArray(), $connectionDetails);
        if (!empty($config['replicaSet'])) {
        	$options['replicaSet'] = $config['replicaSet'];
        }
        $this->manager = new Manager($this->createConnectionURI($connectionDetails), $options);
        $this->dbName = $connectionDetails['database'];
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
            ."${seedList}";
           // ."/${connectionDetails['database']}";
        /*if(!empty($connectionDetails['replicaSet'])) {
            $connectionURI .= "?replicaSet=${connectionDetails['replicaSet']}";
            if(!empty($connectionDetails['readPreference'])) {
                $connectionURI .= "&readPreference=${connectionDetails['readPreference']}";
            }
        }*/

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

        $where = [
        	'session_id' => $key
        ];
        $query = new Query($where, ['limit' => 1]);

        $namespace = "{$this->dbName}.{$type}";

        $cursor = $this->manager->executeQuery($namespace, $query);

	    if (false === ($cursor = current($cursor->toArray()))) {
		    return null;
	    }

        if(isset($cursor['expire_at'])) {
            $expireAt = $cursor['expire_at'];
            if($expireAt <= time()) {
            	$this->delete($type, $key);

                return NULL;
            }
        }

        if(!empty($cursor['payload'])) {
            $payload = unserialize($cursor['payload']);
            return $payload;
        }

        return $cursor;
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

        $document = [
            'session_id' => $key,
            'payload' => serialize($value),
            'expire_at' => $expire
        ];

        $options = [
            'upsert' => true
        ];

        $bulk = new BulkWrite();
        $bulk->update(['session_id' => $key], $document, $options);
        $this->manager->executeBulkWrite($this->_getNamespace($type), $bulk);

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

        $bulk = new BulkWrite();
        $bulk->delete(['session_id' => $key]);

        $this->manager->executeBulkWrite($this->_getNamespace($type), $bulk);
    }

    protected function _getNamespace($type)
    {
    	return "{$this->dbName}.{$type}";
    }
}