<?php

namespace Solarium\Tests\QueryType\Server\CoreAdmin\Result;

use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Server\CoreAdmin\Result\Result;

class ResultTest extends TestCase
{
    /**
     * @var Result
     */
    protected $result;

    public function setUp(): void
    {
        $this->result = new CoreAdminDummy();
    }

    public function testGetWasSuccessful()
    {
        $this->assertTrue($this->result->getWasSuccessful());
    }

    public function testGetStatusMessage()
    {
        $this->assertSame('OK', $this->result->getStatusMessage());
    }

    /**
     * @see Solarium\QueryType\Server\CoreAdmin\ResponseParser::parse()
     * @see Solarium\QueryType\Server\CoreAdmin\Result\Result::__get()
     */
    public function testAccessResponseAsProperty()
    {
        $data = [
            '_original_response' => [
                'timing' => [
                    'time' => 318.0,
                    'doSplit' => [
                        'time' => 318.0,
                    ],
                    'findDocSetsPerLeaf' => [
                        'time' => 0.0,
                    ],
                    'addIndexes' => [
                        'time' => 21.0,
                    ],
                    'subIWCommit' => [
                        'time' => 294.0,
                    ],
                ],
            ],
        ];

        $this->result->mapData($data);
        $this->assertSame($data['_original_response'], $this->result->response);
    }

    public function testAccessOtherProperty()
    {
        $this->result->mapData(['foo' => 'bar']);

        $this->assertSame('bar', $this->result->foo);
    }
}

class CoreAdminDummy extends Result
{
    protected $parsed = true;

    public function __construct()
    {
        $this->wasSuccessful = true;
        $this->statusMessage = 'OK';
    }

    public function mapData(array $mapData): void
    {
        parent::mapData($mapData);
    }
}
