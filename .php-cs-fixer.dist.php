<?php
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new PhpCsFixer\Config())
    ->setRules(
        [
            '@PSR2' => true,
            '@Symfony' => true,
            'array_syntax' => ['syntax' => 'short'],
            'binary_operator_spaces' => ['operators' => ['=' => 'align', '=>' => 'align']],
            'blank_line_after_opening_tag' => false,
            'cast_spaces' => ['space' => 'none'],
            'concat_space' => ['spacing' => 'one'],
            'class_attributes_separation' => ['elements' => ['method' => 'one', 'property' => 'one']],
            'declare_strict_types' => true,
            'general_phpdoc_annotation_remove' => ['annotations' => ['author']],
            'modernize_types_casting' => true,
            'ordered_class_elements' => [
                'order' => [
                    'use_trait',
                    'case',
                    'constant_public',
                    'constant_protected',
                    'constant_private',
                    'property_public',
                    'property_protected',
                    'property_private',
                    'construct',
                    'destruct',
                    'magic',
                    'phpunit',
                    'method_public',
                    'method_protected',
                    'method_private'
                ]
            ],
            'php_unit_test_annotation' => ['style' => 'annotation'],
            'php_unit_method_casing' => false,
            'phpdoc_to_comment' => false,
            'void_return' => true,
            'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        ]
    )
    ->setRiskyAllowed(true)
    ->setFinder($finder);
