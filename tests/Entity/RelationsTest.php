<?php

namespace ORM\Test\Entity;

use ORM\EntityManager;
use ORM\Exception\IncompletePrimaryKey;
use ORM\Exception\InvalidConfiguration;
use ORM\Exception\InvalidRelation;
use ORM\Exception\UndefinedRelation;
use ORM\Exception\NoEntityManager;
use ORM\Relation\ManyToMany;
use ORM\Relation\OneToMany;
use ORM\Relation\OneToOne;
use ORM\Relation\Owner;
use ORM\Test\Entity\Examples\Article;
use ORM\Test\Entity\Examples\Category;
use ORM\Test\Entity\Examples\ContactPhone;
use ORM\Test\Entity\Examples\DamagedABBRVCase;
use ORM\Test\Entity\Examples\Psr0_StudlyCaps;
use ORM\Test\Entity\Examples\Snake_Ucfirst;
use ORM\Test\Entity\Examples\StudlyCaps;
use ORM\Test\TestCase;
use ORM\Test\Entity\Examples\RelationExample;
use ORM\EntityFetcher;

class RelationsTest extends TestCase
{
    public function provideRelationDefinitions()
    {
        return [
            [
                RelationExample::class,
                'studlyCaps',
                Owner::class,
                StudlyCaps::class,
                ['studlyCapsId' => 'id']
            ],
            [
                RelationExample::class,
                'psr0StudlyCaps',
                Owner::class,
                Psr0_StudlyCaps::class,
                ['psr0StudlyCaps' => 'id']
            ],
            [
                RelationExample::class,
                'contactPhones',
                OneToMany::class,
                ContactPhone::class,
                null,
                'relation'
            ],
            [
                RelationExample::class,
                'dmgd',
                Owner::class,
                DamagedABBRVCase::class,
                ['dmgdId' => 'id']
            ],
            [
                DamagedABBRVCase::class,
                'relation',
                OneToOne::class,
                RelationExample::class,
                null,
                'dmgd'
            ],
            [
                Snake_Ucfirst::class,
                'relations',
                OneToMany::class,
                RelationExample::class,
                null,
                'snake'
            ],
            [
                Article::class,
                'categories',
                ManyToMany::class,
                Category::class,
                ['id' => 'article_id'],
                'articles',
                'article_category'
            ],
            [
                Category::class,
                'articles',
                ManyToMany::class,
                Article::class,
                ['id' => 'category_id'],
                'categories',
                'article_category'
            ],
        ];
    }

    /** @dataProvider provideRelationDefinitions
     * @test */
    public function getRelationAlwaysHasClassAndCardinality($class, $relation, $type, $related)
    {
        $relationDefinition = $class::getRelation($relation);

        self::assertInstanceOf($type, $relationDefinition);
        self::assertSame($related, $relationDefinition->getClass());
    }

    /** @dataProvider provideRelationDefinitions
     * @test */
    public function getRelationReturnsTheSameObject($class, $relation)
    {
        $result1 = $class::getRelation($relation);
        $result2 = $class::getRelation($relation);

        self::assertSame($result1, $result2);
    }

    /** @test */
    public function throwsWhenRelationUndefined()
    {
        self::expectException(UndefinedRelation::class);
        self::expectExceptionMessage('Relation undefinedRel is not defined');

        RelationExample::getRelation('undefinedRel');
    }

    /** @test */
    public function throwsWhenShortFormIsInvalid()
    {
        self::expectException(InvalidConfiguration::class);
        self::expectExceptionMessage('Invalid short form for relation invalid');

        RelationExample::getRelation('invalid');
    }

    public function provideRelationDefinitionsWithReference()
    {
        return array_filter($this->provideRelationDefinitions(), function ($definition) {
            return !empty($definition[5]);
        });
    }

    /** @dataProvider provideRelationDefinitionsWithReference
     * @test */
    public function shortRelationDefinitionReference($class, $relation, $cardinality, $related, $reference)
    {
        $relationDefinition = $class::getRelation($relation);

        self::assertSame($reference, $relationDefinition->getReference());
    }

