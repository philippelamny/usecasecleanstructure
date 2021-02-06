<?php

namespace Vietanywhere\UseCase\CreateCleanStructure\Commands;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Vietanywhere\UseCase\CreateCleanStructure\Helpers\Tools;

class UseCaseCreateStructure extends SymfonyCommand
{
    /**
     *
     */
    protected function configure() {
        $this->setName('usecase:create:structure');
        $this->setDescription('Create all the files related to the creation of a new usecase (usecase, presenter)');

        $this->addOption('core-path', 'cp', InputOption::VALUE_OPTIONAL, 'path of your core where the domain will be created');
        $this->addOption('prefix-namespace', 'pn', InputOption::VALUE_OPTIONAL, 'Prefix of the namespace of the Domaine');
    }

    private $input;
    private $ouput;
    private $basepath;
    private $prefixNamespace;

    const __STRUCTURE__DIR_DOMAIN= 'Domains';
    const __STRUCTURE__DIR_SUB_DOMAIN_NAME = 'SubDomains';
    const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_NAME = 'UseCase';

    const __STRUCTURE__DOMAIN_STRUCTURE_DIR = ['Entity', 'Model'];
    const __STRUCTURE__SUB_DOMAIN_STRUCTURE_DIR = ['_ViewModel', 'Model'];

    const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_PRESENTATION_NAME = 'Presentation';
    const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_PRESENTATION_VIEWMODEL_NAME = '_ViewModel';
    const __STRUCTURE__DIR_SUB_DOMAIN_USECASE_MODEL_NAME = 'Model';

    const __STRUCTURE__BASE_PATH = '';

    /**
     * Obtenir la liste de dossiers existants
     * @param string $fromRelative
     * @return array
     */
    private function ListeExistante(string $fromRelative) : array
    {
        $result = [];
        $liste_rep = scandir($this->getFromDirRootWWW(static::__STRUCTURE__DIR_DOMAIN . '/' . $fromRelative));
        foreach ($liste_rep as $rep) {
            if (!in_array($rep, ['.', '..'])) {
                $result[] = $rep;
            }
        }

        return $result;
    }

    /**
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
            Tools::createFileFromContent($content, $fileName, $dir, ['php']);
            echo "Creation nouvelle class : {$fileName}\n";
        }
        echo "Class {$dir}/{$fileName} : ok \n";
        return $fileName;
    }

    /**
     * @param $content
     * @param $fileName
     * @param $pathRelative
     * @param array $ModelClass
     * @param array $ViewModelClass
     * @param array $UseCaseOutputClass
     * @param array $PresenterContratcClass
     * @param array $VueViewModelClass
     * @param array $ViewModelFactoryClass
     * @param array $UseCaseInputClass
     * @return array
     */
    private function writeClassPhpBase($content, $fileName, $pathRelative, $ModelClass = [], $ViewModelClass = [], $UseCaseOutputClass = [], $PresenterContratcClass = [], $VueViewModelClass = [], $ViewModelFactoryClass = [], $UseCaseInputClass = []) : array
    {
        $dir = $this->getFromDirRootWWW($pathRelative);
        $namespace = ucfirst($pathRelative);
        $content = str_replace('%%namespace%%',  Tools::transformPathToNamespace($this->prefixNamespace . $namespace), $content);
        $content = str_replace('%%ClassName%%', $fileName, $content);

        foreach ([
                     'ModelClass' => $ModelClass,
                     'ViewModelClass' => $ViewModelClass,
                     'UseCaseOutputClass' => $UseCaseOutputClass,
                     'PresenterContratcClass' => $PresenterContratcClass,
                     'VueViewModelClass' => $VueViewModelClass,
                     'ViewModelFactoryClass' => $ViewModelFactoryClass,
                     'UseCaseInputClass' => $UseCaseInputClass
                 ]
        as $name => $item
        ) {
            if (!empty($item)) {
                $content = str_replace("%%{$name}Name%%", $item['className'], $content);
                $content = str_replace("%%{$name}Namespace%%", Tools::transformPathToNamespace($this->prefixNamespace . $item['namespace']), $content);
                $content = str_replace("%%{$name}NamespaceUse%%", Tools::transformPathToNamespace($this->prefixNamespace . $item['namespace'] . '/' . $item['className']), $content);
            }
        }

        static::createFileIfNotExists($content, $fileName, $dir);
        return ['namespace' => $namespace, 'className' => $fileName];
    }

