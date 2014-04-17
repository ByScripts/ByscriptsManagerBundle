<?php

namespace Byscripts\Bundle\ManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('byscripts_manager');

        $rootNode
            ->children()
            ->arrayNode('exceptions')
            ->treatNullLike(['\Exception'])
            ->prototype('scalar')
            ->beforeNormalization()
            ->always(function($value){

                    if (!class_exists($value)) {
                        throw new \Exception('Class not found:' . $value);
                    }

                    $reflection = new \ReflectionClass($value);
                    $instance = $reflection->newInstance();

                    if (!$instance instanceof \Exception) {
                        throw new \Exception('Class ' . $value . ' must extends \Exception');
                    }

                    return $value;
                })
        ;


        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
