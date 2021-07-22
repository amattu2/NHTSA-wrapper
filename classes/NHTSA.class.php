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
    "decode" => "https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/%s?format=json",
    "recalls" => "https://one.nhtsa.gov/webapi/api/Recalls/vehicle/modelyear/%d/make/%s/model/%s?format=json",
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

    // Check Return Data
    if (!$result || !isset($result["Results"]) || !isset($result["Count"])) {
      return null;
    }

    // Parse Data
    foreach($result["Results"] as $item) {
      // Checks
      if (!$item['Value'] || !$item['Variable']) { continue; }
      if ($item['Variable'] === "Error Text") { continue; }
      if ($item['Value'] === "Not Applicable") { continue; }

      // Variables
      $parsed_result[$item['Variable']] = $item['Value'];
    }

    // Return
    return isset($result["Count"]) && $result["Count"] > 0 && !isset($parsed_result["Error Code"]) ? $parsed_result : null;
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
    $parsed_result['Trim'] = self::parse_trim($result);

    // Parse Engine
    $parsed_result['Engine'] = self::parse_engine($result);

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
    return $result && isset($result["Count"]) && $result["Count"] > 0 ? $result["Results"] : null;
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
   * Parse all Trim options from NHTSA decode
   *
   * @param array raw decode result
   * @return string Formatted Trim decode
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-07-22T11:58:01-040
   */
  private static function parse_trim(array $result) : string
  {
    // Formatted Trim
    $trim = "";

    // Supplied Trim
    if (isset($result['Trim']) && !empty($result['Trim'])) {
      $trim['Trim'] = strtoupper($result['Trim']);
    }

    // Drive Train
    if (isset($result['Drive Type']) && !empty($result['Drive Type'])) {
      if (strpos($result["Drive Type"], "RWD") !== false) {
        $trim["Trim"] .= " RWD";
      } else if (strpos($result["Drive Type"], "FWD") !== false) {
        $trim["Trim"] .= " FWD";
      } else if (strpos($result["Drive Type"], "4WD") !== false) {
        $trim["Trim"] .= " 4WD";
      } else if (strpos($result["Drive Type"], "AWD") !== false) {
        $trim["Trim"] .= " AWD";
      }
    }

    // Return Formatted Result
    return strtoupper(trim(preg_replace('/\s\s+/', ' ', $trim['Trim'])));
  }

  /**
   * Parse all of the Engine options from NHTSA decode
   *
   * @param array raw decode result
   * @return string Formatted Engine decode
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-07-22T11:30:38-040
   */
  private static function parse_engine(array $result) : string
  {
    // Formatted Engine
    $engine = "";

    // Displacement
    if (isset($result['Displacement (L)']) && !empty($result['Displacement (L)'])) {
      $engine = sprintf("%0.1f", $result['Displacement (L)']) ."L";
    }

    // Cylinders
    if (isset($result['Engine Number of Cylinders']) && !empty($result['Engine Number of Cylinders'])) {
      $engine .= " ". $result['Engine Number of Cylinders'] ."-Cyl";
    }

    // Fuel Type
    if (isset($result['Fuel Type - Primary']) && !empty($result['Fuel Type - Primary'])) {
      switch (strtolower($result['Fuel Type - Primary'])) {
        case "diesel":
          $engine .= " (DIESEL)";
          break;
        case "flex":
          $engine .= " (FLEX)";
          break;
      }
    }

    // Model/Cubic-Centimeters Denotation
    if (isset($result['Engine Model']) && !empty($result['Engine Model']) && strlen($result["Engine Model"]) <= 30) {
      $engine .= " (". $result['Engine Model'] .")";
    } else if (isset($result['Displacement (CC)']) && !empty($result['Displacement (CC)'])) {
      $engine .= " (". number_format($result['Displacement (CC)']) ."cc)";
    }

    // Valve Design
    if (preg_match('%\b(DOHC|SOHC|CVA|OHV)\b%i', $engine) == 0 && isset($result["Valve Train Design"]) && !empty($result["Valve Train Design"])) {
      if (strpos($result["Valve Train Design"], "DOHC") !== false) {
        $engine .= " (DOHC)";
      } else if (strpos($result["Valve Train Design"], "SOHC") !== false) {
        $engine .= " (SOHC)";
      } else if (strpos($result["Valve Train Design"], "OHV") !== false) {
        $engine .= " (OHV)";
      } else if (strpos($result["Valve Train Design"], "CVA") !== false) {
        $engine .= " (CVA)";
      }
    }

    // Return Formatted Result
    return strtoupper(trim(preg_replace('/\s\s+/', ' ', $engine)));
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