    public function provideRelationDefinitionsWithOpponent()
    {
        return array_filter($this->provideRelationDefinitions(), function ($definition) {
            return !empty($definition[6]);
        });
    }

    /** @dataProvider provideRelationDefinitionsWithOpponent
     * @test */
    public function shortRelationDefinitionOpponent(
        $class,
        $relation,
        $cardinality,
        $related,
        $reference,
        $opponent = null
    ) {
        $opponentRelation = $related::getRelation($opponent);

        $relationDefinition = $class::getRelation($relation);

        self::assertSame($opponentRelation, $relationDefinition->getOpponent());
    }

    public function provideRelationDefinitionsWithTable()
    {
        return array_filter($this->provideRelationDefinitions(), function ($definition) {
            return !empty($definition[6]);
        });
    }

    /** @dataProvider provideRelationDefinitionsWithTable
     * @test */
    public function shortRelationDefinitionTable(
        $class,
        $relation,
        $cardinality,
        $related,
        $reference,
        $opponent = null,
        $table = null
    ) {
        $relationDefinition = $class::getRelation($relation);

        self::assertSame($table, $relationDefinition->getTable());
    }

    /** @test */
    public function getsEntityManagerViaGetInstance()
    {
        $entity = new Article(['id' => 42]);
        $em = EntityManager::getInstance(Article::class);
        $em->shouldReceive('fetch')->with(Category::class)->once()->passthru();

        $entity->fetch('categories');
    }

    /** @test */
    public function fetchUsesEntityManagerFromConstruct()
    {
        $entity = new Article(['id' => 42], $this->em);

        $fetcher = $entity->fetch('categories');

        self::assertInstanceOf(EntityFetcher::class, $fetcher);
    }

    public function provideRelationsWithCardinalityOne()
    {
        return [
            [RelationExample::class, 'dmgd'],
            [RelationExample::class, 'mySnake'],
        ];
    }

    /** @dataProvider provideRelationsWithCardinalityOne
     * @test */
    public function getRelatedReturnsResultFromFetchFor($class, $relation)
    {
        $entity = \Mockery::mock($class)->makePartial();
        $related = new StudlyCaps();
        $entity->shouldReceive('fetch')->with($relation, true)->once()->andReturn($related);

        $result = $entity->getRelated($relation);

        self::assertSame($related, $result);
    }

    /** @dataProvider provideRelationsWithCardinalityOne
     * @test */
    public function getRelatedStoresTheValue($class, $relation)
    {
        $entity = \Mockery::mock($class)->makePartial();
        $related = new StudlyCaps();
        $entity->shouldReceive('fetch')->with($relation, true)->once()->andReturn($related);
        $entity->getRelated($relation);

        $result = $entity->getRelated($relation);

        self::assertSame($related, $result);
    }

    /** @dataProvider provideRelationsWithCardinalityOne
     * @test */
    public function refreshsRelationWithRefresh($class, $relation)
    {
        $entity = \Mockery::mock($class)->makePartial();
        $related = new StudlyCaps();
        $entity->shouldReceive('fetch')->with($relation, true)->twice()->andReturn($related);
        $entity->getRelated($relation);

        $result = $entity->getRelated($relation, true);

        self::assertSame($related, $result);
    }

    /** @dataProvider provideRelationsWithCardinalityOne
     * @test */
    public function getRelatedDoesNotStoreNullValues($class, $relation)
    {
        $entity = \Mockery::mock($class)->makePartial();
        $related = new StudlyCaps();
        $entity->shouldReceive('fetch')->with($relation, true)->twice()->andReturn(null, $related);
        $entity->getRelated($relation);

        $result = $entity->getRelated($relation);

        self::assertSame($related, $result);
    }

    /** @test */
    public function setRelationStoresTheId()
    {
        $entity = new RelationExample();
        $related = new StudlyCaps(['id' => 42]);

        $entity->setRelated('studlyCaps', $related);

        self::assertSame(42, $entity->studlyCapsId);
    }

    /** @test */
    public function setRelationThrowsWhenKeyIsIncomplete()
    {
        $entity = new RelationExample();
        $related = new StudlyCaps();

        self::expectException(IncompletePrimaryKey::class);
        self::expectExceptionMessage('Key incomplete to save foreign key');

        $entity->setRelated('studlyCaps', $related);
    }

