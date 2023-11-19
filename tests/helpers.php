<?php

namespace GALIAIS\Flysystem\IPFS;

function file_get_contents($path): string
{
    return "contents of {$path}";
}

function fopen($path, $mode): string
{
    return "resource of {$path} with mode {$mode}";
}

$GLOBALS['result_of_ini_get'] = true;

function ini_get()
{
    return $GLOBALS['result_of_ini_get'];
}
