<?php
namespace Sample;

require_once('gachaBase.php');

/**
 * Gacha
 */
class GachaHistoryConsideration extends GachaBase
{
  /**
   * Adjust rating data with consideration of history data.
   *
   * Adjust rating data and set sum of rate and this method will consider the history.
   *
   * @param array $additional_data list of ids how many times drew.
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
      if (array_key_exists($key, $additional_data)) {
        $reduction_num = ($additional_data[$key] > 5) ? 5 : $additional_data[$key];
        for ($i=0; $i<$reduction_num; $i++) {
          $adjust_rate /= 2;
        }
      }
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
}
