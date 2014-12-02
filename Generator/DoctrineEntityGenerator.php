<?php

namespace Limenius\Bundle\AramblaGeneratorBundle\Generator;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Sensio\Bundle\GeneratorBundle\Generator\DoctrineEntityGenerator as SensioGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bridge\Doctrine\RegistryInterface;




class DoctrineEntityGenerator
{

    private $filesystem;
    private $registry;

    public function __construct(Filesystem $filesystem, RegistryInterface $registry)
    {
        $this->filesystem = $filesystem;
        $this->registry = $registry;

    }

    public function generate(BundleInterface $bundle, $entity, $format, array $schema, $withRepository)
    {
        $fields = array();

        $properties = $schema['properties'];

        if (!isset($properties['id'])) {
            unset($properties['id']);
        }

        foreach ($properties as $field => $specification) {
            $fields[] = array('fieldName' => $field, 'type' => $specification['type'], 'id' => false);
        }

        $generator = new SensioGenerator($this->filesystem, $this->registry);
        $generator->generate($bundle, $entity, $format, $fields, $withRepository);

    }
}
