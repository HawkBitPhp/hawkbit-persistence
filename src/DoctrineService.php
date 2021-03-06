<?php
/**
 * Created by PhpStorm.
 * User: marco.bunge
 * Date: 14.10.2016
 * Time: 12:23
 */

namespace Hawkbit\Doctrine;

use ContainerInteropDoctrine\EntityManagerFactory;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use Hawkbit\Application;
use League\Container\Container;

class DoctrineService implements DoctrineServiceInterface
{

    /**
     * @var Container
     */
    private $container;

    /**
     * PersistenceService constructor.
     * @param array $factories
     * @param Application $application
     */
    public function __construct(array $factories = [], Application $application)
    {
        if(1 > count($factories)){
            throw new \InvalidArgumentException('No factories available!');
        }

        // create a new container and separate from application lifecycle
        // container is delegated in service provider
        $container = new Container();

        // share config array
        $container->share('config', $application->getConfigurator()->toArray());
        $this->container = $this->registerFactories($container, $factories);
    }

    /**
     * Register factories to container
     *
     * @param Container $container
     * @param array $factories
     * @return Container
     */
    private function registerFactories(Container $container, array $factories){

        foreach ($factories as $alias => $args){
            if(1 > count($factories)){
                throw new \RuntimeException('Invalid factory config');
            }

            $class = array_shift($args);

            if(!class_exists($class)){
                throw new \RuntimeException('Invalid factory class');
            }

            $container->add($alias, function() use ($class, $args){
                return (new \ReflectionClass($class))->newInstanceArgs($args);
            });

        }

        return $container;
    }

    /**
     * Get entity manager for connection
     *
     * @param $connection
     * @return EntityManager
     */
    public function getEntityManager($connection = self::DEFAULT_CONNECTION_NAME){
        /** @var EntityManagerFactory $emf */
        $emf = $this->container->get(self::resolveFactoryAlias(EntityManagerFactory::class, $connection), [$connection]);
        return $emf($this->container);
    }

    /**
     * Get the container
     *
     * @return \League\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Resolve alias from factory and connection
     *
     * @param $factory
     * @param $connection
     * @return string
     */
    public static function resolveFactoryAlias($factory, $connection = self::DEFAULT_CONNECTION_NAME){
        $key = Inflector::tableize(str_replace('Factory', '', substr($factory, strrpos($factory, '\\') + 1)));
        return sprintf('doctrine.%s.%s', $key, $connection);
    }
}