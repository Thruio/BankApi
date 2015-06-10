<?php

namespace Thru\Bank\Models;

use Thru\ActiveRecord\ActiveRecord;

/**
 * Class Balance
 * @var $transaction_id integer
 * @var $account_id integer
 * @var $run_id integer
 * @var $name text
 * @var $value text
 * @var $state ENUM("Complete","Pending")
 * @var $occured date
 * @var $created date
 * @var $updated date
 */
class Transaction extends ActiveRecord{

  protected $_table = "transactions";

  public $transaction_id;
  public $account_id;
  public $run_id;
  public $name;
  public $value;
  public $state = "Complete";
  public $occured;
  public $created;
  public $updated;

  public function save(){
    $this->updated = date("Y-m-d H:i:s");
    if(!$this->created){
      $this->created = date("Y-m-d H:i:s");
    }
    parent::save();
  }
}
