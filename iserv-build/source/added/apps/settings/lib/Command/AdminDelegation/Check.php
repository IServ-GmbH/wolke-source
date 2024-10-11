<?php

namespace OCA\Settings\Command\AdminDelegation;

use OC\Core\Command\Base;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\IGroupManager;
use OCP\Settings\IDelegatedSettings;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Check extends Base
{
    public function __construct(
        private AuthorizedGroupService $authorizedGroupService,
        private IGroupManager $groupManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('admin-delegation:check')
            ->setDescription('check if admin delegation to a group is set')
            ->addArgument('settingClass', InputArgument::REQUIRED, 'Admin setting class')
            ->addArgument('groupId', InputArgument::REQUIRED, 'Delegate to group ID')
            ->addUsage('\'OCA\Settings\Settings\Admin\Server\' mygroup')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $settingClass = $input->getArgument('settingClass');

        $groups = $this->authorizedGroupService->findExistingGroupsForClass($settingClass);

        $groupId = $input->getArgument('groupId');

        if (!in_array(IDelegatedSettings::class, (array) class_implements($settingClass), true)) {
            $io->error('The specified class isn’t a valid delegated setting.');
            return 2;
        }

        if (!$this->groupManager->groupExists($groupId)) {
            $io->error('The specified group didn’t exist.');
            return 3;
        }

        if (current(array_filter($groups, fn ($g) => $g->getGroupId() === $groupId))) {
            $io->success('Administration of ' . $settingClass . ' is delegated to ' . $groupId . '.');
            return 0;
        } else {
            $io->info('Administration of ' . $settingClass . ' is NOT delegated to ' . $groupId . '.');
            return 1;
        }
    }
}
