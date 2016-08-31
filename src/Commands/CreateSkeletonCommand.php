<?php
/**
 * @author: José Luis Salinas
 * @package: CreateSkeletonPlugin
 */

namespace JLSalinas\Composer\CreateSkeleton\Commands;

use Composer\Command\BaseCommand;
use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;


class CreateSkeletonCommand extends BaseCommand
{

    private $runScripts = false;
    private static $kernelManipulator;
    private static $rootDir = '';

    public function __construct()
    {
        require_once self::getRootDir().'vendor/autoload.php';
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('create-skeleton')
            ->setDescription('Create new empty project from thephpleague/skeleton into given directory.')
            ->setDefinition([
                new InputArgument('directory', InputArgument::REQUIRED, 'Directory where the files will be created'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO();
        
        $directory = $input->getOption('directory');
        if (!$directory) {
            $io->writeError('<warning>You did not provide a value for argument "directory". Using default "new-skeleton".</warning>');
            $directory = "new-skeleton";
        } elseif (!preg_match('/^\w[\w\-]*$/', $directory)) {
            $io->writeError('<error>Argument "directory" contains invalid characters. Valid regex: "/^\w[\w\-]*$/".</error>');
            exit 1;
        }
        var_dump('$directory');
        var_dump($directory);
        
        if (!self::clonerepo('thephpleague/skeleton', $directory)) {
            $io->writeError("<error>Error cloning GitHub repository thephpleague/skeleton into $directory.</error>");
            exit 2;
        }
        
        $ret = self::prefill($directory);
        if ($ret === true) {
            echo "Done.\n";
        } else {
            $io->writeError("<error>Error replacing placeholders: $ret.</error>");
            exit 3;
        }
    }
    
    const COL_DESCRIP = 0;
    const COL_DEFAULT = 1;
    const COL_EMPTY =   2;

    const FIELDS = [
        'author_name' =>            ['Your name',                                                        '', false],
        'author_github_username' => ['Your Github username (<username> in https://github.com/username)', '', false],
        'author_email' =>           ['Your email address',                                               '{author_github_username}@example.com', true],
        'author_twitter' =>         ['Your twitter username',                                            '@{author_github_username}', true],
        'author_website' =>         ['Your website',                                                     'https://github.com/{author_github_username}', true],

        'package_vendor' =>         ['Package vendor (<vendor> in https://github.com/vendor/package)',   '{author_github_username}', false],
        'package_name' =>           ['Package name (<package> in https://github.com/vendor/package)',    '', false],
        'package_description' =>    ['Package very short description',                                   '', false],
    
        'psr4_namespace' =>         ['PSR-4 namespace (usually, Vendor\\Package)',                       '{package_vendor}\\{package_name}', false],
    ];
    
    const REPLACEMENTS = [
        ':vendor\\\\:package_name\\\\' => function ($values) { return str_replace('\\', '\\\\', $values['psr4_namespace']) . '\\\\'; },
        ':author_name'                 => function ($values) { return $values['author_name']; },
        ':author_username'             => function ($values) { return $values['author_github_username']; },
        ':author_website'              => function ($values) { return $values['author_website'] ?: ('https://github.com/' . $values['author_github_username']); },
        ':author_email'                => function ($values) { return $values['author_email'] ?: ($values['author_github_username'] . '@example.com'); },
        ':vendor'                      => function ($values) { return $values['package_vendor']; },
        ':package_name'                => function ($values) { return $values['package_name']; },
        ':package_description'         => function ($values) { return $values['package_description']; },
        'League\\Skeleton'             => function ($values) { return $values['psr4_namespace']; },
    ];
    
    public static function readFromConsole ($prompt)
    {
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
    
    public static function interpolate($text, $values)
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
    
    public static function input ($prompt, $end = ':', $options = [], $default = '', $allow_empty = true)
    {
        $text = $prompt;
        
        if ( is_string($options) ) {
            $options = [$options];
        }
        if ( count($options) > 0 ) {
            $text .= ' [' . implode('/', $options) . ']'; 
        }
        $text .= $end . ' ';
        
        do {
            $input = readFromConsole($text);
            if ( empty($input) ) {
                $input = $default;
            }
        } while ( !$allow_empty && empty($input) );
        
        return $input;
    }
    
    public static function askForValues ($fields)
    {
        $values = [];
        
        foreach ($fields as $f => $field) {
            $default = isset($field[self::COL_DEFAULT]) ? self::interpolate($field[self::COL_DEFAULT], $values): '';
            $values[$f] = self::input($field[self::COL_DESCRIP], ':', $default != '' ? [$default] : [], $default, $field[self::COL_EMPTY]);
        }
        
        return $values;
    }

    protected static function prefill ($dir)
    {
        $modify = 'n';
        do {
            if ($modify == 'q') {
                exit;
            }
            
            $values = [];
            
            echo "----------------------------------------------------------------------\n";
            echo "Please, provide the following information:\n";
            echo "----------------------------------------------------------------------\n";
            $values = askForValues(self::FIELDS);
            echo "\n";
            
            echo "----------------------------------------------------------------------\n";
            echo "Please, check that everything is correct:\n";
            echo "----------------------------------------------------------------------\n";
            foreach (self::FIELDS as $f => $field) {
                echo $field[self::COL_DESCRIP] . ": $values[$f]\n";
            }
            echo "\n";
            
            $modify = strtolower(self::input('Modify files with these values', '?', ['y','N','q'], 'n', false));
        } while ($modify != 'y');
        echo "\n";
        
        exit;
        
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
        
        return true;
    }
    
    protected static function clonerepo ($repo, $dir)
    {
        $cmd = "pwd";
        $output = null;
        $retcode = null;
        exec($cmd, $output, $retcode);
        var_dump('$cmd');
        var_dump($cmd);
        var_dump('$output');
        var_dump($output);
        var_dump('$retcode');
        var_dump($retcode);
        
        $cmd = "git clone https://github.com/$repo.git $dir";
        $output = null;
        $retcode = null;
        exec($cmd, $output, $retcode);
        var_dump('$cmd');
        var_dump($cmd);
        var_dump('$output');
        var_dump($output);
        var_dump('$retcode');
        var_dump($retcode);
        
        return $retcode == 0;
    }
    
}
