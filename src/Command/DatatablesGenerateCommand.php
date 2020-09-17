<?php

namespace Omines\DataTablesBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class DatatablesGenerateCommand extends Command {

    protected static $defaultName = 'datatables:generate';
    private $em;
    private $cs;
    private $io;

    public function __construct(?string $name = null) {
        parent::__construct($name);
    }

    protected function configure() {
        $this
                ->setDescription('Add a short description for your command')
//                ->addArgument('controllerName', InputArgument::REQUIRED, 'Name of controller with sufix of Controller')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $this->io = new SymfonyStyle($input, $output);
        $io = $this->io;
        $io->writeln('<info>Create Datatable From command</info>');
        $io->writeln('<comment>=========================</comment>');

        $helper = $this->getHelper('question');
        $io->writeln('<info>Enter Name Of Controller (<comment>Eg. AdminController</comment>)</info>');
        $controllerQ = new \Symfony\Component\Console\Question\Question('>', '');
        $controllerQ->setAutocompleterValues($this->getControllerSuggestions());
        $controller = $helper->ask($input, $output, $controllerQ);
        if ($controller == null || trim($controller) == "") {
            $io->error('Controller Not Given');
            return 0;
        }
        $controllerNameSpace = $this->getControllerNameSpace($controller);
        if (!$controllerNameSpace) {
            return 0;
        }
        $io->writeln('<info>Please name of entity: (<comment>Eg. AdminEntity</comment>)</info>');
        $entityQ = new \Symfony\Component\Console\Question\Question('>', 'entity');
        $entityQ->setAutocompleterValues($this->getEntitySuggestion());
        $entity = $helper->ask($input, $output, $entityQ);
        $entityNameSpace = $this->getEntityNameSpace($entity);
        if (!$entityNameSpace) {
            return 0;
        }
        $io->writeln('<info>Enter Required Twig Name: (Eg.<comment>adminDatatable</comment>)</info>');
        $twigQ = new \Symfony\Component\Console\Question\Question('>', '');
        $twig = $helper->ask($input, $output, $twigQ);
        if ($twig == null || trim($twig) == "") {
            $io->error('Twig Not Given');
            return 0;
        }
        $twigPath = $this->twigNameValidatedPath($twig);

        $io->writeln('<info>Please the route where you want datatable to be created: (Eg.<comment>/admin/datatable</comment>)</info>');
        $routeQ = new \Symfony\Component\Console\Question\Question('>', '');
        $route = $helper->ask($input, $output, $routeQ);
        if ($route == null || trim($route) == "") {
            $io->error('Route Not Given');
            return 0;
        }
        $controllerFileName = $this->getFileNameFromNameSpace($controllerNameSpace);
        $filecontent = file_get_contents($controllerFileName);
        $twigContent = file_put_contents($twigPath, $this->getTwigContent());
        $pos = strripos($filecontent, '}');
        $newContent = substr_replace($filecontent, $this->getDatatableFunction($entityNameSpace, $this->twigNameValidator($twig), $route), $pos);
        file_put_contents($controllerFileName, $newContent);

        $io->success($route);
        $io->success($controller);
        $io->success('Datatable Generated');
        return 0;
    }

    public function getTwigPath() {
        $twig = $this->getApplication()->getKernel()->getContainer()->getParameter('twig.default_path');
        return $twig;
    }

    public function twigNameValidator($name) {
        if (strpos('.html.twig', $name) !== false) {
            $name;
        } else {
            $name = $name . '.html.twig';
            return $name;
        }
    }

    public function twigNameValidatedPath($name) {
        if (strpos('.html.twig', $name) !== false) {
            return $this->getTwigPath() . '/' . $name;
        } else {
            $name = $name . '.html.twig';
            return $this->getTwigPath() . '/' . $name;
        }
    }

    public function getProjectPath() {
        $kernel = $this->getApplication()->getKernel()->getContainer()->get('kernel');
        return $kernel->getProjectDir();
    }

