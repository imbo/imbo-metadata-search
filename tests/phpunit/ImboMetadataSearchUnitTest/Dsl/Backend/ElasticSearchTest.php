<?php
namespace ImboMetadataSearchUnitTest\Backend;

use Imbo\MetadataSearch\Backend\ElasticSearch;
use Imbo\MetadataSearch\Dsl\Parser;
use Elasticsearch\Client as ElasticsearchClient;

use Exception;

class ElasticSearchTest extends \PHPUnit_Framework_TestCase {
    private $backend;
    private $client;

    public function setUp() {
        $this->client = $this->getMock('ElasticSearch\Client', ['search']);
        $this->backend = new ElasticSearch($this->client);
    }

    public function testSearch() {
        $result = [
            'hits' => [
                'total' => 0,
                'hits' => []
            ]
        ];
        $this->client->expects($this->once())
                     ->method('search')
                     ->will($this->returnValue($result));


        $ast = Parser::parse([]);
        $queryParams = [
            'page' => 1,
            'limit' => 20,
            'from' => '2015-01-01',
            'to' => '2015-01-31'
        ];
        $this->backend->search(['foo', 'bar'], $ast, $queryParams);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSearchException() {
        $this->client->expects($this->once())
                     ->method('search')
                     ->will($this->throwException(new Exception));
        $ast = Parser::parse([]);
        $queryParams = [
            'page' => 1,
            'limit' => 20,
            'from' => '2015-01-01',
            'to' => '2015-01-31'
        ];
        $this->backend->search(['foo', 'bar'], $ast, $queryParams);
    }
}
