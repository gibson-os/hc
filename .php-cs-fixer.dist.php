<?php
declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'align_multiline_comment' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_before_statement' => true,
        'concat_space' => ['spacing' => 'one'],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_type_declaration' => true,
        'global_namespace_import' => true,
        'heredoc_to_nowdoc' => true,
        'is_null' => true,
        'list_syntax' => ['syntax' => 'long'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
        'no_null_property_initialization' => true,
        'echo_tag_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_braces' => true,
        'no_unneeded_final_method' => true,
        'no_unreachable_default_argument_value' => false,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => true,
        'phpdoc_to_comment' => false,
        'php_unit_strict' => false,
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_no_package' => false,
        'phpdoc_order' => true,
        'protected_to_private' => false,
        'semicolon_after_instruction' => true,
        'single_line_comment_style' => true,
        'strict_comparison' => false,
        'strict_param' => false,
        'yoda_style' => false,
        'class_attributes_separation' => true,
        'declare_strict_types' => true,
        'blank_line_after_opening_tag' => false,
        'no_extra_blank_lines' => true,
        'single_line_throw' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . DIRECTORY_SEPARATOR . 'src',
                __DIR__ . DIRECTORY_SEPARATOR . 'tests',
            ])
            ->name('*.php'),
    )
;
