<?php

namespace Solarium\Tests\QueryType\Select\Query;

use PHPUnit\Framework\TestCase;
use Solarium\Component\Analytics\Analytics;
use Solarium\Component\Debug;
use Solarium\Component\DisMax;
use Solarium\Component\DistributedSearch;
use Solarium\Component\FacetSet;
use Solarium\Component\Grouping;
use Solarium\Component\Highlighting\Highlighting;
use Solarium\Component\MoreLikeThis;
use Solarium\Component\QueryElevation;
use Solarium\Component\ReRankQuery;
use Solarium\Component\Spatial;
use Solarium\Component\Spellcheck;
use Solarium\Component\Stats\Stats;
use Solarium\Component\Suggester;
use Solarium\Component\TermVector;
use Solarium\Core\Client\Client;
use Solarium\Exception\InvalidArgumentException;
use Solarium\Exception\OutOfBoundsException;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query;

abstract class AbstractQueryTestCase extends TestCase
{
    /**
     * @var Query
     */
    protected $query;

    public function testGetType()
    {
        $this->assertSame(Client::QUERY_SELECT, $this->query->getType());
    }

    public function testGetResponseParser()
    {
        $this->assertInstanceOf(
            'Solarium\QueryType\Select\ResponseParser',
            $this->query->getResponseParser()
        );
    }

    public function testGetRequestBuilder()
    {
        $this->assertInstanceOf(
            'Solarium\QueryType\Select\RequestBuilder',
            $this->query->getRequestBuilder()
        );
    }

    public function testSetAndGetResultClass()
    {
        $this->query->setResultClass('MyResult');
        $this->assertSame('MyResult', $this->query->getResultClass());
    }

    public function testSetAndGetDocumentClass()
    {
        $this->query->setDocumentClass('MyDocument');
        $this->assertSame('MyDocument', $this->query->getDocumentClass());
    }

    public function testSetAndGetQueryWithTrim()
    {
        $this->query->setQuery(' *:* ');
        $this->assertSame('*:*', $this->query->getQuery());
    }

    public function testSetAndGetQueryWithBind()
    {
        $this->query->setQuery('id:%1%', [678]);
        $this->assertSame('id:678', $this->query->getQuery());
    }

    public function testSetAndGetQueryDefaultOperator()
    {
        $value = Query::QUERY_OPERATOR_AND;

        $this->query->setQueryDefaultOperator($value);
        $this->assertSame($value, $this->query->getQueryDefaultOperator());
    }

    public function testSetAndGetQueryDefaultField()
    {
        $value = 'mydefault';

        $this->query->setQueryDefaultField($value);
        $this->assertSame($value, $this->query->getQueryDefaultField());
    }

    public function testSetAndGetStart()
    {
        $this->query->setStart(234);
        $this->assertSame(234, $this->query->getStart());
    }

    public function testSetAndGetRows()
    {
        $this->query->setRows(100);
        $this->assertSame(100, $this->query->getRows());
    }

    public function testGetDefaultCanCancel()
    {
        $this->assertNull($this->query->getCanCancel());
    }

    public function testSetAndGetCanCancel()
    {
        $this->query->setCanCancel(true);
        $this->assertTrue($this->query->getCanCancel());
    }

    public function testGetDefaultQueryUuid()
    {
        $this->assertNull($this->query->getQueryUuid());
    }

    public function testSetAndGetQueryUuid()
    {
        $this->query->setQueryUuid('foobar');
        $this->assertSame('foobar', $this->query->getQueryUuid());
    }

    public function testAddField()
    {
        $expectedFields = $this->query->getFields();
        $expectedFields[] = 'newfield';
        $this->query->addField('newfield');
        $this->assertSame($expectedFields, $this->query->getFields());
    }

    public function testClearFields()
    {
        $this->query->addField('newfield');
        $this->query->clearFields();
        $this->assertSame([], $this->query->getFields());
    }

    public function testAddFields()
    {
        $fields = ['field1', 'field2'];

        $this->query->clearFields();
        $this->query->addFields($fields);
        $this->assertSame($fields, $this->query->getFields());
    }

