<?php

namespace Vietanywhere\UseCase\CreateCleanStructure\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Vietanywhere\UseCase\CreateCleanStructure\Helpers\Tools;
use Vietanywhere\UseCase\CreateCleanStructure\Templates\TemplateBuilder;

class UseCaseCreateStructure extends SymfonyCommand
{
    /**
     *
     */
    protected function configure() {
        $this->setName('usecase:create:structure');
        $this->setDescription('Create all the files related to the creation of a new usecase (usecase, presenter)');

        $this->addOption('core-path', 'c', InputOption::VALUE_OPTIONAL, 'path of your core where the domain will be created');
        $this->addOption('prefix-namespace', 'p', InputOption::VALUE_OPTIONAL, 'Prefix of the namespace of the Domaine');
    }

    // Typage à partir de 7.4
    /** @var  InputInterface */
    private $input;

    /** @var OutputInterface */
    private $ouput;

    /** @var string */
    private $basepath;

    /** @var string */
    private $prefixNamespace;


    private const __STRUCTURE__DIR_DOMAIN= 'Domains';
    private const __STRUCTURE__DIR_SUB_DOMAIN_NAME = 'SubDomains';
    private const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_NAME = 'UseCase';

    private const __STRUCTURE__DOMAIN_STRUCTURE_DIR = ['Entity', 'Model'];
    private const __STRUCTURE__SUB_DOMAIN_STRUCTURE_DIR = ['_ViewModel', 'Model'];

    private const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_PRESENTATION_NAME = 'Presentation';
    private const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_PRESENTATION_VIEWMODEL_NAME = '_ViewModel';
    private const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_MODEL_NAME = 'Model';

    /**
     * Obtenir la liste de dossiers existants
     * @param string $fromRelative
     * @return array
     */
    private function ListeExistante(string $fromRelative) : array
    {
        $result = [];
        $dir = $this->getFromDirRootWWW($fromRelative);
        if (is_dir($dir)) {
            $liste_rep = scandir($dir);
            foreach ($liste_rep as $rep) {
                if (!in_array($rep, ['.', '..'])) {
                    $result[] = $rep;
                }
            }
        }

        return $result;
    }

    /**
     * @review la méthode Tools::createFileFromContent va s'occuper de créer l'arborescence des dossier lorsque tu génère une classe. Tu n'as donc pas besoin de gérer l'arborescence des dossier dans cette commande.
     * C'est pour générer les répertoires communes aux domaines/sous-domaine.
     *
     * @param $domainPathRelative
     */
    private function createDirectory($domainPathRelative)
    {
        $fullPath = $this->getFromDirRootWWW($domainPathRelative);

        Tools::createDirectory($fullPath);
    }

    /**
     * Creation des dossiers ncessaires au domaine
     *
     * @param string $domainPathRelative
     */
    private function generateStructureDomain(string $domainPathRelative)
    {
        $this->createDirectory($domainPathRelative);
        $this->createDirectory($domainPathRelative . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_NAME);
        foreach (static::__STRUCTURE__DOMAIN_STRUCTURE_DIR as $dir) {
            $this->createDirectory($domainPathRelative. '/' . $dir);
        }
    }

    /**
     * Creation des dossiers ncessaires au sub domaine
     *
     * @param string $pathRelative
     */
    private function generateStructureSubDomain(string $pathRelative)
    {
        $this->createDirectory($pathRelative);
        $this->createDirectory($pathRelative . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_USECASE_NAME);
        foreach (static::__STRUCTURE__SUB_DOMAIN_STRUCTURE_DIR as $dir) {
            $this->createDirectory($pathRelative. '/' . $dir);
        }
    }

    /**
     * @param string $content
     * @param string $fileName
     * @param string $dir
     * @return string
     */
    private function createFileIfNotExists(string $content, string $fileName, string $dir) : string
    {
        $fileName = $fileName . '.php';
        if (!file_exists($dir . '/'. $fileName)) {
            Tools::createFileFromContent($content, $fileName, $dir);
            echo "Creation nouvelle class : {$fileName}\n"; //@review il faudrait utilise l'output fournie par la Command parente
        }
        echo "Class {$dir}/{$fileName} : ok \n";
        return $fileName;
    }

    /**
     * @param $content
     * @param $className
     * @param $pathRelative
     * @return array
     */
    private function writeClassPhpBase($content, $className, $pathRelative) : array
    {
        $dir = $this->getFromDirRootWWW($pathRelative);
        $namespace = ucfirst($pathRelative);
        $content = str_replace('%%namespace%%',  Tools::transformPathToNamespace($this->prefixNamespace . $namespace), $content);
        $content = str_replace('%%ClassName%%', $className, $content);

        foreach ([
                     'ModelClass' => $this->ModelClass,
                     'ViewModelClass' => $this->ViewModelClass,
                     'UseCaseOutputClass' => $this->UseCaseOutputClass,
                     'PresenterContratcClass' => $this->PresenterContratcClass,
                     'VueViewModelClass' => $this->VueViewModelClass,
                     'ViewModelFactoryClass' => $this->ViewModelFactoryClass,
                     'UseCaseInputClass' => $this->UseCaseInputClass
                 ]
        as $name => $item
        ) {
            if (!empty($item)) {
                $content = str_replace("%%{$name}Name%%", $item['className'], $content);
                $content = str_replace("%%{$name}Namespace%%", Tools::transformPathToNamespace($this->prefixNamespace . $item['namespace']), $content);
                $content = str_replace("%%{$name}NamespaceUse%%", Tools::transformPathToNamespace($this->prefixNamespace . $item['namespace'] . '/' . $item['className']), $content);
            }
        }

        static::createFileIfNotExists($content, $className, $dir);
        return ['namespace' => $namespace, 'className' => $className];
    }

