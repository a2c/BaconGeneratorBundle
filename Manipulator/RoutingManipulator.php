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
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;

/**
 * Changes the PHP code of a YAML routing file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RoutingManipulator extends RoutingManipulator
{
    private $file;
    private $controllerFolder;

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
     * Sets the controller folder.
     *
     * @param string $controllerFolder The configuration controllerFolder
     */
    public function setControllerFolder($controllerFolder)
    {
        $this->controllerFolder = $controllerFolder;
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

        if($this->controllerFolder) {
            $controller = $this->controllerFolder . '/' . $controller;
        }
        
        $code .= sprintf("    resource: \"@%s/Controller/%sController.php\"\n    type:     annotation\n", $bundle, $controller);

        $code .= "\n";
        $code .= $current;

        return false !== file_put_contents($this->file, $code);
    }
}
