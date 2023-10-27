<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'header_comment' => [
            'header' => <<<EOF
This file is part of the OpenapiBundle package.

(c) Niels Nijens <nijens.niels@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        ],
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => false,
        ],
        'ordered_imports' => true,
        'yoda_style' => false, // Do not enforce Yoda style (add unit tests instead...)
    ])
    ->setFinder($finder);