    private $ModelClass = null;
    private $UseCaseInputClass = null;
    private $UseCaseOutputClass = null;
    private $ViewModelClass = null;
    private $ViewModelFactoryClass = null;
    private $PresenterContratcClass = null;
    private $VueViewModelClass = null;
    private $VuePresenterClass = null;
    private $UseCaseClass = null;


    /**
     * @param string $pathRelativeUsecase
     * @param string $useCaseName
     * @throws \Exception
     */
    private function generateStructureSubDomainUseCase(string $pathRelativeUsecase, string $useCaseName)
    {
        // Creation des dossier
        $this->createDirectory($pathRelativeUsecase);

        $pathRelativeUsecaseModel = $pathRelativeUsecase . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_USECASE_MODEL_NAME;
        $this->createDirectory($pathRelativeUsecaseModel);


        $pathRelativeUsecasePresentation = $pathRelativeUsecase . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_USECASE_PRESENTATION_NAME;
        $this->createDirectory($pathRelativeUsecasePresentation);

        $pathRelativeUsecasePresentationViewModel = $pathRelativeUsecasePresentation . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_USECASE_PRESENTATION_VIEWMODEL_NAME;
        $this->createDirectory($pathRelativeUsecasePresentationViewModel);


        $this->ModelClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_MODEL__, $useCaseName, $pathRelativeUsecaseModel);
        $this->UseCaseInputClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_INPUT__, $useCaseName, $pathRelativeUsecase);
        $this->UseCaseOutputClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_OUTPUT__, $useCaseName, $pathRelativeUsecase);
        $this->ViewModelClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_PRESENTATION_PRESENTER_VIEW_MODEL__, $useCaseName, $pathRelativeUsecasePresentationViewModel);
        $this->ViewModelFactoryClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_PRESENTATION_PRENSENTER_VLEW_MODEL_FACTORY__, $useCaseName, $pathRelativeUsecasePresentationViewModel);
        $this->PresenterContratcClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_PRESENTATION_PRESENTER_CONTRAT__ ,$useCaseName, $pathRelativeUsecasePresentation);
        $this->VueViewModelClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_PRESENTATION_VUE_VIEW_MODEL__, $useCaseName, $pathRelativeUsecasePresentation);
        $this->VuePresenterClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE_PRESENTATION_VUE_PRESENTER__, $useCaseName, $pathRelativeUsecasePresentation);
        $this->UseCaseClass = $this->generateUseCaseClassTemplate(TemplateBuilder::__USE_CASE__, $useCaseName, $pathRelativeUsecase);
    }


    /**
     * @param array $template
     * @param string $useCaseName
     * @param string $pathRelative
     * @return array
     * @throws \Exception
     */
    private function generateUseCaseClassTemplate(array $template, string $useCaseName, string $pathRelative) : array {
        $data = TemplateBuilder::buildTemplateClass($template, $useCaseName);

        if (empty($data)) {
            throw new \Exception("Template manquante !");
        }

        return $this->writeClassPhpBase($data['content'], $data['className'], $pathRelative);
    }

    /**
     * @return string
     */
    private function getBasePath() : string {
        return $this->basepath;
    }

    /**
     * @param $domainPathRelative
     * @return string
     */
    private function getFromDirRootWWW($domainPathRelative) : string
    {
        return  $this->getBasePath() . '/' . $domainPathRelative;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->ouput = $output;

        $this->basepath = $input->getOption('core-path');
        $this->basepath = empty($this->basepath) ? getcwd()  : $this->basepath;
        $this->prefixNamespace = $input->getOption('prefix-namespace');
        $this->prefixNamespace = empty($this->prefixNamespace) ? '' : ucfirst($this->prefixNamespace) . '/';

        $this->createDirectory(static::__STRUCTURE__DIR_DOMAIN);

        $domainesExistants = $this->ListeExistante(static::__STRUCTURE__DIR_DOMAIN);
        // Gestion du domaine
        $domain = $this->anticipate('Domaine ? (vide pour arreter) : ', $domainesExistants);
        $domain = ucfirst($domain);
        $domainPathRelative = static::__STRUCTURE__DIR_DOMAIN . '/' . $domain;
        if (empty($domain)) {
            return 0;
        }
        $this->generateStructureDomain($domainPathRelative);

        $SubdomainesExistants = $this->ListeExistante($domainPathRelative . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_NAME);
        $subDomain = $this->anticipate('Sous-Domaine ? (vide pour arreter) : ', $SubdomainesExistants);
        $subDomain = ucfirst($subDomain);
        $subDomainPathRelative = $domainPathRelative . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_NAME . '/' . $subDomain;
        if (empty($subDomain)) {
            return 0;
        }
        $this->generateStructureSubDomain($subDomainPathRelative);

        do {
            $useCase = $this->anticipate('Use-Case ? (vide pour arreter) : ', []);
            if (empty($useCase)) {
                return 0;
            }

            $useCase = ucfirst($useCase);
            $useCaseSubDomainPathRelative = $subDomainPathRelative . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_USECASE_NAME . '/' . $useCase;
            $this->generateStructureSubDomainUseCase($useCaseSubDomainPathRelative, $useCase);
        } while (!empty($useCase));
    }


    /**
     * @param string $question
     * @param array $choices
     * @return string|null
     */
    private function anticipate(string $question, array $choices) {

        // TODO : Gestion de l'autocomplétion $choices
        $helper = $this->getHelper('question');
        $question = new Question($question);
        $answer = $helper->ask($this->input, $this->ouput, $question);
        return $answer;
    }

}
