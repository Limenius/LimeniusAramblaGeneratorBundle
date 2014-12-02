<?php

namespace Limenius\Bundle\AramblaGeneratorBundle\Tests\Generator;


use Symfony\Component\Filesystem\Filesystem;

use Limenius\Bundle\AramblaGeneratorBundle\Generator\DoctrineEntityGenerator;

class DoctrineGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $filesystem;
    protected $tmpDir;

    const FORMAT_XML = 'xml';
    const FORMAT_YAML = 'yml';
    const FORMAT_ANNOTATION = 'annotation';

    const WITH_REPOSITORY = true;
    const WITHOUT_REPOSITORY = false;

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/sf2';
        $this->filesystem = new Filesystem();
        $this->filesystem->remove($this->tmpDir);
    }

    protected function generate($format, $with_repository = false, $schema)
    {
        $this->getGenerator()->generate($this->getBundle(), 'Foo', $format, $schema, $with_repository);
    }

    protected function getGenerator()
    {
        $generator = new DoctrineEntityGenerator($this->filesystem, $this->getRegistry());
        return $generator;
    }

    public function getRegistry()
    {
        $registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $registry->expects($this->any())->method('getManager')->will($this->returnValue($this->getManager()));
        $registry->expects($this->any())->method('getAliasNamespace')->will($this->returnValue('Foo\\BarBundle\\Entity'));
        return $registry;
    }


    public function getManager()
    {
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($this->getConfiguration()));
        return $manager;
    }

    
    public function getConfiguration()
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->any())->method('getEntityNamespaces')->will($this->returnValue(array('Foo\\BarBundle')));
        return $config;
    }


    protected function assertAttributesAndMethodsExists(array $otherStrings = array())
    {
        $content = file_get_contents($this->tmpDir.'/Entity/Foo.php');
        $strings = array(
            'namespace Foo\\BarBundle\\Entity',
            'class Foo',
            'private $id',
            'private $title',
            'private $artist',
            'public function getId',
            'public function getTitle',
            'public function getArtist',
            'public function setTitle',
            'public function setArtist',
        );
        $strings = array_merge($strings, $otherStrings);
        foreach ($strings as $string) {
            $this->assertContains($string, $content);
        }
    }

    protected function assertFilesExists(array $files)
    {
        foreach ($files as $file) {
            $this->assertTrue(file_exists($this->tmpDir.'/'.$file), sprintf('%s has been generated', $file));
        }
    }

    protected function getBundle()
    {
        $bundle = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle->expects($this->any())->method('getPath')->will($this->returnValue($this->tmpDir));
        $bundle->expects($this->any())->method('getName')->will($this->returnValue('FooBarBundle'));
        $bundle->expects($this->any())->method('getNamespace')->will($this->returnValue('Foo\BarBundle'));
        return $bundle;
    }

    public function testGenerateAnnotation()
    {
        $schema = [
                        'type' => 'object',
                        'description' => 'A canonical song',
                        'properties' => [
                            'title' =>  [ 'type' => 'string' ],
                            'artist' => [ 'type' => 'string' ]
                        ],
                        'required' => [
                            'title', 'artist'
                        ]
                    ] ;

        $this->generate(self::FORMAT_ANNOTATION, self::WITHOUT_REPOSITORY, $schema);
        $files = array(
            'Entity/Foo.php',
        );
        $annotations = array(
            '@ORM\Column(name="title"',
            '@ORM\Column(name="artist"',
        );
        $this->assertFilesExists($files);
        $this->assertAttributesAndMethodsExists($annotations);
    }

    public function tearDown()
    {
        $this->filesystem->remove($this->tmpDir);
    }
}
