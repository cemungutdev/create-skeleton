<?php
/**
 * @author: José Luis Salinas
 * @package: CreateSkeletonPlugin
 */

namespace JLSalinas\Composer\CreateSkeleton;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class CreateSkeletonPlugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function getCapabilities()
    {
        return array(
            'Composer\Plugin\Capability\CommandProvider' => 'JLSalinas\Composer\CreateSkeleton\CommandProvider'
        );
    }
}