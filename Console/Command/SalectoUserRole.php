<?php
/*
 * Console Class to set existing user role resources.
 * @category  Salecto
 * @package   Salecto_Advertisment
 * @author    
 */
namespace Salecto\NewUserRole\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Framework\Acl\AclResource\ProviderInterface;


/**
 * Command Class to set user role resporces. 
 */
class SalectoUserRole extends Command
{
    /**
     * input argument parameter to receive role id. 
     */
    const ROLE_ID = 'role';

    /**
     * input argument for receving resource id(s)
     */
    const RESOURCE_IDS = 'resource';

    /**
     * input option to unset role resource(s)
     */
    const DECLINE = 'unset';

    /**
     * Constructor
     *
     * @param \Salecto\Advertisment\Model\GridModelFactory
     */
    public function __construct(
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory,
        ProviderInterface $aclResourceProvider
    ) {
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
        $this->aclResourceProvider = $aclResourceProvider;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure ()
    {
        $this->setName('reform:role:resource');
        $this->setDescription('Command for reform user role resources.');
        $this->addArgument(
            self::ROLE_ID, 
            InputArgument::REQUIRED,
            'Expects role id'
        );
        $this->addArgument(
            self::RESOURCE_IDS, 
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Expects resporces id(s)'
        );
        $this->addOption(
                self::DECLINE,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'unsets the resource id and children from requested resources'
        );
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return Exception|int
     */
    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $obsereRole = $input->getArgument('role');

        $configResource = $this->aclResourceProvider->getAclResources();

        $resourceIds = [];
        foreach ($input->getArgument('resource') as $obsereResource ) {
            $resourceFamily = $this->findResourceId($configResource, 'id', $obsereResource);
            $familyIds = $this->getResourceIds($resourceFamily, 'id');
            $resourceIds = array_merge($resourceIds, $familyIds);
        }
        
        $removeIds = $input->getOption(self::DECLINE);

        foreach ($removeIds as $removeId ) {
            $resourceFamily = $this->findResourceId($resourceFamily, 'id', $removeId);
            $familyIds = $this->getResourceIds($resourceFamily, 'id');
            $removeIds = array_merge($removeIds, $familyIds);
        }

        $loadRole = $this->roleFactory->create()->load($obsereRole);

        if ($loadRole->getId()) {
            try {
                $updateRole = $this->rulesFactory->create()
                                ->setRoleId($loadRole->getId())
                                ->setResources(array_values(array_diff($resourceIds, $removeIds)))
                                ->saveRel();

                if ($updateRole->getRoleId()) {
                    $output->writeln('<info>User role `'.$updateRole->getRoleId().'` => `'.$loadRole->getRoleName().'` been updated.</info>');
                }
            } catch (\Exception $e) {
                $output->writeln('<error>Can not save new user role - `' . $e . '`</error>');            
            }
        } else {
            $output->writeln('<error>No user role found with id `'.$obsereRole.'`.</error>');
        }
    }

    /**
     * It will fetch the array family of provided parent resource id `$configResource`
     *
     * @param Resources array $resourceData
     * @param resource id $key
     * @param parent resource id to find.  $value
     *
     * @return null|array
     */
    public function findResourceId($resourceData, $key, $value)
    {
      $results = array();
      if (is_array($resourceData)) {
          if (isset($resourceData[$key]) && $resourceData[$key] == $value) {
              $results[] = $resourceData;
          }

          foreach ($resourceData as $subData) {
              $results = array_merge($results, $this->findResourceId($subData, $key, $value));
          }
      }
      return $results;
    }

    /**
     * Collects the key `id` from array output of findResourceId()
     *
     * @param Resources array $resourceData
     * @param resource ids to collect $key
     *
     * @return null|array
     */
    public function getResourceIds($array, $key) {
      $results = array();
      if (is_array($array)) {
          if (isset($array[$key])) {
            $results[] = $array[$key]; 
          }
          foreach ($array as $subarray) {
              $results = array_merge($results, $this->getResourceIds($subarray, $key));
          }
      }
      return $results;
    }
}
