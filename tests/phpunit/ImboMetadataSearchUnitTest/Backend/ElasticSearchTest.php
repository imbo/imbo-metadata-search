<?php

namespace ImboMetadataSearchUnitTest\Backend;

use Imbo\MetadataSearch\Backend\ElasticSearch;
use Imbo\MetadataSearch\Dsl\Parser;

use Exception;

class ElasticSearchTest extends \PHPUnit_Framework_TestCase {

    public function testSearch() {
        $client = $this->getClientMock();
        $backend = new ElasticSearch($client, 'indexName');

        $result = [
            'hits' => [
                'total' => 0,
                'hits' => []
            ]
        ];
        $client->expects($this->once())
                     ->method('search')
                     ->will($this->returnValue($result));


        $ast = Parser::parse([]);
        $queryParams = [
            'page' => 1,
            'limit' => 20,
            'from' => '2015-01-01',
            'to' => '2015-01-31'
        ];
        $backend->search(['foo', 'bar'], $ast, $queryParams);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testSearchException() {
        $client = $this->getClientMock();
        $backend = new ElasticSearch($client, 'indexName');
        $client->expects($this->once())
                     ->method('search')
                     ->will($this->throwException(new Exception("Fail")));
        $ast = Parser::parse([]);
        $queryParams = [
            'page' => 1,
            'limit' => 20,
            'from' => '2015-01-01',
            'to' => '2015-01-31'
        ];
        $backend->search(['foo', 'bar'], $ast, $queryParams);
    }

    public function testInstantiatesWithIndexNameAsStringParam() {
        $backend = new ElasticSearch($this->getClientMock(), 'indexName');

        $this->assertEquals('indexName', $backend->getIndexName());
    }

    public function testCanInstantiateWithArrayOptionParam() {
        $backend = new ElasticSearch($this->getClientMock(), [
            'index' => ['name' => 'foo']
        ]);

        $this->assertEquals('foo', $backend->getIndexName());
    }

    /**
     * @expectedException Imbo\Exception\RuntimeException
     * @expectedExceptionMessage Index name for elasticsearch metadata search backend must be given
     */
    public function testThrowsOnInvalidIndexName() {
        $backend = new ElasticSearch($this->getClientMock(), [
            'index' => ['name' => null]
        ]);
    }

    private function getClientMock() {
        return (
            $this->getMockBuilder('Elasticsearch\Client')
                ->disableOriginalConstructor()
                ->getMock()
        );
    }
}
