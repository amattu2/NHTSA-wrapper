<?php
/*
  Produced 2021
  By https://amattu.com/links/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Class Namespace
namespace amattu;

// Exception Classes
class UnknownHTTPException extends \Exception {}
class InvalidHTTPResponseException extends \Exception {}

/**
 * A https://vpic.nhtsa.dot.gov/api/ API access class
 */
class NHTSA {
  // Class Variables
  private static $endpoints = Array(
    "decode" => "https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/%s?format=json%s"
  );
  private static $minimum_year = 1950;

  /**
   * Decode a 17-digit VIN
   *
   * @param string vin number
   * @param ?integer model year
   * @return ?array parsed NHTSA result
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-04-04T16:19:40-040
   */
  public static function decodeVIN(string $vin, int $model_year = 0) : ?array
  {
    // Checks
    if (!$vin || strlen($vin) != 17) {
      return null;
    }
    if ($model_year && ($model_year < self::$minimum_year || $model_year > (date("Y") + 2))) {
      return null;
    }

    // Fetch Data
    $vin = strtoupper($vin);
    $endpoint = sprintf(self::$endpoints["decode"], $vin, ($model_year ? "&modelyear=$model_year" : ""));
    $result = json_decode(self::http_get($endpoint), true);
    $parsed_result = Array();

    // Parse Data
    foreach($result["Results"] as $item) {
      // Checks
      if (!$item['Value']) { continue; }

      // Variables
      $parsed_result[$item['Variable']] = $item['Value'];
    }

    // Return
    return $result["Count"] && $result["Count"] > 0 ? $parsed_result : null;
  }

  /**
   * Perform a HTTP Get request
   *
   * @param string URL
   * @return ?string result body
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-04-03T19:16:29-040
   */
  private static function http_get(string $endpoint) : ?string
  {
    // cURL Initialization
    $handle = curl_init();
    $result = null;
    $error = 0;

    // Options
    curl_setopt($handle, CURLOPT_URL, $endpoint);
    curl_setopt($handle, CURLOPT_HTTPGET, 1);
    curl_setopt($handle, CURLOPT_FAILONERROR, 1);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_MAXREDIRS, 2);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_TIMEOUT, 10);

    // Fetch Result
    $result = curl_exec($handle);
    $error = curl_errno($handle);

    // Return
    curl_close($handle);
    return $result && !$error ? $result : null;
  }
}
?>
