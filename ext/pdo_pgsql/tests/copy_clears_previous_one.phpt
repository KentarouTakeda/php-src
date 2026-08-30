--TEST--
PDO PgSQL a COPY left on the connection does not hang the next copy method
--EXTENSIONS--
pdo_pgsql
--SKIPIF--
<?php
require __DIR__ . '/config.inc';
require dirname(__DIR__, 2) . '/pdo/tests/pdo_test.inc';
PDOTest::skip();
?>
--FILE--
<?php

require_once __DIR__ . "/config.inc";

/* exec() does not read past the first result, so these leave the connection copying */
$leftovers = [
    'COPY OUT' => 'COPY t TO STDOUT',
    'COPY IN' => 'COPY t FROM STDIN',
];

$copies = [
    'copyToArray' => fn(Pdo\Pgsql $db) => $db->copyToArray('t'),
    'copyFromArray' => fn(Pdo\Pgsql $db) => $db->copyFromArray('t', [42]),
    'copyToFile' => fn(Pdo\Pgsql $db) => $db->copyToFile('t', 'php://memory'),
    'copyFromFile' => fn(Pdo\Pgsql $db) => $db->copyFromFile('t', 'php://memory'),
];

$db = Pdo::connect($config['ENV']['PDOTEST_DSN']);
$db->setAttribute(Pdo::ATTR_ERRMODE, Pdo::ERRMODE_SILENT);

$db->exec('CREATE TEMP TABLE t(i int)');
$db->exec('INSERT INTO t VALUES (42)');

foreach ($copies as $copy => $call) {
    foreach ($leftovers as $leftover => $sql) {
        $db->exec($sql);
        echo "$copy after $leftover: ";
        var_dump($call($db) !== false);
    }
}
?>
--EXPECT--
copyToArray after COPY OUT: bool(true)
copyToArray after COPY IN: bool(true)
copyFromArray after COPY OUT: bool(true)
copyFromArray after COPY IN: bool(true)
copyToFile after COPY OUT: bool(true)
copyToFile after COPY IN: bool(true)
copyFromFile after COPY OUT: bool(true)
copyFromFile after COPY IN: bool(true)
