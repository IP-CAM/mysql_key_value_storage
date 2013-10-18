<?php
/**
 * простой тест класса HashTableStorage
 * Описание - в файле класса
 *
 * @author Vladimir Chmil <vladimir.chmil@gmail.com>
 */

require_once "lib/HashTableStorage.php";

$storage = new HashTableStorage();
$storage_mem = new HashTableStorage('storage_mem', true);

define('MAXNUM', 600);

$letters = range('a', 'z');
$sz      = count($letters) - 1;

for ($i = 0; $i < MAXNUM; $i ++) {
    $num = mt_rand(0, 10000000);

    $word = "";
    for ($j = 0; $j < mt_rand(6, 20); $j ++) {
        $word .= $letters[mt_rand(0, $sz)];
    }

    $storage->set($word . ":" . $num, array('word' => $word, 'num' => $num));
    $storage_mem->set($word . ":" . $num, array('word' => $word,
                                               'num' => $num));
}

