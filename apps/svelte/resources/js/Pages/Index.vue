<php>
use Symfony\Component\Finder\Finder;

$files = new Finder()->files()->in(config('fusion.paths.pages'))->name('*.vue');

$files = collect($files)->map(function($file) {
    return str($file->getRelativePathname())->chopEnd('.vue')->lower();
});

prop($files);
</php>

<template>
    <ul v-for='file in files' class='mx-4 my-2'>
      <li>
        <a :href='file'>{{ file }}</a>
      </li>
    </ul>
</template>
