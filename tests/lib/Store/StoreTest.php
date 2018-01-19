<?php
/**
 * Test for the store:Mongo data store.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Chris Beaton <c.beaton@prolificinteractive.com>
 * @package prolificinteractive/simplesamlphp-module-mongo
 */

namespace SimpleSAML\Test\Module\mongo\Store;

use PHPUnit\Framework\TestCase;
use \SimpleSAML_Configuration as Configuration;

final class StoreTest extends TestCase
{
    public function testSingleHostConnection()
    {
        Configuration::setConfigDir(__DIR__ . '/fixture/single-host');
        new \sspmod_mongo_Store_Store();
        $this->assertTrue(true);
    }

    public function testGet()
    {
        $store = new \sspmod_mongo_Store_Store();
        $connection = $store->getConnection();
        $database = getenv('DB_MONGODB_DATABASE');
        $collection = $connection->{$database}->session;
        $collection->remove();
        $this->assertEquals(0, $collection->count());

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = time() + 1000000;

        $result = $store->get($type, $key);
        $this->assertNull($result);

        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $collection->count());

        $result = $store->get($type, $key);
        $this->assertEquals($result, $value);
        $this->assertEquals(1, $collection->count());
    }

    public function testExpiredGet()
    {
        $store = new \sspmod_mongo_Store_Store();
        $connection = $store->getConnection();
        $database = getenv('DB_MONGODB_DATABASE');
        $collection = $connection->{$database}->session;
        $collection->remove();
        $this->assertEquals(0, $collection->count());

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = 0;
        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $collection->count());

        $result = $store->get($type, $key);
        $this->assertNull($result);
        $this->assertEquals(0, $collection->count());
    }

    public function testSet()
    {
        $store = new \sspmod_mongo_Store_Store();
        $connection = $store->getConnection();
        $database = getenv('DB_MONGODB_DATABASE');
        $collection = $connection->{$database}->session;
        $collection->remove();
        $this->assertEquals(0, $collection->count());

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = time() + 1000000;

        $result = $store->get($type, $key);
        $this->assertNull($result);

        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $collection->count());

        $result = $store->get($type, $key);
        $this->assertEquals($result, $value);
        $this->assertEquals(1, $collection->count());

        $value = array('some' => 'otherthing');
        $result = $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $collection->count());
        $this->assertEquals($expire, $result);
        $result = $store->get($type, $key);
        $this->assertEquals($result, $value);
        $this->assertEquals(1, $collection->count());
    }

    public function testDelete()
    {
        $store = new \sspmod_mongo_Store_Store();
        $connection = $store->getConnection();
        $database = getenv('DB_MONGODB_DATABASE');
        $collection = $connection->{$database}->session;
        $collection->remove();
        $this->assertEquals(0, $collection->count());

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = time() + 1000000;
        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $collection->count());

        $store->delete($type, $key);
        $this->assertEquals(0, $collection->count());
    }
}