<?php
namespace Thru\Bank\Banking;

use Thru\Bank\Models\Account;
use Thru\Bank\Models\Balance;
use Thru\Bank\Models\Run;
use Thru\Bank\Models\Transaction;

class CashPlusBankAccount extends BaseBankAccount {
  protected $baseUrl = "https://secure.membersaccounts.com";

  public function __construct($accountName){
    parent::__construct($accountName);
  }

  public function run(Run $run){
    parent::run($run);
    $account = Account::FetchOrCreateByName($this->getAccountName());
    $this->getSelenium()->get($this->baseUrl);
    $this->getSelenium()->findElement(\WebDriverBy::name("ctl00\$_login\$UserName"))->clear()->sendKeys($this->getAuth("username"));
    $this->getSelenium()->findElement(\WebDriverBy::name("ctl00\$_login\$Password"))->clear()->sendKeys($this->getAuth("password"));
    $this->getSelenium()->findElement(\WebDriverBy::name("ctl00\$_login\$LoginButton"))->click();
    $this->takeScreenshot("Logged in");
    $this->getSelenium()->findElement(\WebDriverBy::cssSelector("a[href='PrimaryCard.aspx']"))->click();
    $this->takeScreenshot("Account Details");
    $currentBalance = $this->getSelenium()->findElement(\WebDriverBy::id("ctl00_ctrlAccountBalance1_lblCurrentBalance"))->getText();
    $currentBalance = preg_replace("/[^0-9,.]/", "", $currentBalance);

    // Create new balance data.
    $balance = new Balance();
    $balance->run_id = $run->run_id;
    $balance->account_id = $account->account_id;
    $balance->value = $currentBalance;

    // Update account last checked
    $account->last_check = date("Y-m-d H:i:s");

    // Save balance and account.
    $balance->save();
    $account->save();

    echo "Current balance is £{$currentBalance}\n";

    // Go get transaction data
    $this->getSelenium()->findElement(\WebDriverBy::cssSelector("a[href='PrimaryStatements.aspx']"))->click();

    // Try to get pending data
    try {
      echo "Trying to add pending transactions ... ";
      $pendingTable = $this->getSelenium()->findElement(\WebDriverBy::id("ctl00_TransGrid_gridPending"));
      $transactions = $this->parseTable($run, $account, $pendingTable, true);
      echo count($transactions) . " found.\n";
    }catch (\NoSuchElementException $e){
      // Do nothing.
    }

    // Try to get current data
    try {
      echo "Trying to add current transactions ... ";
      $currentTable = $this->getSelenium()->findElement(\WebDriverBy::id("ctl00_TransGrid_gridAuth"));
      $transactions = $this->parseTable($run, $account, $currentTable, false);
      echo count($transactions) . " found.\n";
    }catch (\NoSuchElementException $e){
      // Do nothing.
    }

    $this->cleanUp();
  }

  /**
   * @param Run $run
   * @param Account $account
   * @param \RemoteWebElement $transactionTable
   * @params Bool $pending Wether or not the transaction is pending
   * @return Transaction[]
   */
  private function parseTable(Run $run, Account $account, \RemoteWebElement $transactionTable, $pending){
    $transactions = [];
    $transactionRows = $transactionTable->findElements(\WebDriverBy::className("StatementGridItemStyle"));
    foreach($transactionRows as $transactionRow){
      $is_credit = $transactionRow->findElement(\WebDriverBy::className('financeType'))->getText() == 'Credit' ? true : false;
      if($is_credit) {
        $value = $transactionRow->findElement(\WebDriverBy::className("credit"))->getText();
      }else{
        $value = $transactionRow->findElement(\WebDriverBy::className("debit"))->getText();
      }
      $value = preg_replace("/[^0-9,.-]/", "", $value);

      $transaction = Transaction::Create(
        $run,
        $account,
        $transactionRow->findElement(\WebDriverBy::className("merchantName"))->getText(),
        date("Y-m-d H:i:s", strtotime($transactionRow->findElement(\WebDriverBy::className("date"))->getText())),
        $value,
        $pending?"Pending":"Complete"
      );

      $transactions[] = $transaction;
    }
    return $transactions;
  }
}