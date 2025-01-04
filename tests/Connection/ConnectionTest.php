<?php

declare(strict_types=1);

namespace Tests\Connection;

use Brash\Dbal\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Tests\Connection\Support\InfrastructureSupport;

pest()->group('integration_tests');

const TABLE = 'test';

beforeEach(function () {
    $this->sut = DriverManager::getConnection(ConnectionParams::sqliteParams());
    $support = new InfrastructureSupport(TABLE);
    $support->createInfrastructure($this->sut);
});

afterEach(function () {
    $support = new InfrastructureSupport(TABLE);
    $support->dropInfrastructure($this->sut);
    /** @var Connection */
    $sut = $this->sut;
    $sut->close();
});

afterAll(function () {
    DriverManager::close();
});

test('should compare query builder', function () {
    /** @var Connection */
    $conn = $this->sut;
    $sql = $conn
        ->createQueryBuilder()
        ->select('*')
        ->from('user', 'u')
        ->where('u.id = :id')
        ->setParameter('id', 3)
        ->setMaxResults(1)
        ->getSQL();
    expect('SELECT * FROM user u WHERE u.id = :id LIMIT 1')->toEqual($sql);
});

test('should read TABLE after insert', function () {
    /** @var Connection */
    $conn = $this->sut;

    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val1',
        'field2' => 'val2',
    ]);
    $result = $conn->createQueryBuilder()
        ->select('*')
        ->from(TABLE, 't')
        ->where('t.id = ?')
        ->setParameters(['1'])
        ->setMaxResults(1)->executeQuery();

    expect($result->fetchAssociative())->toEqual([
        'id' => '1',
        'field1' => 'val1',
        'field2' => 'val2',
    ]);
});

test('expects multiple rows', function () {
    /** @var Connection */
    $conn = $this->sut;
    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val1',
        'field2' => 'val2',
    ]);
    $conn->insert(TABLE, [
        'id' => '2',
        'field1' => 'val21',
        'field2' => 'val22',
    ]);
    $conn->insert(TABLE, [
        'id' => '3',
        'field1' => 'val31',
        'field2' => 'val32',
    ]);
    $queryBuilder = $conn->createQueryBuilder();

    $result = $queryBuilder
        ->select('*')
        ->from(TABLE, 't')
        ->where($queryBuilder->expr()->or(
            $queryBuilder->expr()->eq('t.id', '?1'),
            $queryBuilder->expr()->eq('t.id', '?2')
        ))
        ->setParameters(['1', '2'])->executeQuery();

    expect($result->fetchAllAssociative())->toHaveCount(2);
});

test('Should expect error to be thrown when TABLE does not exist', function () {
    $this->sut->insert('non_existent_table', [
        'id' => '1',
        'field1' => 'val11',
        'field2' => 'val12',
    ]);
})->throws(TableNotFoundException::class);

test('should find one by query', function () {
    /** @var Connection */
    $conn = $this->sut;
    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val11',
        'field2' => 'val12',
    ]);
    expect($conn->fetchOne('select field1 from test'))->toBe('val11');
});

test('Should expect unique constraint violation', function () {
    /** @var Connection */
    $conn = $this->sut;
    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val11',
        'field2' => 'val12',
    ]);
    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val11',
        'field2' => 'val12',
    ]);
})->throws(UniqueConstraintViolationException::class);

test('should update row correctly', function () {
    /** @var Connection */
    $conn = $this->sut;

    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val11',
        'field2' => 'val12',
    ]);
    $affectedRows = $conn->update(TABLE, [
        'field1' => 'val3',
    ], [
        'id' => '1',
    ]);

    expect($affectedRows)->toBe(1);
});

test('should delete row correctly', function () {
    /** @var Connection */
    $conn = $this->sut;
    $conn->insert(TABLE, [
        'id' => '1',
        'field1' => 'val11',
        'field2' => 'val12',
    ]);

    $affectedRows = $conn->delete(TABLE, [
        'id' => '1',
    ]);

    expect($affectedRows)->toBe(1);
});