    /** @test */
    public function setRelationThrowsWhenClassWrong()
    {
        $entity = new RelationExample();

        self::expectException(InvalidRelation::class);
        self::expectExceptionMessage('Invalid entity for relation studlyCaps');

        $entity->setRelated('studlyCaps', new Psr0_StudlyCaps(['id' => 42]));
    }

    /** @test */
    public function setRelationThrowsForNonOwner()
    {
        $entity = new DamagedABBRVCase();

        self::expectException(InvalidRelation::class);
        self::expectExceptionMessage('This is not the owner of the relation');

        $entity->setRelated('relation', new RelationExample());
    }

    /** @test */
    public function setRelationStoresTheRelatedObject()
    {
        $entity = \Mockery::mock(RelationExample::class)->makePartial();
        $related = new StudlyCaps(['id' => 42]);
        $entity->shouldNotReceive('fetch')->with('studlyCaps', null, true);
        $entity->setRelated('studlyCaps', $related);

        $result = $entity->getRelated('studlyCaps');

        self::assertSame($related, $result);
    }

    /** @test */
    public function setRelationAllowsNull()
    {
        $entity = new RelationExample([], $this->em);
        $related = new StudlyCaps(['id' => 42]);
        $entity->setRelated('studlyCaps', $related);

        $entity->setRelated('studlyCaps', null);

        self::assertNull($entity->studlyCapsId);
        self::assertNull($entity->getRelated('studlyCaps'));
    }

    /** @test */
    public function addRelatedCreatesTheAssociation()
    {
        $article = new Article(['id' => 42], $this->em);
        $category = new Category(['id' => 23]);
        $this->pdo->shouldReceive('query')
            ->with('INSERT INTO "article_category" ("article_id","category_id") VALUES (42,23)')
            ->once()->andReturn(\Mockery::mock(\PDOStatement::class));

        $article->addRelated('categories', [$category]);
    }

    /** @test */
    public function addRelatedCreatesAMultilineInsert()
    {
        $article = new Article(['id' => 42], $this->em);
        $category1 = new Category(['id' => 23]);
        $category2 = new Category(['id' => 24]);
        $this->pdo->shouldReceive('query')
                  ->with('INSERT INTO "article_category" ("article_id","category_id") VALUES (42,23),(42,24)')
                  ->once()->andReturn(\Mockery::mock(\PDOStatement::class));

        $article->addRelated('categories', [$category1, $category2]);
    }

    /** @test */
    public function addRelatedThrowsWhenClassWrong()
    {
        $article = new Article(['id' => 42], $this->em);

        self::expectException(InvalidRelation::class);
        self::expectExceptionMessage('Invalid entity for relation categories');

        $article->addRelated('categories', [new Category(['id' => 23]), new StudlyCaps()]);
    }

    /** @test */
    public function addRelatedThrowsWhenRelationIsNotManyToMany()
    {
        $entity = new RelationExample([], $this->em);

        self::expectException(InvalidRelation::class);
        self::expectExceptionMessage('This is not a many-to-many relation');

        $entity->addRelated('studlyCaps', [new StudlyCaps(['id' => 23])]);
    }

    /** @test */
    public function addRelatedThrowsWhenEntityHasNoKey()
    {
        $entity = new Article([], $this->em);

        self::expectException(IncompletePrimaryKey::class);
        self::expectExceptionMessage('Key incomplete to save foreign key');

        $entity->addRelated('categories', [new Category(['id' => 23])]);
    }

    /** @test */
    public function addRelatedThrowsWhenARelationHasNoKey()
    {
        $entity = new Article(['id' => 42], $this->em);

        self::expectException(IncompletePrimaryKey::class);
        self::expectExceptionMessage('Key incomplete to save foreign key');

        $entity->addRelated('categories', [new Category(['id' => 23]), new Category()]);
    }

    /** @test */
    public function addRelatedDoesNothingWithEmptyArray()
    {
        $entity = new Article(['id' => 42], $this->em);
        $this->pdo->shouldNotReceive('query');

        $entity->addRelated('categories', []);
    }

