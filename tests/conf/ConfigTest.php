<?php
namespace webfiori\tests\conf;
use PHPUnit\Framework\TestCase;
use webfiori\entity\DBConnectionInfo;
use webfiori\conf\Config;
/**
 * A set of unit tests to test the class 'Config'.
 *
 * @author Ibrahim
 */
class ConfigTest extends TestCase{
    /**
     * @test
     */
    public function testGet00() {
        $conf = Config::get();
        $this->assertSame(Config::get(),$conf);
    }
    /**
     * @test
     */
    public function testGetDbConnection00() {
        $conf = Config::get();
        $this->assertNull($conf->getDBConnection('not exist'));
    }
    /**
     * @test
     */
    public function testGetDbConnection01() {
        $conf = Config::get();
        $newConn = new DBConnectionInfo('root', '123456', 'test_db');
        $conf->addDbConnection($newConn);
        $this->assertTrue($conf->getDBConnection('New_Connection') instanceof DBConnectionInfo);
        $this->assertTrue($conf->getDBConnection(' New_Connection ') instanceof DBConnectionInfo);
    }
}