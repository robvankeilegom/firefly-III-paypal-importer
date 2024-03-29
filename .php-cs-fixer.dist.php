#!/usr/bin/env php
<?php

$rules = [
    '@PhpCsFixer'            => true,
    'phpdoc_align'           => false,
    'array_indentation'      => true,
    'array_syntax'           => ['syntax' => 'short'],
    'binary_operator_spaces' => [
        'operators' => [
            '=>' => 'align_single_space_minimal',
            '='  => 'align_single_space_minimal',
        ],
    ],
    'blank_line_after_namespace'   => true,
    'blank_line_after_opening_tag' => true,
    'blank_line_before_statement'  => [
        'statements' => [
            'continue', 'declare', 'return', 'throw', 'try', 'do', 'if',
            'switch',
        ],
    ],
    'braces'                      => true,
    'cast_spaces'                 => ['space' => 'single'],
    'class_attributes_separation' => true,
    'class_definition'            => ['single_line' => true],
    'concat_space'                => ['spacing' => 'one'],
    'declare_equal_normalize'     => ['space' => 'none'],
    'elseif'                      => true,
    'encoding'                    => true,
    'full_opening_tag'            => true,
    'function_declaration'        => true,
    'function_typehint_space'     => true,
    'heredoc_to_nowdoc'           => true,
    'include'                     => true,
    'indentation_type'            => true,
    'line_ending'                 => true,
    'linebreak_after_opening_tag' => true,
    'lowercase_cast'              => true,
    // 'lowercase_constants' => true,
    'lowercase_keywords'                     => true,
    'magic_constant_casing'                  => true,
    'method_argument_space'                  => ['on_multiline' => 'ensure_fully_multiline'],
    'method_chaining_indentation'            => true,
    'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    'native_function_casing'                 => true,
    'no_alias_functions'                     => ['sets' => ['@internal', '@IMAP']], // Risky
    'no_blank_lines_after_class_opening'     => true,
    'no_blank_lines_after_phpdoc'            => true,
    'no_closing_tag'                         => true,
    'no_empty_comment'                       => false,
    'no_empty_phpdoc'                        => true,
    'no_empty_statement'                     => true,
    // 'no_extra_blank_lines' => [
    // 'tokens' => [
    // 'break', 'case', 'continue', 'curly_brace_block', 'default', 'extra',
    // 'parenthesis_brace_block', 'square_brace_block', 'throw',
    // 'use', 'useTrait', 'use_trait'
    // ],
    // ],
    'no_leading_import_slash'                     => true,
    'no_leading_namespace_whitespace'             => true,
    'no_mixed_echo_print'                         => ['use' => 'echo'],
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_php4_constructor'                         => true,
    'no_short_bool_cast'                          => true,
    'no_singleline_whitespace_before_semicolons'  => true,
    'no_spaces_after_function_name'               => true,
    'no_spaces_inside_parenthesis'                => true,
    'no_trailing_comma_in_list_call'              => true,
    'no_trailing_comma_in_singleline_array'       => true,
    'no_trailing_whitespace_in_comment'           => true,
    'no_trailing_whitespace'                      => true,
    'no_unneeded_control_parentheses'             => true,
    'no_unused_imports'                           => true,
    'no_useless_else'                             => true,
    'no_useless_return'                           => true,
    'no_whitespace_before_comma_in_array'         => true,
    'no_whitespace_in_blank_line'                 => true,
    'normalize_index_brace'                       => true,
    'not_operator_with_successor_space'           => true,
    'object_operator_without_whitespace'          => true,
    'ordered_imports'                             => ['sort_algorithm' => 'length'],
    'php_unit_strict'                             => true,
    'phpdoc_indent'                               => true,
    'general_phpdoc_tag_rename'                   => true,
    'phpdoc_inline_tag_normalizer'                => true,
    'phpdoc_tag_type'                             => true,
    'phpdoc_no_access'                            => true,
    'phpdoc_no_empty_return'                      => true,
    'phpdoc_no_package'                           => true,
    'phpdoc_no_useless_inheritdoc'                => true,
    'phpdoc_order'                                => true,
    'phpdoc_scalar'                               => [
        'types' => [
            'boolean', 'double', 'integer', 'real', 'str',
        ],
    ],
    'phpdoc_single_line_var_spacing'     => true,
    'phpdoc_summary'                     => true,
    'phpdoc_to_comment'                  => true,
    'phpdoc_trim'                        => true,
    'phpdoc_types'                       => ['groups' => ['simple', 'alias', 'meta']],
    'phpdoc_var_without_name'            => true,
    'self_accessor'                      => true,
    'short_scalar_cast'                  => true,
    'simplified_null_return'             => true,
    'single_blank_line_at_eof'           => true,
    'single_class_element_per_statement' => ['elements' => ['const', 'property']],
    'single_import_per_statement'        => true,
    'single_line_after_imports'          => true,
    'single_line_comment_style'          => true, // defaults comment_types => ['asterisk', 'hash']
    'single_quote'                       => true,
    'space_after_semicolon'              => true,
    'standardize_increment'              => true,
    'standardize_not_equals'             => true,
    'switch_case_semicolon_to_colon'     => true,
    'switch_case_space'                  => true,
    'ternary_operator_spaces'            => true,
    'trailing_comma_in_multiline'        => ['elements' => ['arrays']],
    'trim_array_spaces'                  => true,
    'unary_operator_spaces'              => true,
    'visibility_required'                => ['elements' => ['property', 'method']],
    'whitespace_after_comma_in_array'    => true,
];

$excludes = [
    'bootstrap/cache',
    'node_modules',
    'storage',
    'public',
    'docs',
    'bootstrap',
];

$finder = PhpCsFixer\Finder::create()
    ->exclude($excludes)
    ->in(__DIR__)
    ->notName('*.blade.php')
    ->notName('.phpstorm.meta.php')
    ->notName('_ide_*.php');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder);
