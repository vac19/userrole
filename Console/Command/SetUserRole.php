<?php
/*
 * Console Class for set role to user
 * @category  Salecto
 * @package   Salecto_Advertisment
 * @author    Vashishtha
 */
namespace Salecto\NewUserRole\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\User\Model\UserFactory;
use Magento\Authorization\Model\RoleFactory;

/**
 * Class for set role to user
 */
class SetUserRole extends Command
{
    /**
     * command input parameter 'role'
     */
    const USERID = 'userid';

    /**
     * command input parameter 'role'
     */
    const ROLEID = 'roleid';

    /**
     * User model factory
     *
     * @var \Magento\User\Model\UserFactory
     */
    protected $_userFactory;

    /**
     * Factory for user role model
     *
     * @var \Magento\Authorization\Model\RoleFactory
     */
    protected $_roleFactory;

    /**
     * Constructor
     *
     * @param \Salecto\Advertisment\Model\GridModelFactory
     */
    public function __construct(
        UserFactory $userFactory,
        RoleFactory $roleFactory
    ) {
        
        $this->_userFactory = $userFactory;
        $this->_roleFactory = $roleFactory;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('salecto:set:role');
        $this->setDescription('It will set user as administrator.');
        $this->addOption(
                self::USERID,
                null,
                InputOption::VALUE_REQUIRED,
                'USERID'
        );
        $this->addOption(
                self::ROLEID,
                null,
                InputOption::VALUE_REQUIRED,
                'ROLEID'
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
        $userId = $input->getOption(self::USERID);
        $roleId = $input->getOption(self::ROLEID);
        if ($userId && $roleId) {
            $model = $this->_userFactory->create()->load($userId);
            $roleName = $this->checkRole($roleId);
            if ($userId && $model->isObjectNew()) {
                $output->writeln('<error>User with id = `'.$userId.'`, No longer exisits</error>');
            } elseif ($roleName === 0) {
                $output->writeln('<error>User Role with id = `'.$roleId.'`, Not exisits</error>');
            } else {
              $model->setUserId($userId);
              $model->setRoleId($roleId);
              $model->save();
              $output->writeln('<info>User with id `'.$userId.'`set as `'.$roleName.'`</info>');
            }
        } else {
            $output->writeln('<error>please set both userid and roleid, i.e. --userid=integer --roleid=integer</error>');
        }
    }

    /**
     * Execute the command
     *
     * @param role id $roleId
     *
     * @return null|0|string
     */
    private function checkRole($roleId){
        $role = $this->_roleFactory->create()->load($roleId);
        if ($role->getId()) {
            return $role->getRoleName();
        } else {
            return 0;
        }
    }
}
