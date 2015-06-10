<?php
namespace Thru\Bank;

class CooperativeBankAccount extends BaseBankAccount {
  protected $baseUrl = "https://personal.co-operativebank.co.uk/CBIBSWeb/start.do";

  public function __construct($accountName){
    parent::__construct($accountName);
  }

  public function run(){
    parent::run();
    $this->getSelenium()->get($this->baseUrl);
    if($this->getAuth('sort') && $this->getAuth('acct')){
      $this->getSelenium()->findElement(\WebDriverBy::id("sortcode"))->clear()->sendKeys($this->getAuth('sort'));
      $this->getSelenium()->findElement(\WebDriverBy::id("accountnumber"))->clear()->sendKeys($this->getAuth('acct'));
    }elseif($this->getAuth('creditcard')){
      $this->getSelenium()->findElement(\WebDriverBy::id("visanumber"))->clear()->sendKeys($this->getAuth('creditcard'));
    }else{
      throw new BankAccountAuthException("No 'sort' and 'acct' given, nor was a 'creditcard'");
    }
    $this->takeScreenshot("Identity");
    $this->getSelenium()->findElement(\WebDriverBy::name('ok'))->click();

    $words = ['first','second','third','fourth'];

    foreach(str_split($this->getAuth('security'), 1) as $index => $number){
      try{
        $indexAsWord = $words[$index];
        $digitSelect = $this->getSelenium()->findElement(\WebDriverBy::id($indexAsWord . 'PassCodeDigit'));
        $digitSelect->findElement(\WebDriverBy::cssSelector("option[value='" . $number . "']"))->click();
        echo "Selected Security PIN digit was {$indexAsWord} and equals {$number}\n";
      }catch(\NoSuchElementException $e){
        // Do nothing
      }
    }
    $this->takeScreenshot("Security");

    $this->getSelenium()->findElement(\WebDriverBy::name('ok'))->click();

    // Memorable Date?
    try{
      $challengeMemorableDay = $this->getSelenium()->findElement(\WebDriverBy::name("memorableDay"));
      $challengeMemorableMonth = $this->getSelenium()->findElement(\WebDriverBy::name("memorableMonth"));
      $challengeMemorableYear = $this->getSelenium()->findElement(\WebDriverBy::name("memorableYear"));
      $challengeMemorableDay->clear()->sendKeys(date('d', strtotime($this->getAuth('memorable_date'))));
      $challengeMemorableMonth->clear()->sendKeys(date('m', strtotime($this->getAuth('memorable_date'))));
      $challengeMemorableYear->clear()->sendKeys(date('Y', strtotime($this->getAuth('memorable_date'))));
      echo "Memorable date is " . $challengeMemorableDay->getAttribute('value') . " / " .$challengeMemorableMonth->getAttribute('value') . " / " . $challengeMemorableYear->getAttribute('value') . "\n";

    }catch(\NoSuchElementException $e){
      // Do nothing
    }

    // First School?
    try{
      $challengeFirstSchool = $this->getSelenium()->findElement(\WebDriverBy::name("firstSchool"));
      $challengeFirstSchool->clear()->sendKeys($this->getAuth('first_school'));
      echo "First school is {$challengeFirstSchool->getAttribute('value')}\n";
    }catch(\NoSuchElementException $e){
      // Do nothing
    }

    // Last School?
    try{
      $challengeLastSchool = $this->getSelenium()->findElement(\WebDriverBy::name("lastSchool"));
      $challengeLastSchool->clear()->sendKeys($this->getAuth('last_school'));
      echo "Last school is {$challengeLastSchool->getAttribute('value')}\n";
    }catch(\NoSuchElementException $e){
      // Do nothing
    }

    // Birthplace?
    try{
      $challengeBirthPlace = $this->getSelenium()->findElement(\WebDriverBy::name("birthPlace"));
      $challengeBirthPlace->clear()->sendKeys($this->getAuth('birth_place'));
      echo "Birthplace is {$challengeBirthPlace->getAttribute('value')}\n";
    }catch(\NoSuchElementException $e){
      // Do nothing
    }

    // Birthplace?
    try{
      $challengeMemorableName = $this->getSelenium()->findElement(\WebDriverBy::name("memorableName"));
      $challengeMemorableName->clear()->sendKeys($this->getAuth('memorable_name'));
      echo "Memorable Name is {$challengeMemorableName->getAttribute('value')}\n";
    }catch(\NoSuchElementException $e){
      // Do nothing
    }

    $this->takeScreenshot("Challenge");
    $this->getSelenium()->findElement(\WebDriverBy::name('ok'))->click();
    $this->takeScreenshot("Logged in");

    try{
      $errors = $this->getSelenium()->findElements(\WebDriverBy::cssSelector('.error'));
      $errorsMessages = '';
      foreach($errors as $error){
        $errorsMessages .= trim($error->getText()) . "\n";
      }
      throw new BankAccountAuthException("Failed to log in. See Logged-in screenshot. {$errorsMessages}");
    }catch(\NoSuchElementException $e){
      // Do nothing, everything is ok
    }
  }
}