    public function getEntityArrayOptions() {
        $entityManager = $this->getApplication()->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        $entities = [];
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metas as $meta) {
            $entities[] = $meta->getName();
        }
        return $entities;
    }

    public function getEntitySuggestion() {
        $options = $this->getEntityArrayOptions();
        $arr = [];
        foreach ($options as $o) {
            $name = explode("\\", $o);
            $coun = count($name) - 1;
            $name = $name[$coun];
            $arr[$name] = $o;
        }
        return $arr;
    }

    public function getEntityNameSpace($name) {
        $suggestions = $this->getEntitySuggestion();
        if (array_key_exists($name, $suggestions)) {
            return $suggestions[$name];
        } else {
            $this->io->error('Entity Does Not Exixt');
            return false;
        }
    }

    public function getControllers() {
        $router = $this->getApplication()->getKernel()->getContainer()->get('router');
        /** @var $collection \Symfony\Component\Routing\RouteCollection */
        $collection = $router->getRouteCollection();
        $allRoutes = $collection->all();
        $routes = array();
        /** @var $params \Symfony\Component\Routing\Route */
        foreach ($allRoutes as $route => $params) {
            $defaults = $params->getDefaults();

            if (isset($defaults['_controller'])) {
                $controllerAction = explode(':', $defaults['_controller']);
                $controller = $controllerAction[0];

                if (!isset($routes[$controller])) {
                    $routes[$controller] = array();
                }

                $routes[$controller][] = $route;
            }
        }
        return $routes;
    }

    public function getControllersNames() {
        $controllers = $this->getControllers();
        $cons = [];
        foreach ($controllers as $k => $v) {
            $cons[] = $k;
        }
        return $cons;
    }

    public function getControllerSuggestions() {
        $controllers = $this->getControllersNames();
        $array = [];
        foreach ($controllers as $c) {
            $ar = explode('\\', $c);
            $count = count($ar) - 1;
            $suggestion = $ar[$count];
            $array[$suggestion] = $c;
        }
        return $array;
    }

    public function getControllerNameSpace($name) {
        $suggestions = $this->getControllerSuggestions();
        if (array_key_exists($name, $suggestions)) {
            return $suggestions[$name];
        } else {
            $this->io->error('Controller Does Not Exixt');
            return false;
        }
    }

    public function generateRoutingAnnotation($route) {
        $routename = str_replace('/', '_', $route);
        $ann = chr(13);
        $ann .= "/**\n";
        $route = '* @Route("' . $route . '", name="' . $routename . '"';
        $route .= ', methods={"';
        $route .= implode('","', ['GET', 'POST']);
        $route .= '"}';
        $route .= ')' . "\n";
        $ann .= $route;
        $ann .= "*/" . "\n";
        $ann .= "\n";

        return trim($ann);
    }

    public function getFileNameFromNameSpace($namespace) {
        $namesapceArr = explode("\\", $namespace);
        $len = count($namesapceArr) - 1;
        $controllerFileName = $namesapceArr[$len] . '.php';
        $finder = new Finder();
        $files = $finder->files()->in($this->getProjectPath())->name($controllerFileName);
        $path = '';
        foreach ($files as $file) {
            $path = $file->getRealPath();
            // ...
        }
        return $path;
    }

    public function getTwigContent() {
        $content = '
            <html>
            <head>
            <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jq-3.2.1/dt-1.10.16/datatables.min.css"/>
            </head>
            <body>
            <!-- Insert this where you want the table to appear -->
<div id="presidents">Loading...</div>

<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jq-3.2.1/dt-1.10.16/datatables.min.js"></script>
<!-- Insert this at the end of your body element, but before the closing tag -->
<script src="{{ asset(\'bundles/datatables/js/datatables.js\') }}"></script>
<script>
$(function() {
    $(\'#presidents\').initDataTables({{ datatable_settings(datatable) }});
});
</script>
</body>
</html>';
        return $content;
    }

    public function generateFunctionName($route) {
        $name = str_replace('/', "", $route);
        return $name;
    }

    public function getDatatableFunction($entityNameSpace, $twig, $route) {
        $annotaion = $this->generateRoutingAnnotation($route);
        $functionName = $this->generateFunctionName($route);
        $function = $annotaion . "\n" . ''
                . 'public function ' . $functionName . '(\Symfony\Component\HttpFoundation\Request $request ,\Omines\DataTablesBundle\DataTableFactory $dataTableFactory){'
                . '$table = $dataTableFactory->create()
            ->add("id", TextColumn::class)
            ->createAdapter(\Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter::class, [
        \'entity\' => \\' . $entityNameSpace . '::class,
        ])
        ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render("' . $twig . '", [\'datatable\' => $table]);'
                . '}'
                . '}';
        return $function;
    }

}
