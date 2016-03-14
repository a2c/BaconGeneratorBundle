<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bacon\Bundle\GeneratorBundle\Manipulator;

use Symfony\Component\DependencyInjection\Container;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator;
use Symfony\Component\Yaml\Yaml;
use Sensio\Bundle\GeneratorBundle\Manipulator\Manipulator;

/**
 * Changes the PHP code of a YAML routing file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RoutingRestManipulator extends Manipulator
{
    private $file;

    /**
     * Constructor.
     *
     * @param string $file The YAML routing file path
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Adds a routing resource at the top of the existing ones.
     *
     * @param string $bundle
     * @param string $format
     * @param string $prefix
     * @param string $path
     *
     * @return bool true if it worked, false otherwise
     *
     * @throws \RuntimeException If bundle is already imported
     */
    public function addResource($bundle, $format, $prefix = '/', $path = 'routing')
    {
        $current = '';
        $code = sprintf("%s:\n", $this->getImportedResourceYamlKey($bundle, $prefix));

        if (file_exists($this->file)) {
            $current = file_get_contents($this->file);

            // Don't add same bundle twice
            if (false !== strpos($current, '@'.$bundle)) {
                throw new \RuntimeException(sprintf('Bundle "%s" is already imported.', $bundle));
            }
        } elseif (!is_dir($dir = dirname($this->file))) {
            mkdir($dir, 0777, true);
        }

        if ('annotation' == $format) {
            $code .= sprintf("    resource: \"@%s/Controller/\"\n    type:     annotation\n", $bundle);
        } else {
            $code .= sprintf("    resource: \"@%s/Resources/config/%s.%s\"\n", $bundle, $path, $format);
        }
        $code .= sprintf("    prefix:   %s\n", $prefix);
        $code .= "\n";
        $code .= $current;

        if (false === file_put_contents($this->file, $code)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the routing file contain a line for the bundle.
     *
     * @param string $bundle
     *
     * @return bool
     */
    public function hasResourceInAnnotation($bundle)
    {
        if (!file_exists($this->file)) {
            return false;
        }

        $config = Yaml::parse(file_get_contents($this->file));

        $search = sprintf('@%s/Controller/', $bundle);

        foreach ($config as $resource) {
            if (array_key_exists('resource', $resource)) {
                return $resource['resource'] === $search;
            }
        }

        return false;
    }

    /**
     * Add an annotation controller resource.
     *
     * @param string $bundle
     * @param string $controller
     *
     * @return bool
     */
    public function addAnnotationController($bundle, $controller)
    {
        $current = '';

        if (file_exists($this->file)) {
            $current = file_get_contents($this->file);
        } elseif (!is_dir($dir = dirname($this->file))) {
            mkdir($dir, 0777, true);
        }

        $code = sprintf("%s:\n", Container::underscore(substr($bundle, 0, -6)).'_'.Container::underscore($controller));

        $code .= sprintf("    resource: \"@%s/Controller/Rest/%sController.php\"\n    type:     rest\n    name_prefix:     api_%s\n    prefix:     %s\n", $bundle, $controller, strtolower($controller), strtolower($controller));

        $code .= "\n";
        $code .= $current;

        return false !== file_put_contents($this->file, $code);
    }

    public function getImportedResourceYamlKey($bundle, $prefix)
    {
        $snakeCasedBundleName = Container::underscore(substr($bundle, 0, -6));
        $routePrefix = DoctrineCrudGenerator::getRouteNamePrefix($prefix);

        return sprintf('%s%s%s', $snakeCasedBundleName, '' !== $routePrefix ? '_' : '' , $routePrefix);
    }
}
