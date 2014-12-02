<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Limenius\Bundle\AramblaGeneratorBundle\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineEntityCommand;
use Sensio\Bundle\GeneratorBundle\Tests\Command\GenerateCommandTest;

class GenerateRamlEntityCommandTest extends GenerateCommandTest
{
    /**
     * @dataProvider getNonInteractiveCommandData
     */
    public function testNonInteractiveCommand($options, $expected)
    {
        list($bundle, $entity, $format, $schema) = $expected;
        $generator = $this->getGenerator();
        $generator
            ->expects($this->once())
            ->method('generate')
            ->with($this->getBundle(), $entity, $format, $schema)
        ;
        $generator
            ->expects($this->any())
            ->method('isReservedKeyword')
            ->will($this->returnValue(false))
        ;
        $tester = new CommandTester($this->getCommand($generator, ''));
        $tester->execute($options, array('interactive' => false));
    }

    public function getNonInteractiveCommandData()
    {
        return array(
            array(array(0 => 'Tests/Command/Fixtures/simple.raml',  array('--bundle' => 'AcmeBlogBundle'), array('--format' => 'annotation')),
            array('AcmeBlogBundle', 'Song', 'annotation', array(
                'type' => 'object',
                'description' => 'A canonical song',
                'properties' => array(
                    'title' => array(
                        'type' => 'string'
                    ),
                    'artist' => array(
                        'type' => 'string'
                    )
                ),
                'required' => array(
                    'title', 'artist'
                )
            )))
        );
    }

    protected function getCommand($generator, $input)
    {
        $command = new GenerateDoctrineEntityCommand();
        $command->setContainer($this->getContainer());
        $command->setHelperSet($this->getHelperSet($input));
        $command->setGenerator($generator);
        return $command;
    }

    protected function getGenerator()
    {
        // get a noop generator
        return $this
            ->getMockBuilder('Limenius\Bundle\AramblaGeneratorBundle\Generator\DoctrineEntityGenerator')
            ->disableOriginalConstructor()
            ->setMethods(array('generate', 'isReservedKeyword'))
            ->getMock()
        ;
    }
}
