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
                new InputArgument('directory', InputArgument::OPTIONAL, 'Directory where the files should be created'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // var_dump($input->getArgument('package'));
        var_dump('directory');
        var_dump($input->getArgument('directory'));
    }
    
}