    /** @test */
    public function addRelatedAllowsToPassEntityManager()
    {
        $article = new Article(['id' => 42]);
        $category = new Category(['id' => 23]);
        $this->pdo->shouldReceive('query')
                  ->with('INSERT INTO "article_category" ("article_id","category_id") VALUES (42,23)')
                  ->once()->andReturn(\Mockery::mock(\PDOStatement::class));

        $article->addRelated('categories', [$category]);
    }

    /** @test */
    public function deleteRelatedDeletesTheAssociation()
    {
        $article = new Article(['id' => 42], $this->em);
        $category = new Category(['id' => 23]);
        $this->pdo->shouldReceive('query')
            ->with('DELETE FROM "article_category" WHERE "article_id" = 42 AND ("category_id" = 23)')
            ->once()->andReturn(\Mockery::mock(\PDOStatement::class));

        $article->deleteRelated('categories', [$category]);
    }

    /** @test */
    public function deleteRelatedExecutesOnlyOneStatement()
    {
        $article = new Article(['id' => 42], $this->em);
        $category1 = new Category(['id' => 23]);
        $category2 = new Category(['id' => 24]);
        $this->pdo->shouldReceive('query')
                  ->with('DELETE FROM "article_category" WHERE "article_id" = 42 ' .
                         'AND ("category_id" = 23 OR "category_id" = 24)')
                  ->once()->andReturn(\Mockery::mock(\PDOStatement::class));

        $article->deleteRelated('categories', [$category1, $category2]);
    }

    /** @test */
    public function deleteRelatedThrowsWhenClassWrong()
    {
        $article = new Article(['id' => 42], $this->em);

        self::expectException(InvalidRelation::class);
        self::expectExceptionMessage('Invalid entity for relation categories');

        $article->deleteRelated('categories', [new Category(['id' => 23]), new StudlyCaps()]);
    }

    /** @test */
    public function deleteRelatedThrowsWhenRelationIsNotManyToMany()
    {
        $entity = new RelationExample([], $this->em);

        self::expectException(InvalidRelation::class);
        self::expectExceptionMessage('This is not a many-to-many relation');

        $entity->deleteRelated('studlyCaps', [new StudlyCaps(['id' => 23])]);
    }

    /** @test */
    public function deleteRelatedThrowsWhenEntityHasNoKey()
    {
        $entity = new Article([], $this->em);

        self::expectException(IncompletePrimaryKey::class);
        self::expectExceptionMessage('Key incomplete to save foreign key');

        $entity->deleteRelated('categories', [new Category(['id' => 23])]);
    }

    /** @test */
    public function deleteRelatedThrowsWhenARelationHasNoKey()
    {
        $entity = new Article(['id' => 42], $this->em);

        self::expectException(IncompletePrimaryKey::class);
        self::expectExceptionMessage('Key incomplete to save foreign key');

        $entity->deleteRelated('categories', [new Category(['id' => 23]), new Category()]);
    }

    /** @test */
    public function deleteRelatedDoesNothingWithEmptyArray()
    {
        $entity = new Article(['id' => 42], $this->em);
        $this->pdo->shouldNotReceive('query');

        $entity->deleteRelated('categories', []);
    }

    /** @test */
    public function deleteRelatedAllowsToPassEntityManager()
    {
        $article = new Article(['id' => 42]);
        $category = new Category(['id' => 23]);
        $this->pdo->shouldReceive('query')
                  ->with('DELETE FROM "article_category" WHERE "article_id" = 42 ' .
                         'AND ("category_id" = 23)')
                  ->once()->andReturn(\Mockery::mock(\PDOStatement::class));

        $article->deleteRelated('categories', [$category]);
    }

    /** @test */
    public function serializeSavesRelated()
    {
        $entity = new RelationExample();
        $related = new DamagedABBRVCase(['id' => 42]);
        $entity->setRelated('dmgd', $related);

        $entity = unserialize(serialize($entity));
        $result = $entity->getRelated('dmgd');

        self::assertEquals($related, $result);
    }
}
