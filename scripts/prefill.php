<?php
define('PROJECT_DIR', getcwd() . '/');

define('COL_DESCRIP', 0);
define('COL_DEFAULT', 1);
define('COL_EMPTY',   2);

$fields = [
    'author_name' =>            ['Your name',                                                        '', false],
    'author_github_username' => ['Your Github username (<username> in https://github.com/username)', '', false],
    'author_email' =>           ['Your email address',                                               '{author_github_username}@example.com', true],
    //'author_twitter' =>         ['Your twitter username',                                            '@{author_github_username}', true],
    'author_website' =>         ['Your website',                                                     'https://github.com/{author_github_username}', true],

    'package_vendor' =>         ['Package vendor (<vendor> in https://github.com/vendor/package)',   '{author_github_username}', false],
    'package_name' =>           ['Package name (<package> in https://github.com/vendor/package)',    '', false],
    'package_description' =>    ['Package very short description',                                   '', false],
    
    'psr4_namespace' =>         ['PSR-4 namespace (usually, Vendor\\Package)',                       '{package_vendor}\\{package_name}', false],
];

$values = [];

$replacements = [
    ':vendor\\\\:package_name\\\\' => function () use(&$values) { return str_replace('\\', '\\\\', $values['psr4_namespace']) . '\\\\'; },
    ':author_name'                 => function () use(&$values) { return $values['author_name']; },
    ':author_username'             => function () use(&$values) { return $values['author_github_username']; },
    ':author_website'              => function () use(&$values) { return $values['author_website'] ?: ('https://github.com/' . $values['author_github_username']); },
    ':author_email'                => function () use(&$values) { return $values['author_email'] ?: ($values['author_github_username'] . '@example.com'); },
    ':vendor'                      => function () use(&$values) { return $values['package_vendor']; },
    ':package_name'                => function () use(&$values) { return $values['package_name']; },
    ':package_description'         => function () use(&$values) { return $values['package_description']; },
    'League\\Skeleton'             => function () use(&$values) { return $values['psr4_namespace']; },
];

function read_from_console ($prompt) {
    if ( function_exists('readline') ) {
        $line = trim(readline($prompt));
        if (!empty($line)) {
            readline_add_history($line);
        }
    } else {
        echo $prompt;
        $line = trim(fgets(STDIN));
    }
    return $line;
}

function interpolate($text, $values)
{
    if (!preg_match_all('/\{(\w+)\}/', $text, $m)) {
        return $text;
    }
    foreach ($m[0] as $k => $str) {
        $f = $m[1][$k];
        $text = str_replace($str, $values[$f], $text);
    }
    return $text;
}

function input ($prompt, $end = ':', $options = [], $default = '', $allow_empty = true) {
    $text = $prompt;
    
    if ( is_string($options) ) {
        $options = [$options];
    }
    if ( count($options) > 0 ) {
        $text .= ' [' . implode('/', $options) . ']'; 
    }
    $text .= $end . ' ';
    
    do {
        $input = read_from_console($text);
        if ( empty($input) ) {
            $input = $default;
        }
    } while ( !$allow_empty && empty($input) );
    
    return $input;
}

function ask_for_values ($fields) {
    $values = [];
    
    foreach ($fields as $f => $field) {
        $default = isset($field[COL_DEFAULT]) ? interpolate($field[COL_DEFAULT], $values): '';
        $values[$f] = input($field[COL_DESCRIP], ':', $default != '' ? [$default] : [], $default, $field[COL_EMPTY]);
    }
    
    return $values;
}

$modify = 'n';
do {
    if ($modify == 'q') {
        exit;
    }
    
    $values = [];
    
    echo "----------------------------------------------------------------------\n";
    echo "Please, provide the following information:\n";
    echo "----------------------------------------------------------------------\n";
    $values = ask_for_values($fields);
    echo "\n";
    
    echo "----------------------------------------------------------------------\n";
    echo "Please, check that everything is correct:\n";
    echo "----------------------------------------------------------------------\n";
    foreach ($fields as $f => $field) {
        echo $field[COL_DESCRIP] . ": $values[$f]\n";
    }
    echo "\n";
    
    $modify = strtolower(input('Modify files with these values', '?', ['y','N','q'], 'n', false));
} while ($modify != 'y');
echo "\n";

$files = array_merge(
    glob(PROJECT_DIR . '*.md'),
    glob(PROJECT_DIR . 'composer.json'),
    glob(PROJECT_DIR . 'src/*.php')
);
foreach ($files as $f) {
    $contents = file_get_contents($f);
    foreach ($replacements as $str => $func) {
        $contents = str_replace($str, $func(), $contents);
    }
    file_put_contents($f . '.new', $contents);
}

echo "Done.\n";
