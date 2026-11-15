<?php

/*
 * The package's own vendor/ when it is installed on its own, otherwise the monorepo's.
 */
require is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/../vendor/autoload.php'
    : __DIR__.'/../../../vendor/autoload.php';
