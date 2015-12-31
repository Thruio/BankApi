<?php

namespace Thru\Bank;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Thru\BankApi\Banking\BankAccountAuthException;
use Thru\BankApi\Banking\BaseBankAccount;
use Thru\BankApi\Models\Account;
use Thru\BankApi\Models\AccountHolder;

class Worker
{
    public function run()
    {
        $settings = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(APP_ROOT . "/configuration.yml"));

        if (isset($settings['Selenium']['Host'])) {
            $host = $settings['Selenium']['Host'];
        } elseif (isset($_SERVER['SELENIUM_PORT'])) {
            $host = parse_url($_SERVER['SELENIUM_PORT']);
            $host = "http://" . $host['host'] . ":" . $host['port'] . "/wd/hub";
        } else {
            $host = "http://localhost:4444/wd/hub";
        }
        
        echo "Connecting to Selenium at {$host} ... \n";
        if (isset($settings['Selenium']['BrowserDriver'])) {
            switch ($settings['Selenium']['BrowserDriver']) {
                case 'chrome':
                    $desiredCapabilities = \DesiredCapabilities::chrome();
                    break;
                case 'firefox':
                default:
                    $desiredCapabilities = \DesiredCapabilities::firefox();
                    break;
            }
        } else {
            $desiredCapabilities = \DesiredCapabilities::firefox();
        }
        if ($settings['Telegram']) {
            $bot = new \TelegramBot\Api\BotApi($settings['Telegram']['BotToken']);
            $telegram = function ($message) use ($bot, $settings) {
                if (count($settings['Telegram']['Channels']) > 0) {
                    foreach ($settings['Telegram']['Channels'] as $chat_id) {
                        $bot->sendMessage($chat_id, $message);
                    }
                }
            };
        }


        foreach ($settings['People'] as $person) {
            $accountHolder = AccountHolder::FetchOrCreateByName($person['Name']);
            $seleniumDriver = \RemoteWebDriver::create($host, $desiredCapabilities);

            $monolog = new Logger("BankAPI");

            $slackLoggerHandler = new \Monolog\Handler\SlackHandler(
                SLACK_TOKEN,
                SLACK_CHANNEL,
                SLACK_USER,
                null,
                null,
                \Monolog\Logger::DEBUG
            );
            $slackLoggerHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
            $monolog->pushHandler($slackLoggerHandler);

            $run = new \Thru\BankApi\Models\Run();
            $run->setLogger($monolog);
            if ($telegram) {
                $run->setTelegram($telegram);
            }
            $run->save();

            $run->getLogger()->addInfo("Running check for balances...");

            //try {
            foreach ($person['Accounts'] as $account_label => $details) {
                echo "Logging into {$person['Name']}'s {$account_label}...\n";


                $connectorName = "\\Thru\\BankApi\\Banking\\" . $details['connector'];
                $connector = new $connectorName($account_label);
                if (!$connector instanceof BaseBankAccount) {
                    throw new \Exception("Connector is not instance of BaseBankAccount");
                }
                $connector->setAuth($details['auth']);
                $connector->setSelenium($seleniumDriver);
                try {
                    $connector->run($accountHolder, $run, $account_label);
                } catch (BankAccountAuthException $authException) {
                    echo $authException->getMessage();
                }

                echo "\n\n";
            }
            /*}catch(\Exception $e){
                echo "*****************************************************\n";
                echo "*** An exception occurred while checking balances ***\n";
                echo "*****************************************************\n";
                echo "\n";
                echo $e->getMessage();
            }*/

            $seleniumDriver->close();

            $run->end();
        }
    }
}
