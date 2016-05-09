<?php

namespace ImboMetadataSearchUnitTest\Model;

use Imbo\MetadataSearch\Model\BackendResponse;

class BackendResponseTest extends \PHPUnit_Framework_TestCase {
    public function testGetData() {
        $model = new BackendResponse();
        $this->assertSame(['hits' => 0, 'imageIdentifiers' => []], $model->getData());

        $model->setImageIdentifiers(['foo', 'bar'])->setHits(2);
        $this->assertSame(['hits' => 2, 'imageIdentifiers' => ['foo', 'bar']], $model->getData());
    }
}