    public function testAddFieldsAsStringWithTrim()
    {
        $this->query->clearFields();
        $this->query->addFields('field1, field2');
        $this->assertSame(['field1', 'field2'], $this->query->getFields());
    }

    public function testRemoveField()
    {
        $this->query->clearFields();
        $this->query->addFields(['field1', 'field2']);
        $this->query->removeField('field1');
        $this->assertSame(['field2'], $this->query->getFields());
    }

    public function testSetFields()
    {
        $this->query->clearFields();
        $this->query->addFields(['field1', 'field2']);
        $this->query->setFields(['field3', 'field4']);
        $this->assertSame(['field3', 'field4'], $this->query->getFields());
    }

    public function testAddSort()
    {
        $this->query->addSort('field1', Query::SORT_DESC);
        $this->assertSame(
            ['field1' => Query::SORT_DESC],
            $this->query->getSorts()
        );
    }

    public function testAddSorts()
    {
        $sorts = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_ASC,
        ];

        $this->query->addSorts($sorts);
        $this->assertSame(
            $sorts,
            $this->query->getSorts()
        );
    }

    public function testRemoveSort()
    {
        $sorts = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_ASC,
        ];

        $this->query->addSorts($sorts);
        $this->query->removeSort('field1');
        $this->assertSame(
            ['field2' => Query::SORT_ASC],
            $this->query->getSorts()
        );
    }

    public function testRemoveInvalidSort()
    {
        $sorts = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_ASC,
        ];

        $this->query->addSorts($sorts);
        $this->query->removeSort('invalidfield'); // continue silently
        $this->assertSame(
            $sorts,
            $this->query->getSorts()
        );
    }

    public function testClearSorts()
    {
        $sorts = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_ASC,
        ];

        $this->query->addSorts($sorts);
        $this->query->clearSorts();
        $this->assertSame(
            [],
            $this->query->getSorts()
        );
    }

    public function testSetSorts()
    {
        $sorts = [
            'field1' => Query::SORT_DESC,
            'field2' => Query::SORT_ASC,
        ];

        $this->query->addSorts($sorts);
        $this->query->setSorts(['field3' => Query::SORT_ASC]);
        $this->assertSame(
            ['field3' => Query::SORT_ASC],
            $this->query->getSorts()
        );
    }

    public function testAddAndGetFilterQuery()
    {
        $fq = new FilterQuery();
        $fq->setKey('fq1')->setQuery('category:1');
        $this->query->addFilterQuery($fq);

        $this->assertSame(
            $fq,
            $this->query->getFilterQuery('fq1')
        );
    }

    public function testAddAndGetFilterQueryWithKey()
    {
        $key = 'fq1';

        $fq = $this->query->createFilterQuery($key);
        $fq->setQuery('category:1');

        $this->assertSame(
            $key,
            $fq->getKey()
        );

        $this->assertSame(
            $fq,
            $this->query->getFilterQuery('fq1')
        );
    }

    public function testAddFilterQueryWithoutKey()
    {
        $fq = new FilterQuery();
        $fq->setQuery('category:1');

        $this->expectException(InvalidArgumentException::class);
        $this->query->addFilterQuery($fq);
    }

    public function testAddFilterQueryWithEmptyKey()
    {
        $fq = new FilterQuery();
        $fq->setKey('')->setQuery('category:1');

        $this->expectException(InvalidArgumentException::class);
        $this->query->addFilterQuery($fq);
    }

    public function testAddFilterQueryWithUsedKey()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq1')->setQuery('category:2');

        $this->query->addFilterQuery($fq1);
        $this->expectException(InvalidArgumentException::class);
        $this->query->addFilterQuery($fq2);
    }

    public function testGetInvalidFilterQuery()
    {
        $this->assertNull(
            $this->query->getFilterQuery('invalidtag')
        );
    }

    public function testAddFilterQueries()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq2')->setQuery('category:2');

        $filterQueries = ['fq1' => $fq1, 'fq2' => $fq2];

        $this->query->addFilterQueries($filterQueries);
        $this->assertSame(
            $filterQueries,
            $this->query->getFilterQueries()
        );
    }

    public function testRemoveFilterQuery()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq2')->setQuery('category:2');

        $filterQueries = [$fq1, $fq2];

        $this->query->addFilterQueries($filterQueries);
        $this->query->removeFilterQuery('fq1');
        $this->assertSame(
            ['fq2' => $fq2],
            $this->query->getFilterQueries()
        );
    }

    public function testRemoveFilterQueryWithObjectInput()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq2')->setQuery('category:2');

        $filterQueries = [$fq1, $fq2];

        $this->query->addFilterQueries($filterQueries);
        $this->query->removeFilterQuery($fq1);
        $this->assertSame(
            ['fq2' => $fq2],
            $this->query->getFilterQueries()
        );
    }

    public function testRemoveInvalidFilterQuery()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq2')->setQuery('category:2');

        $filterQueries = ['fq1' => $fq1, 'fq2' => $fq2];

        $this->query->addFilterQueries($filterQueries);
        $this->query->removeFilterQuery('fq3'); // continue silently
        $this->assertSame(
            $filterQueries,
            $this->query->getFilterQueries()
        );
    }

    public function testClearFilterQueries()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq2')->setQuery('category:2');

        $filterQueries = [$fq1, $fq2];

        $this->query->addFilterQueries($filterQueries);
        $this->query->clearFilterQueries();
        $this->assertSame(
            [],
            $this->query->getFilterQueries()
        );
    }

    public function testSetFilterQueries()
    {
        $fq1 = new FilterQuery();
        $fq1->setKey('fq1')->setQuery('category:1');

        $fq2 = new FilterQuery();
        $fq2->setKey('fq2')->setQuery('category:2');

        $filterQueries1 = ['fq1' => $fq1, 'fq2' => $fq2];

        $this->query->addFilterQueries($filterQueries1);

        $fq3 = new FilterQuery();
        $fq3->setKey('fq3')->setQuery('category:3');

        $fq4 = new FilterQuery();
        $fq4->setKey('fq4')->setQuery('category:4');

        $filterQueries2 = ['fq3' => $fq3, 'fq4' => $fq4];

        $this->query->setFilterQueries($filterQueries2);

        $this->assertSame(
            $filterQueries2,
            $this->query->getFilterQueries()
        );
    }

    public function testConfigMode()
    {
        $config = [
            'query' => 'text:mykeyword',
            'sort' => ['score' => 'asc'],
            'fields' => ['id', 'title', 'category'],
            'rows' => 100,
            'start' => 200,
            'filterquery' => [
                ['key' => 'pub', 'local_tag' => ['pub'], 'query' => 'published:true'],
                'online' => ['local_tag' => 'onl', 'query' => 'online:true'],
            ],
            'component' => [
                'facetset' => [
                    'facet' => [
                        ['type' => 'field', 'local_key' => 'categories', 'field' => 'category'],
                        'category13' => ['type' => 'query', 'query' => 'category:13'],
                    ],
                ],
            ],
            'resultclass' => 'MyResultClass',
            'documentclass' => 'MyDocumentClass',
            'local_tag' => ['t1', 't2'],
            'cursormark' => '*',
            'splitonwhitespace' => false,
        ];
        $query = new Query($config);

        $this->assertSame($config['query'], $query->getQuery());
        $this->assertSame($config['sort'], $query->getSorts());
        $this->assertSame($config['fields'], $query->getFields());
        $this->assertSame($config['rows'], $query->getRows());
        $this->assertSame($config['start'], $query->getStart());
        $this->assertSame($config['documentclass'], $query->getDocumentClass());
        $this->assertSame($config['resultclass'], $query->getResultClass());
        $this->assertSame($config['cursormark'], $query->getCursorMark());
        $this->assertFalse($query->getSplitOnWhitespace());
        $this->assertSame('published:true', $query->getFilterQuery('pub')->getQuery());
        $this->assertSame('online:true', $query->getFilterQuery('online')->getQuery());

        $facets = $query->getFacetSet()->getFacets();
        $this->assertSame(
            'category',
            $facets['categories']->getField()
        );
        $this->assertSame(
            'category:13',
            $facets['category13']->getQuery()
        );

        $components = $query->getComponents();
        $this->assertCount(1, $components);
        $this->assertInstanceOf(FacetSet::class, array_pop($components));
        $this->assertSame(['t1', 't2'], $query->getLocalParameters()->getTags());
    }

    public function testConfigModeWithSingleValueTag()
    {
        $query = new Query(['local_tag' => 't1']);
        $this->assertSame(['t1'], $query->getLocalParameters()->getTags());
    }

    public function testSetAndGetComponents()
    {
        $mlt = new MoreLikeThis();
        $this->query->setComponent('mlt', $mlt);

        $this->assertSame(
            ['mlt' => $mlt],
            $this->query->getComponents()
        );
    }

    public function testSetAndGetComponent()
    {
        $mlt = new MoreLikeThis();
        $this->query->setComponent('mlt', $mlt);

        $this->assertSame(
            $mlt,
            $this->query->getComponent('mlt')
        );
    }

    public function testSetAndGetComponentQueryInstance()
    {
        $mlt = new MoreLikeThis();
        $this->query->setComponent('mlt', $mlt);

        $this->assertSame(
            $this->query,
            $this->query->getComponent('mlt')->getQueryInstance()
        );
    }

    public function testGetInvalidComponent()
    {
        $this->assertNull(
            $this->query->getComponent('invalid')
        );
    }

    public function testGetInvalidComponentAutoload()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->query->getComponent('invalid', true);
    }

    public function testRemoveComponent()
    {
        $mlt = new MoreLikeThis();
        $this->query->setComponent('mlt', $mlt);

        $this->assertSame(
            ['mlt' => $mlt],
            $this->query->getComponents()
        );

        $this->query->removeComponent('mlt');

        $this->assertSame(
            [],
            $this->query->getComponents()
        );
    }

    public function testRemoveComponentWithObjectInput()
    {
        $mlt = new MoreLikeThis();
        $this->query->setComponent('mlt', $mlt);

        $this->assertSame(
            ['mlt' => $mlt],
            $this->query->getComponents()
        );

        $this->query->removeComponent($mlt);

        $this->assertSame(
            [],
            $this->query->getComponents()
        );
    }

    public function testGetMoreLikeThis()
    {
        $this->assertInstanceOf(
            MoreLikeThis::class,
            $this->query->getMoreLikeThis()
        );
    }

    public function testGetDisMax()
    {
        $this->assertInstanceOf(
            DisMax::class,
            $this->query->getDisMax()
        );
    }

    public function testGetHighlighting()
    {
        $this->assertInstanceOf(
            Highlighting::class,
            $this->query->getHighlighting()
        );
    }

    public function testGetGrouping()
    {
        $this->assertInstanceOf(
            Grouping::class,
            $this->query->getGrouping()
        );
    }

    public function testGetQueryElevation()
    {
        $this->assertInstanceOf(
            QueryElevation::class,
            $this->query->getQueryElevation()
        );
    }

    public function testRegisterComponentType()
    {
        $components = $this->query->getComponentTypes();
        $components['mykey'] = 'mycomponent';

        $this->query->registerComponentType('mykey', 'mycomponent');

        $this->assertSame(
            $components,
            $this->query->getComponentTypes()
        );
    }

    public function testCreateFilterQuery()
    {
        $options = ['optionA' => 1, 'optionB' => 2];
        $fq = $this->query->createFilterQuery($options);

        // check class
        $this->assertInstanceOf(FilterQuery::class, $fq);

        // check option forwarding
        $fqOptions = $fq->getOptions();
        $this->assertSame(
            $options['optionB'],
            $fqOptions['optionB']
        );
    }

    public function testGetSpellcheck()
    {
        $this->assertInstanceOf(
            Spellcheck::class,
            $this->query->getSpellcheck()
        );
    }

    public function testGetSuggester()
    {
        $this->assertInstanceOf(
            Suggester::class,
            $this->query->getSuggester()
        );
    }

    public function testGetTermVector()
    {
        $this->assertInstanceOf(
            TermVector::class,
            $this->query->getTermVector()
        );
    }

    public function testGetDistributedSearch()
    {
        $this->assertInstanceOf(
            DistributedSearch::class,
            $this->query->getDistributedSearch()
        );
    }

    public function testGetStats()
    {
        $this->assertInstanceOf(
            Stats::class,
            $this->query->getStats()
        );
    }

    public function testGetDebug()
    {
        $this->assertInstanceOf(
            Debug::class,
            $this->query->getDebug()
        );
    }

    public function testGetSpatial()
    {
        $this->assertInstanceOf(
            Spatial::class,
            $this->query->getSpatial()
        );
    }

    public function testGetReRankQuery()
    {
        $this->assertInstanceOf(
            ReRankQuery::class,
            $this->query->getReRankQuery()
        );
    }

    public function testGetAnalytics(): void
    {
        $this->assertInstanceOf(
            Analytics::class,
            $this->query->getAnalytics()
        );
    }

    public function testAddTag()
    {
        $this->query->addTag('testtag');
        $this->assertSame(['testtag'], $this->query->getTags());
    }

    public function testAddTags()
    {
        $this->query->addTags(['t1', 't2']);
        $this->assertSame(['t1', 't2'], $this->query->getTags());
    }

    public function testRemoveTag()
    {
        $this->query->addTags(['t1', 't2']);
        $this->query->removeTag('t1');
        $this->assertSame(['t2'], $this->query->getTags());
    }

    public function testClearTags()
    {
        $this->query->addTags(['t1', 't2']);
        $this->query->clearTags();
        $this->assertSame([], $this->query->getTags());
    }

    public function testSetTags()
    {
        $this->query->addTags(['t1', 't2']);
        $this->query->setTags(['t3', 't4']);
        $this->assertSame(['t3', 't4'], $this->query->getTags());
    }

    public function testGetDefaultPartialResults()
    {
        $this->assertNull($this->query->getPartialResults());
    }

    public function testSetAndGetPartialResults()
    {
        $this->query->setPartialResults(true);
        $this->assertTrue($this->query->getPartialResults());
    }

    public function testGetDefaultTimeAllowed()
    {
        $this->assertNull($this->query->getTimeAllowed());
    }

    public function testSetAndGetTimeAllowed()
    {
        $this->query->setTimeAllowed(1200);
        $this->assertSame(1200, $this->query->getTimeAllowed());
    }

    public function testGetDefaultCpuAllowed()
    {
        $this->assertNull($this->query->getCpuAllowed());
    }

    public function testSetAndGetCpuAllowed()
    {
        $this->query->setCpuAllowed(500);
        $this->assertSame(500, $this->query->getCpuAllowed());
    }

    public function testGetDefaultMemAllowed()
    {
        $this->assertNull($this->query->getMemAllowed());
    }

    public function testSetAndGetMemAllowed()
    {
        $this->query->setMemAllowed(2.5);
        $this->assertSame(2.5, $this->query->getMemAllowed());
    }

    public function testGetDefaultSegmentTerminateEarly()
    {
        $this->assertNull($this->query->getSegmentTerminateEarly());
    }

    public function testSetAndGetSegmentTerminateEarly()
    {
        $this->query->setSegmentTerminateEarly(true);
        $this->assertTrue($this->query->getSegmentTerminateEarly());
    }

    public function testGetDefaultMultiThreaded()
    {
        $this->assertNull($this->query->getMultiThreaded());
    }

    public function testSetAndGetMultiThreaded()
    {
        $this->query->setMultiThreaded(true);
        $this->assertTrue($this->query->getMultiThreaded());
    }

    public function testSetCursorMark()
    {
        $this->query->setCursorMark('*');
        $this->assertSame('*', $this->query->getCursorMark());
    }

    public function testClearCursorMark()
    {
        $this->query->setCursorMark('*');
        $this->query->clearCursorMark();
        $this->assertNull($this->query->getCursorMark());
    }

    public function testSetAndGetSplitOnWhitespace()
    {
        $this->query->setSplitOnWhitespace(false);
        $this->assertFalse($this->query->getSplitOnWhitespace());
    }
}
