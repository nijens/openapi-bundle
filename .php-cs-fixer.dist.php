<?php

$finder = PhpCsFixer\Finder::create()
    ->in(
        array(
            __DIR__.'/src',
            __DIR__.'/tests',
        )
    );

return (new PhpCsFixer\Config())
    ->setRules(array(
        '@Symfony' => true,
        'yoda_style' => false, // Do not enforce Yoda style (add unit tests instead...)
        'ordered_imports' => true,
        'header_comment' => array(
            'header' => <<<EOF
This file is part of the OpenapiBundle package.

(c) Niels Nijens <nijens.niels@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
        )
    ))
    ->setFinder($finder);
