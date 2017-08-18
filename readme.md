# Set a customer password

Since Magento 2 no longer provides facilities to set a customers password, this command can come in handy during development when working with testing customer accounts.

## Installation

```bash
composer config repositories.vinaikopp-customer-password-command vcs https://github.com/Vinai/module-customer-password-command.git
composer require vinaikopp/module-customer-password-command:dev-master
bin/magento setup:upgrade
```

## Usage 

Call the command and pass the customers email address and the new password.

```bash
bin/magento customer:change-password test@examplecom password123
```

If customer accounts are not shared between websites, a website code has to be specified with the `--website` or `-w` option.


```bash
bin/magento customer:change-password --website base test@examplecom password123
```
