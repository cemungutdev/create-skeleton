<?php
/**
 * @author: José Luis Salinas
 * @package: CreateSkeletonPlugin
 */

namespace JLSalinas\Composer\CreateSkeleton;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [new CreateSkeletonCommand()];
    }
}