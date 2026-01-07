<?php

namespace Solarium\Tests\Plugin\MinimumScoreFilter;

use Solarium\Core\Query\DocumentInterface;
use Solarium\Plugin\MinimumScoreFilter\Document as FilterDocument;
use Solarium\QueryType\Select\Result\Document;
use Solarium\Tests\QueryType\Select\Result\AbstractDocumentTestCase;

class DocumentTest extends AbstractDocumentTestCase
{
    /**
     * @var FilterDocument
     */
    protected DocumentInterface $doc;

    public function setUp(): void
    {
        $doc = new Document($this->fields);
        $this->doc = new FilterDocument($doc, true);
    }

    public function testMarkedAsLowScore(): void
    {
        $this->assertTrue($this->doc->markedAsLowScore());

        $doc2 = new Document($this->fields);
        $filterDoc2 = new FilterDocument($doc2, false);
        $this->assertFalse($filterDoc2->markedAsLowScore());
    }
}
