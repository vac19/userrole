<?php
/*
 * Console Class for add Advertisment into DB table.
 * @category  Salecto
 * @package   Salecto_Advertisment
 * @author    Salecto
 */
namespace Salecto\NewUserRole\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Class SomeCommand
 */
class NewUserRole extends Command
{
    /**
     * command input parameter 'role'
     */
    const NAME = 'role';

    /**
     * RoleFactory
     *
     * @var roleFactory
     */
    private $roleFactory;
 
     /**
     * RulesFactory
     *
     * @var rulesFactory
     */
    private $rulesFactory;

    /**
     * Constructor
     *
     * @param \Salecto\Advertisment\Model\GridModelFactory
     */
    public function __construct(
        \Magento\Authorization\Model\RoleFactory $roleFactory,
        \Magento\Authorization\Model\RulesFactory $rulesFactory 
    ) {
        
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('salecto:user:role');
        $this->setDescription('This is my first console command.');
        $this->addOption(
                self::NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'NAME'
            );
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $salectoRole = $input->getOption(self::NAME);
        $roles = $this->roleFactory->create();
        $role = $roles->getCollection();
        $role->addFieldToFilter('role_name',['eq' => $salectoRole]);
        $role->addFieldToSelect('role_name');

        if (empty($salectoRole)) {
        	$output->writeln('<error> --User Role Name Require. i.e. --role="value"</error>');
        } elseif (empty($role->getFirstItem()->getRoleName())) {

        	try{
        		$role = $this->roleFactory->create();
                $role->setName($salectoRole)
		                  ->setPid(0)
		                  ->setRoleType(RoleGroup::ROLE_TYPE) 
		                  ->setUserType(UserContextInterface::USER_TYPE_ADMIN);
		          $role->save();
		          $resource=[
		                      'Magento_Cms::config_cms',
		                      'Magento_Backend::content_elements',
		                      'Magento_Cms::page',
		                      'Magento_Cms::save',
		                      'Magento_Cms::save_design',
		                      'Magento_Backend::stores',
		                      'Magento_Backend::stores_settings',
		                      'Magento_Config::config',
		                      'Magento_TwoFactorAuth::config',
		                      'Magento_Backend::system',
		                      'Magento_User::acl',
		                      'Magento_TwoFactorAuth::tfa'
		                    ];
		          $return = $this->rulesFactory->create()
		                          ->setRoleId($role->getId())
		                                            ->setResources($resource)
		                                            ->saveRel();
            }catch (\Exception $e){
                $output->writeln('<info>Can not save new user role - `' . $e . '`</info>');            
            }
        	$output->writeln('<info> New User Role Added `'.$salectoRole.'`</info>');
        } else {
            $output->writeln('<error> --Role name already exists `'.$salectoRole.'`</error>');
        }
    }
}
