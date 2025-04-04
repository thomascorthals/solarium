<?php

namespace Solarium\Tests\QueryType\Server\Collections\Query\Action;

use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Server\Collections\Query\Action\Reload;
use Solarium\QueryType\Server\Collections\Query\Query as CollectionsQuery;
use Solarium\QueryType\Server\Collections\Result\ReloadResult;

class ReloadTest extends TestCase
{
    protected Reload $action;

    public function setUp(): void
    {
        $this->action = new Reload();
    }

    public function testGetType()
    {
        $this->assertSame(CollectionsQuery::ACTION_RELOAD, $this->action->getType());
    }

    public function testSetName()
    {
        $this->action->setName('test');
        $this->assertSame('test', $this->action->getName());
    }

    public function testSetAsync()
    {
        $this->action->setAsync('fooXyz');
        $this->assertSame('fooXyz', $this->action->getAsync());
    }

    public function testGetResultClass()
    {
        $this->assertSame(ReloadResult::class, $this->action->getResultClass());
    }
}
