<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
