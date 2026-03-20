<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true,
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'multiline_whitespace_before_semicolons' => false,
        'echo_tag_syntax' => ['format' => 'long'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'no_useless_else' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'length',
        ],
        'single_quote' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'trim_array_spaces' => true,
    ])
    // 💡 by default, Fixer looks for `*.php` files excluding `./vendor/` - here, you can groom this config
    ->setFinder(
        (new Finder())
            // 💡 root folder to check
            ->in(__DIR__)
            // 💡 additional files, eg bin entry file
            // ->append([__DIR__.'/bin-entry-file'])
            // 💡 folders to exclude, if any
             ->exclude(['vendor'])
            // 💡 path patterns to exclude, if any
            // ->notPath([/* ... */])
            // 💡 extra configs
             ->ignoreDotFiles(true) // true by default in v3, false in v4 or future mode
             ->ignoreVCS(true), // true by default
    )
;
