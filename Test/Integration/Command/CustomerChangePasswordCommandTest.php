<?php

declare(strict_types=1);

namespace VinaiKopp\CustomerChangePasswordCommand\Test\Integration;

use Magento\Customer\Model\Customer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VinaiKopp\CustomerChangePasswordCommand\Command\CustomerChangePasswordCommand;

class CustomerChangePasswordCommandTest extends \PHPUnit_Framework_TestCase
{
    private function createCommand(): CustomerChangePasswordCommand
    {
        return ObjectManager::getInstance()->create(CustomerChangePasswordCommand::class);
    }

    private function instantiateCustomerModel(): Customer
    {
        return ObjectManager::getInstance()->create(Customer::class);
    }

    /**
     * @return InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockInput(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMock(InputInterface::class);
    }

    private function getStoreManager(): StoreManagerInterface
    {
        return ObjectManager::getInstance()->get(StoreManagerInterface::class);
    }

    private function getDefaultWebsiteId(): int
    {
        return (int) $this->getStoreManager()->getDefaultStoreView()->getWebsiteId();
    }

    private function getDefaultWebsiteCode(): string
    {
        return $this->getStoreManager()->getWebsite($this->getDefaultWebsiteId())->getCode();
    }

    /**
     * @return OutputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockOutput(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->getMock(OutputInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture current_store customer/account_share/scope 0
     */
    public function testUpdatesCustomerPasswordWithGlobalCustomerAccounts()
    {
        $fixtureCustomerEmail = 'customer@example.com';
        $newPassword = uniqid('new-password');

        $mockInput = $this->createMockInput();
        $mockInput->method('getArgument')->willReturnMap([
            ['email', $fixtureCustomerEmail],
            ['password', $newPassword]
        ]);

        $setPasswordCommand = $this->createCommand();
        $setPasswordCommand->run($mockInput, $this->createMockOutput());

        $this->assertTrue($this->instantiateCustomerModel()->authenticate($fixtureCustomerEmail, $newPassword));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoConfigFixture current_store customer/account_share/scope 1
     */
    public function testUpdatesCustomerPasswordWithWebsiteScopeAccounts()
    {
        $fixtureCustomerEmail = 'customer@example.com';
        $newPassword = uniqid('new-password');

        $mockInput = $this->createMockInput();
        $mockInput->method('getArgument')->willReturnMap([
            ['email', $fixtureCustomerEmail],
            ['password', $newPassword]
        ]);
        $mockInput->method('getOption')->willReturnMap([
            ['website', $this->getDefaultWebsiteCode()]
        ]);

        $setPasswordCommand = $this->createCommand();
        $setPasswordCommand->run($mockInput, $this->createMockOutput());

        $customer = $this->instantiateCustomerModel();
        $customer->setWebsiteId($this->getDefaultWebsiteId());
        
        $this->assertTrue($customer->authenticate($fixtureCustomerEmail, $newPassword));
    }
}
