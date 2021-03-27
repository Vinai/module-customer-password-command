<?php

declare(strict_types=1);

namespace VinaiKopp\CustomerChangePasswordCommand\Command;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CustomerChangePasswordCommand extends Command
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var InputInterface
     */
    private $input;

    public function __construct(
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        CustomerResource $resource,
        AppState $state
    ) {
        parent::__construct();
        $this->customerFactory = $customerFactory;
        $this->customerResource = $resource;
        $this->storeManager = $storeManager;
        $this->state = $state;
    }

    private function guardAgainstSessionInitializationBug(AppState $appState)
    {
        try {
            $appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
        }
    }

    protected function configure()
    {
        $this->setName('customer:change-password');
        $this->setDescription('Set a customers password');
        $this->addOption(
            'website',
            'w',
            InputOption::VALUE_OPTIONAL,
            'Website code if customer accounts are website scope'
        );
        $this->addArgument('email', InputArgument::REQUIRED, 'Customer Email');
        $this->addArgument('password', InputArgument::REQUIRED, 'Password to set');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $customer = $this->getCustomerByEmail($this->getEmail());
        $customer->setPassword($this->getPassword());
        $this->customerResource->save($customer);
        $output->writeln(sprintf('Updated password for customer "%s".', $this->getEmail()));
    }

    private function getEmail(): string
    {
        return $this->input->getArgument('email') ?? '';
    }

    private function getPassword(): string
    {
        return $this->input->getArgument('password') ?? '';
    }

    private function getWebsiteCode(): string
    {
        return $this->input->getOption('website') ?? '';
    }

    private function getWebsiteIdByCode(string $code): int
    {
        $website = $this->storeManager->getWebsite($code);
        if (! $website->getId()) {
            throw new \InvalidArgumentException(sprintf('No website with ID "%s" found.', $code));
        }
        
        
        return (int) $website->getId();
    }

    private function getCustomerByEmail($email): Customer
    {
        $customer = $this->customerFactory->create();
        if ($this->getWebsiteCode()) {
            $websiteId = $this->getWebsiteIdByCode($this->getWebsiteCode());
            $customer->setWebsiteId($websiteId);
        }
        $this->customerResource->loadByEmail($customer, $email);
        if (! $customer->getId()) {
            throw new \InvalidArgumentException(sprintf('No customer with email "%s" found.', $this->getEmail()));
        }

        return $customer;
    }
}
