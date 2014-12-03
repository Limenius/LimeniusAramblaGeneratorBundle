<?php

namespace Limenius\Bundle\AramblaGeneratorBundle\Command;

use Limenius\Bundle\AramblaGeneratorBundle\Generator\DoctrineEntityGenerator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\DBAL\Types\Type;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Limenius\Arambla\Raml;

class GenerateRamlEntityCommand extends ContainerAwareCommand
{
    protected $generator;

    // only useful for unit tests
    public function setGenerator(DoctrineEntityGenerator $generator)
    {
        $this->generator = $generator;
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');
        if (!$question || get_class($question) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper') {
            $this->getHelperSet()->set($question = new QuestionHelper());
        }

        return $question;
    }

    protected function configure()
    {
        $this
            ->setName('arambla:generate:entity')
            ->setDescription('Generates a new Doctrine entity inside a bundle from a RAML file')
            ->addArgument('raml_file', null, InputArgument::REQUIRED, 'The RAML file to read schemas from')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'The Bundle where to generate the entity')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)', 'annotation')
            ->addOption('with-repository', null, InputOption::VALUE_NONE, 'Whether to generate the entity repository or not')
            ->setHelp(<<<EOT
The <info>arambla:generate:entity</info> task generates a new Doctrine
entity inside a bundle from a RAML file:
<info>php app/console arambla:generate:entity apispec.raml --bundle=AcmeBlogBundle</info>
The above command would initialize a new entity in the following bundle
namespace <info>Acme\BlogBundle\Entity\Blog</info>.
You can also optionally specify the fields you want to generate in the new
entity:
The command can also generate the corresponding entity repository class with the
<comment>--with-repository</comment> option:
<info>php app/console arambla:generate:entity apispec.raml --bundle=AcmeBlogBundle --with-repository</info>
By default, the command uses annotations for the mapping information; change it
with <comment>--format</comment>:
<info>php app/console arambla:generate:entity apispec.raml --bundle=AcmeBlogBundle --format=yml</info>
To deactivate the interaction mode, simply use the `--no-interaction` option
without forgetting to pass all needed options:
<info>php app/console arambla:generate:entity apispec.raml --bundle=AcmeBlogBundle -format=annotation --with-repository --no-interaction</info>
EOT
        );
    }

    /**
     * @throws \InvalidArgumentException When the bundle doesn't end with Bundle (Example: "Bundle/MySampleBundle")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        if ($input->isInteractive()) {
            if (!$questionHelper->ask($input, $output, new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true))) {
                $output->writeln('<error>Command aborted</error>');
                return 1;
            }
        }
        $raml_file = $input->getArgument('raml_file');
        $bundle = Validators::validateBundleName($input->getOption('bundle'));
        $format = Validators::validateFormat($input->getOption('format'));
        $questionHelper->writeSection($output, 'Entity generation');
        $bundle = $this->getContainer()->get('kernel')->getBundle(str_replace('/', '\\', $bundle));
        $generator = $this->getGenerator();
        $questionHelper->writeSection($output, 'OrwhatEntity generation');
        $raml = Raml::load($raml_file);
        $schema = $raml['schemas']['Song'];
        $entity = ucfirst('song');

        $generator->generate($bundle, $entity, $format, $schema, $input->getOption('with-repository'));
        $output->writeln('Generating the entity code: <info>OK</info>');
        $questionHelper->writeGeneratorSummary($output, array());
    }

    protected function getGenerator(BundleInterface $bundle = null)
    {
        if (null === $this->generator) {
            $this->generator = $this->createGenerator();
        }
        return $this->generator;
    }


    protected function createGenerator()
    {
        return new DoctrineEntityGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('doctrine'));
    }
}
