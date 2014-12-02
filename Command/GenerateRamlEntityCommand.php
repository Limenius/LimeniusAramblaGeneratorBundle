<?php

namespace Limenius\Bundle\AramblaGeneratorBundle\Command;

use Limenius\Bundle\AramblaGeneratorBundle\Generator\DoctrineEntityGenerator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Doctrine\DBAL\Types\Type;
use Sensio\Bundle\GeneratorBundle\Command\Validator;

class GenerateDoctrineEntityCommand extends GenerateDoctrineCommand
{
    protected function configure()
    {
        $this
            ->setName('arambla:generate:entity')
            ->setDescription('Generates a new Doctrine entity inside a bundle from a RAML file')
            ->addOption('raml_file', null, InputOption::VALUE_REQUIRED, 'The RAML file to read schemas from')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'The Bundle where to generate the entity')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)', 'annotation')
            ->addOption('with-repository', null, InputOption::VALUE_NONE, 'Whether to generate the entity repository or not')
            ->setHelp(<<<EOT
The <info>arambla:generate:entity</info> task generates a new Doctrine
entity inside a bundle from a RAML file:
<info>php app/console arambla:generate:entity --raml_file=apispec.raml --bundle=AcmeBlogBundle:Blog</info>
The above command would initialize a new entity in the following bundle
namespace <info>Acme\BlogBundle\Entity\Blog</info>.
You can also optionally specify the fields you want to generate in the new
entity:
The command can also generate the corresponding entity repository class with the
<comment>--with-repository</comment> option:
<info>php app/console arambla:generate:entity --raml_file=apispec.raml --bundle=AcmeBlogBundle:Blog --with-repository</info>
By default, the command uses annotations for the mapping information; change it
with <comment>--format</comment>:
<info>php app/console arambla:generate:entity --raml_file=apispec.raml --bundle=AcmeBlogBundle:Blog --format=yml</info>
To deactivate the interaction mode, simply use the `--no-interaction` option
without forgetting to pass all needed options:
<info>php app/console arambla:generate:entity --raml_file=apispec.raml --bundle=AcmeBlogBundle:Blog -format=annotation --with-repository --no-interaction</info>
EOT
        );
    }

    /**
     * @throws \InvalidArgumentException When the bundle doesn't end with Bundle (Example: "Bundle/MySampleBundle")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        if ($input->isInteractive()) {
            if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
                $output->writeln('<error>Command aborted</error>');
                return 1;
            }
        }
        $entity = Validators::validateBundleNamespace($input->getOption('bundle'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);
        $format = Validators::validateFormat($input->getOption('format'));
        $dialog->writeSection($output, 'Entity generation');
        $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);
        $generator = $this->getGenerator();
        $generator->generate($bundle, $entity, $format, array_values($fields), $input->getOption('with-repository'));
        $output->writeln('Generating the entity code: <info>OK</info>');
        $dialog->writeGeneratorSummary($output, array());
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();
        $dialog->writeSection($output, 'Welcome to the Arambla Doctrine2 entity generator');
        // namespace
        $output->writeln(array(
            '',
            'This command helps you generate Doctrine2 entities.',
            '',
            'First, you need to give the entity name you want to generate.',
            'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>.',
            '',
        ));
        $bundleNames = array_keys($this->getContainer()->get('kernel')->getBundles());
        while (true) {
            $entity = $dialog->askAndValidate($output, $dialog->getQuestion('The Entity shortcut name', $input->getOption('entity')), array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'), false, $input->getOption('entity'), $bundleNames);
            list($bundle, $entity) = $this->parseShortcutNotation($entity);
            // check reserved words
            if ($this->getGenerator()->isReservedKeyword($entity)) {
                $output->writeln(sprintf('<bg=red> "%s" is a reserved word</>.', $entity));
                continue;
            }
            try {
                $b = $this->getContainer()->get('kernel')->getBundle($bundle);
                if (!file_exists($b->getPath().'/Entity/'.str_replace('\\', '/', $entity).'.php')) {
                    break;
                }
                $output->writeln(sprintf('<bg=red>Entity "%s:%s" already exists</>.', $bundle, $entity));
            } catch (\Exception $e) {
                $output->writeln(sprintf('<bg=red>Bundle "%s" does not exist.</>', $bundle));
            }
        }
        $input->setOption('entity', $bundle.':'.$entity);
        // format
        $output->writeln(array(
            '',
            'Determine the format to use for the mapping information.',
            '',
        ));
        $formats = array('yml', 'xml', 'php', 'annotation');
        $format = $dialog->askAndValidate($output, $dialog->getQuestion('Configuration format (yml, xml, php, or annotation)', $input->getOption('format')), array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateFormat'), false, $input->getOption('format'), $formats);
        $input->setOption('format', $format);
        // repository?
        $output->writeln('');
        $withRepository = $dialog->askConfirmation($output, $dialog->getQuestion('Do you want to generate an empty repository class', $input->getOption('with-repository') ? 'yes' : 'no', '?'), $input->getOption('with-repository'));
        $input->setOption('with-repository', $withRepository);
        // summary
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
            '',
            sprintf("You are going to generate a \"<info>%s:%s</info>\" Doctrine2 entity", $bundle, $entity),
            sprintf("using the \"<info>%s</info>\" format.", $format),
            '',
        ));
    }

    protected function createGenerator()
    {
        return new DoctrineEntityGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('doctrine'));
    }
}
