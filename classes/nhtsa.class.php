<?php
/*
  Produced 2021
  By https://amattu.com/links/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Class Namespace
namespace amattu;

/**
 * A https://vpic.nhtsa.dot.gov/api/ API access class
 */
class NHTSA {
  // Class Variables
  private static $endpoints = Array(
    "decode" => "https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/%s?format=json%s",
    "recalls" => "https://one.nhtsa.gov/webapi/api/Recalls/vehicle/modelyear/%d/make/%s/model/%s?format=json"
  );
  private static $minimum_year = 1950;
  private static $minimum_make_length = 3;
  private static $minimum_model_length = 3;

  /**
   * Decode a 17-digit VIN
   *
   * @param string vin number
   * @param ?integer model year
   * @return ?array raw NHTSA result
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
    return $result["Count"] && $result["Count"] > 0 && !isset($parsed_result["Error Code"]) ? $parsed_result : null;
  }

  /**
   * Parse a raw decode call
   * Converts a decodeVIN call into a pretty parsed Year, Make, Model, Trim, Engine array
   *
   * @param array raw decode result
   * @return ?array pretty parsed NHTSA result
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-04-04T16:52:15-040
   */
  public static function parseDecode(array $result = Array()) : ?array
  {
    // Checks
    if (!$result || empty($result)) {
      return null;
    }

    // Variables
    $parsed_result = Array();

    // Parse Year
    if (isset($result['Model Year']) && is_numeric($result['Model Year'])) {
      $parsed_result["Model_Year"] = $result["Model Year"];
    } else {
      $parsed_result["Model_Year"] = null;
    }

    // Parse Make
    if (isset($result['Make']) && !empty($result['Make'])) {
      $parsed_result['Make'] = strtoupper($result['Make']);
    }

    // Parse Make
    if (isset($result['Model']) && !empty($result['Model'])) {
      $parsed_result['Model'] = strtoupper($result['Model']);
    }

    // Parse Trim
    if (isset($result['Trim']) && !empty($result['Trim'])) {
      $parsed_result['Trim'] = strtoupper($result['Trim']);
    }

    // Parse Engine
    if (isset($result['Displacement (L)']) && !empty($result['Displacement (L)'])) {
      $parsed_result['Engine'] = sprintf("%0.1f", $result['Displacement (L)']) ."L";
    }
    if (isset($result['Engine Number of Cylinders']) && !empty($result['Engine Number of Cylinders'])) {
      $parsed_result['Engine'] .= " ". $result['Engine Number of Cylinders'] ."-Cyl";
    }
    if (isset($result['Fuel Type - Primary']) && !empty($result['Fuel Type - Primary']) && strtolower($result['Fuel Type - Primary']) == "diesel") {
      $parsed_result['Engine'] .= " (Diesel)";
    }
    if (isset($result['Engine Model']) && !empty($result['Engine Model'])) {
      $parsed_result['Engine'] .= " (". $result['Engine Model'] .")";
    } else if (isset($result['Displacement (CC)']) && !empty($result['Displacement (CC)'])) {
      $parsed_result['Engine'] .= " (". number_format($result['Displacement (CC)']) ."cc)";
    }
    if (isset($parsed_result['Engine'])) {
      $parsed_result['Engine'] = strtoupper(preg_replace('/\s\s+/', ' ', $parsed_result['Engine']));
    }

    // Return
    return !empty($parsed_result) ? $parsed_result : null;
  }

  /**
   * Get vehicle recalls by Year, Make, Model
   *
   * @param int model year
   * @param string make
   * @param string model
   * @return ?array NHTSA raw result
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-04-04T16:48:24-040
   */
  public static function getRecalls(int $model_year, string $make, string $model) : ?array
  {
    // Checks
    if (!$model_year || $model_year < self::$minimum_year || $model_year > (date("Y") + 2)) {
      return null;
    }
    if (!$make || strlen($make) < self::$minimum_make_length) {
      return null;
    }
    if (!$model || strlen($model) < self::$minimum_model_length) {
      return null;
    }

    // Fetch Recalls
    $endpoint = sprintf(self::$endpoints["recalls"], $model_year, strtoupper($make), strtoupper($model));
    $result = json_decode(self::http_get($endpoint), true);

    // Return Result
    return $result["Count"] && $result["Count"] > 0 ? $result["Results"] : null;
  }

  /**
   * Parse a raw recall result
   *
   * @param array raw recall result
   * @return ?array parsed recall result
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-04-04T18:16:26-040
   */
  public static function parseRecalls(array $recalls = Array()) : ?array
  {
    // Checks
    if (!$recalls || empty($recalls)) {
      return null;
    }

    // Variables
    $parsed_result = Array();

    // Loops
    foreach ($recalls as $recall) {
      // Checks
      if (!$recall["NHTSACampaignNumber"]) {
        continue;
      }
      if (!$recall["ReportReceivedDate"]) {
        continue;
      }
      if (!$recall["Component"] || !is_string($recall["Component"])) {
        continue;
      }
      if (!$recall["Summary"] || !$recall["Remedy"]) {
        continue;
      }

      // Variables
      $parsed_result[] = Array(
        "Campaign_Number" => $recall["NHTSACampaignNumber"],
        "Component" => explode(":", $recall["Component"]) ?: [],
        "Date" => self::parse_timestamp($recall["ReportReceivedDate"])->format("Y-m-d"),
        "Description" => $recall["Summary"] ?: "",
        "Remedy" => $recall["Remedy"] ?: "",
      );
    }

    // Return
    return $parsed_result;
  }

  /**
   * Parse Unix Timestamp (Miliseconds with offset)
   *
   * @param string unix timestamp
   * @return DateTime parsed representation
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @see https://stackoverflow.com/questions/16749778/php-date-format-date1365004652303-0500
   * @date 2021-04-04T18:04:08-040
   */
  private static function parse_timestamp(string $timestamp) : \DateTime
  {
    try {
      // Match Format
      preg_match('/(\d{10})(\d{3})([\+\-]\d{4})/', $timestamp, $matches);

      // Return
      return \DateTime::createFromFormat("U.u.O",vsprintf('%2$s.%3$s.%4$s', $matches));
    } catch (\Exception $e) {
      return new \DateTime();
    }
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
