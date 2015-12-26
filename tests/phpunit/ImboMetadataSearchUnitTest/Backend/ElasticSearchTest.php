<?php

namespace ImboMetadataSearchUnitTest\Backend;

use Imbo\MetadataSearch\Backend\ElasticSearch;

class ElasticSearchTest extends \PHPUnit_Framework_TestCase {
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
