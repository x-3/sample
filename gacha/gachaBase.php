<?php
namespace Sample;

/**
 * Gacha
 */
class GachaBase
{
  const DEFAULT_RATE = 100;

  protected $_rate_sum = 0; // sum of the rate after adjustment
  protected $_rate_data = null;

  /**
   * Constructor
   *
   * @param string $data_file rate data file.
   *
   * @throws Exception errors e.g. apc, read file, json encode.
   */
  function __construct($data_file, $additional_data = array()) {
    if (apc_exists($data_file)) {
      $this->_rate_data = apc_fetch($key);
      if ($this->_rate_data === FALSE) {
        throw new \Exception('preparing data failed.');
      }
    } else {
      $data = file_get_contents($data_file);
      if ($data === FALSE) {
        throw new \Exception('file_get_contents failed.');
      }

      $this->_rate_data = json_decode($data, true);
      if ($this->_rate_data === null) {
        throw new \Exception('json convert error.');
      }
      if (!$this->adjustRate($additional_data)) {
        throw new \Exception('adjust rate error.');
      }

      if (!apc_store($data_file, $data)) {
        error_log("key: $data_file apc_store failed.");
      }
    }
  }

  /**
   * Draw gacha.
   *
   * @param int $num how many times draw gacha.
   *
   * @return array list of the gacha result.
   */
  public function draw($num = 1) {
    $result = array();

    for ($i = 0; $i < $num; $i++) {
      $hit = mt_rand(1, $this->_rate_sum);
      $total = 0;
      foreach ($this->_rate_data['rate'] as $key => $value) {
        $total += $value['rate'];
        if ($hit <= $total) {
          $result[] = $key;
          break;
        }
      }
    }
    return $result;
  }


  /**
   * Adjust rating data.
   *
   * Adjust rating data and set sum of rate.
   *
   * @return bool success or failure to adjust data.
   */

  protected function adjustRate($additional_data = array()) {
    $adjust_data = $this->_rate_data['rate'];

    // get the max place after the decimal point from rate data.
    $max_place = 0;
    foreach ($adjust_data as $key => $value) {
      $place = $this->getPlaceAfterTheDecimalPoint($value['rate']);
      if ($place > $max_place) {
        $max_place = $place;
      }
    }

    // adjust rate data to make it easier with mt_rand.
    foreach ($adjust_data as $key => $value) {
      $adjust_rate = $value['rate'] * pow(10, $max_place);
      $adjust_data[$key]['rate'] = $adjust_rate;
      $this->_rate_sum += $adjust_rate;
    }

    if ($this->_rate_sum > mt_getrandmax()) {
      error_log("rate sum is more than mt_getrandmax(). need to fix rate data.");
      return false;
    }

    $this->_rate_data['rate'] = $adjust_data;
    return true;
  }

  /**
   * get place after the decimal point.
   *
   * @return int place after the decimal point.
   */
  protected function getPlaceAfterTheDecimalPoint($num) {
    return strlen("$num") - strrpos("$num", '.') - 1;
  }

}
