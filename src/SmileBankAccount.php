<?php
namespace Thru\Bank;

class SmileBankAccount extends CooperativeBankAccount {
  public function __construct(){
    $this->baseUrl = "https://banking.smile.co.uk/SmileWeb/start.do";
    parent::__construct();
  }

}