    /**
     * Creation des dossiers/class ncessaires au use case du sub domaine
     *
     * @param string $pathRelativeUsecase
     * @param string $useCaseName
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


        $ModelClass = $this->generateUseCaseModelClass($useCaseName, $pathRelativeUsecaseModel);

        $UseCaseInputClass = $this->generateUseCaseInputClass($useCaseName, $pathRelativeUsecase);
        $UseCaseOutputClass = $this->generateUseCaseOutputClass($useCaseName, $pathRelativeUsecase, $ModelClass);

        // Creation des class viewmodel
        $ViewModelClass = $this->generateUseCasePresentationPresenterViewModelClass($useCaseName, $pathRelativeUsecasePresentationViewModel);
        $ViewModelFactoryClass = $this->generateUseCasePresentationPresenterViewModelFactoryClass($useCaseName, $pathRelativeUsecasePresentationViewModel, $ModelClass, $ViewModelClass);

        // Creation des class Presentation
        $PresenterContratcClass = $this->generateUseCasePresentationPresenterContratcClass($useCaseName, $pathRelativeUsecasePresentation, $UseCaseOutputClass);
        $VueViewModelClass = $this->generateUseCasePresentationVueViewModelClass($useCaseName, $pathRelativeUsecasePresentation, $ViewModelClass);
        $VuePresenterClass = $this->generateUseCasePresentationVuePresenterClass($useCaseName, $pathRelativeUsecasePresentation, $PresenterContratcClass, $VueViewModelClass, $UseCaseOutputClass, $ViewModelFactoryClass);

        // Creation des class Usecase
        $UseCaseClass = $this->generateUseCaseClass($useCaseName, $pathRelativeUsecase, $UseCaseInputClass, $UseCaseOutputClass, $PresenterContratcClass, $ModelClass);
    }



    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @return array
     */
    private function generateUseCaseModelClass(string $useCaseName, string $pathRelative) : array
    {
        $fileName = $useCaseName . 'Model' ;

        $content = <<<EOD
<?php

namespace %%namespace%%;

class %%ClassName%%
{

}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @return array
     */
    private function generateUseCasePresentationPresenterViewModelClass(string $useCaseName, string $pathRelative) : array
    {
        $fileName = $useCaseName . 'ViewModel' ;
        $content = <<<EOD
<?php

namespace %%namespace%%;

class %%ClassName%%
{

}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @param array $ModelClass
     * @param array $ViewModelClass
     * @return array
     */
    private function generateUseCasePresentationPresenterViewModelFactoryClass(string $useCaseName, string $pathRelative, array $ModelClass, array $ViewModelClass) : array
    {
        $fileName = $useCaseName . 'ViewModelFactory';
        $content = <<<EOF
<?php

namespace %%namespace%%;

use %%ModelClassNamespaceUse%%;

class %%ClassName%%
{
   /**
     * @param %%ModelClassName%% \$model
     *
     * @return %%ViewModelClassName%%
     */
    public static function getViewModel(%%ModelClassName%% \$model): %%ViewModelClassName%%
    {
        \$viewModel = new %%ViewModelClassName%%();

        return \$viewModel;
    }
}
EOF;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative, $ModelClass, $ViewModelClass);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @param $UseCaseOutputClass
     * @return array
     */
    private function generateUseCasePresentationPresenterContratcClass(string $useCaseName, string $pathRelative, $UseCaseOutputClass) : array
    {
        $fileName = $useCaseName . 'PresenterContract';
        $content = <<<EOD
<?php

namespace %%namespace%%;

use %%UseCaseOutputClassNamespaceUse%%;

interface %%ClassName%%
{

    public function present(%%UseCaseOutputClassName%% \$output): void;
}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative, null, null, $UseCaseOutputClass);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @param $PresenterContratcClass
     * @param $VueViewModelClass
     * @param $UseCaseOutputClass
     * @param $ViewModelFactoryClass
     * @return array
     */
    private function generateUseCasePresentationVuePresenterClass(string $useCaseName, string $pathRelative, $PresenterContratcClass, $VueViewModelClass, $UseCaseOutputClass, $ViewModelFactoryClass) : array
    {
        $fileName = $useCaseName . 'VuePresenter';
        $content = <<<EOD
<?php

namespace %%namespace%%;

use %%UseCaseOutputClassNamespaceUse%%;
use %%ViewModelFactoryClassNamespaceUse%%;

class %%ClassName%% implements %%PresenterContratcClassName%%
{

    private \$viewModel;

    /**
     * @param %%UseCaseOutputClassName%% \$output
     */
    public function present(%%UseCaseOutputClassName%% \$output): void
    {
        \$model = \$output->getModel();

        \$this->viewModel = new %%VueViewModelClassName%%();

        \$this->viewModel->viewModel = %%ViewModelFactoryClassName%%::getViewModel(\$model);
    }

    /**
     * @return %%VueViewModelClassName%%
     */
    public function getViewModel(): %%VueViewModelClassName%%
    {
        return \$this->viewModel;
    }

}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative, null, null, $UseCaseOutputClass, $PresenterContratcClass, $VueViewModelClass, $ViewModelFactoryClass);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @param $ViewModelClass
     * @return array
     */
    private function generateUseCasePresentationVueViewModelClass(string $useCaseName, string $pathRelative, $ViewModelClass) : array
    {
        $fileName = $useCaseName . 'VueViewModel';
        $content = <<<EOD
<?php

namespace %%namespace%%;

use %%ViewModelClassNamespaceUse%%;

class %%ClassName%%
{
    /** @var %%ViewModelClassName%% */
    public \$viewModel;

}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative, null, $ViewModelClass);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @param $UseCaseInputClass
     * @param $UseCaseOutputClass
     * @param $PresenterContratcClass
     * @param $ModelClass
     * @return array
     */
    private function generateUseCaseClass(string $useCaseName, string $pathRelative, $UseCaseInputClass, $UseCaseOutputClass, $PresenterContratcClass, $ModelClass) : array
    {
        $fileName = $useCaseName;
        $content = <<<EOD
<?php

namespace %%namespace%%;

use %%PresenterContratcClassNamespaceUse%%;
use %%ModelClassNamespaceUse%%;

class %%ClassName%%
{

    /** @var %%UseCaseOutputClassName%% */
    private \$output;

    /**
     * Gestion des Repositories en param avec l'autowire
     */
    public function __construct()
    {
    }
    
    /**
     * @param %%UseCaseInputClassName%%      \$input
     * @param %%PresenterContratcClassName%% \$presenter
     */
    public function execute(%%UseCaseInputClassName%% \$input, %%PresenterContratcClassName%% \$presenter): void
    {
        \$this->output = new %%UseCaseOutputClassName%%();

        \$this->output->setModel(new %%ModelClassName%%());

        \$presenter->present(\$this->output);
    }
    
}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative, $ModelClass, null, $UseCaseOutputClass, $PresenterContratcClass, null, null, $UseCaseInputClass);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @return array
     */
    private function generateUseCaseInputClass(string $useCaseName, string $pathRelative) : array
    {
        $fileName = $useCaseName .'Input';
        $content = <<<EOD
<?php

namespace %%namespace%%;

class %%ClassName%%
{

    public function __construct()
    {
    }
}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative);
    }

    /**
     * @param string $useCaseName
     * @param string $pathRelative
     * @param $ModelClass
     * @return array
     */
    private function generateUseCaseOutputClass(string $useCaseName, string $pathRelative, $ModelClass) : array
    {
        $fileName = $useCaseName . 'Output';
        $content = <<<EOD
<?php

namespace %%namespace%%;

use %%ModelClassNamespaceUse%%;

class %%ClassName%%
{
    
    /**
     * @var %%ModelClassName%%
     */
    private \$model= null;

    /**
     * @param %%ModelClassName%% \$model
     *
     * @return \$this
     */
    public function setModel(%%ModelClassName%% \$model): %%ClassName%%
    {
        \$this->model = \$model;

        return \$this;
    }

    /**
     * @return %%ModelClassName%%
     */
    public function getModel(): %%ModelClassName%%
    {
        return \$this->model;
    }

}
EOD;

        return $this->writeClassPhpBase($content, $fileName, $pathRelative, $ModelClass);
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
     * Execute the console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
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

        //$domainesExistants = $this->ListeExistante('');
        // Gestion du domaine
        $domain = $this->anticipate('Domaine ? (vide pour arreter)', []);
        $domain = ucfirst($domain);
        $domainPathRelative = static::__STRUCTURE__DIR_DOMAIN . '/' . $domain;
        if (empty($domain)) {
            return 0;
        }
        $this->generateStructureDomain($domainPathRelative);


        $subDomain = $this->anticipate('Sub-Domaine ? (vide pour arreter)', []);
        $subDomain = ucfirst($subDomain);
        $subDomainPathRelative = $domainPathRelative . '/' . static::__STRUCTURE__DIR_SUB_DOMAIN_NAME . '/' . $subDomain;
        if (empty($subDomain)) {
            return 0;
        }
        $this->generateStructureSubDomain($subDomainPathRelative);

        do {
            $useCase = $this->anticipate('use case ? (vide pour arreter)', []);
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
     * @return array
     */
    private function anticipate(string $question, array $choices) {
        $helper = $this->getHelper('question');
        $question = new Question($question);

        $answer = $helper->ask($this->input, $this->ouput, $question);

        return $answer;
    }

}
