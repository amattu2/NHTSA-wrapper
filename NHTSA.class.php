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
  private const K = "Variable";
  private const KI = "VariableId";
  private const V = "Value";
  private const VI = "ValueId";
  private const V_MODEL_YEAR = 29;
  private const V_MAKE = 26;
  private const V_MODEL = 28;
  private const V_TRIM = 38;
  private const V_DRIVE_TYPE = 15;
  private const V_BODY_TYPE = 5;
  private const V_ENG_DISPLACEMENT_L = 13;
  private const V_ENG_DISPLACEMENT_CC = 11;
  private const V_ENG_VALVE_DESIGN = 62;
  private const V_ENG_FI = 67;
  private const V_ENG_NUM_CYLINDERS = 9;
  private const V_ENG_FUEL_PRIMARY = 24;
  private const V_ENG_MODEL = 18;
  private const V_ENG_TURBO = 135;
  private const V_ENG_BHP = 71;

  /**
   * Decode a 17-digit VIN
   *
   * @param string vin number
   * @param int model year
   * @return array raw NHTSA result
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
      if (!$item[self::KI] || empty($item[self::K])) { continue; }
      if (empty($item[self::V])) { continue; }
      if ($item[self::K] === "Error Text") { continue; }
      if ($item[self::V] === "Not Applicable") { continue; }

      // Variables
      $parsed_result[$item[self::KI]] = Array(
        "Variable" => $item[self::K],
        "Value" => $item[self::V],
        "ValueId" => $item[self::VI]
      );
    }

    // Return
    return isset($result["Count"]) && $result["Count"] > 0 && !isset($parsed_result[143]) ? $parsed_result : null;
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
    if (isset($result[self::V_MODEL_YEAR]) && is_numeric($result[self::V_MODEL_YEAR][self::V])) {
      $parsed_result["Model_Year"] = $result[self::V_MODEL_YEAR][self::V];
    } else {
      $parsed_result["Model_Year"] = null;
    }

    // Parse Make
    if (isset($result[self::V_MAKE])) {
      $parsed_result['Make'] = strtoupper($result[self::V_MAKE][self::V]);
    }

    // Parse Make
    if (isset($result[self::V_MODEL])) {
      $parsed_result['Model'] = strtoupper($result[self::V_MODEL][self::V]);
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
    if (isset($result[self::V_TRIM])) {
      $trim = strtoupper($result[self::V_TRIM][self::V]);
    }

    // Drive Train
    if (isset($result[self::V_DRIVE_TYPE])) {
      switch ($result[self::V_DRIVE_TYPE][self::VI]) {
        case 1:
          $trim .= " FWD";
          break;
        case 2:
          $trim .= " 4WD";
          break;
        case 3:
          $trim .= " AWD";
          break;
        case 4:
          $trim .= " RWD";
          break;
      }
    }

    // Body Class
    if (isset($result[self::V_BODY_TYPE])) {
      switch ($result[self::V_BODY_TYPE][self::VI]) {
        case 1:
          $trim .= " CONVERTIBLE";
          break;
        case 3:
          $trim .= " COUPE";
          break;
        case 8:
          $trim .= " CUV";
          break;
        case 15:
          $trim .= " WAGON";
          break;
      }
    }

    // Return Formatted Result
    return strtoupper(trim(preg_replace('/\s\s+/', ' ', $trim)));
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

    // Displacement or Cubic-Centimeters
    if (isset($result[self::V_ENG_DISPLACEMENT_L])) {
      $engine .= sprintf("%0.1f", $result[self::V_ENG_DISPLACEMENT_L][self::V]) . "L";
    } else if (isset($result[self::V_ENG_DISPLACEMENT_CC])) {
      $engine .= number_format($result[self::V_ENG_DISPLACEMENT_CC][self::V]) . "CC";
    }

    // Cylinders
    if (isset($result[self::V_ENG_NUM_CYLINDERS])) {
      $engine .= " ". $result[self::V_ENG_NUM_CYLINDERS][self::V] . "-CYL";
    }

    // Model
    if (isset($result[self::V_ENG_MODEL]) && strlen($result[self::V_ENG_MODEL][self::V]) <= 30) {
      $engine .= " " . $result[self::V_ENG_MODEL][self::V];
    }

    // Valve Design
    if (preg_match('%\b(DOHC|SOHC|CVA|OHV)\b%i', $engine) == 0 && isset($result[self::V_ENG_VALVE_DESIGN])) {
      switch ($result[self::V_ENG_VALVE_DESIGN][self::VI]) {
        case 1:
          $engine .= " CVA";
          break;
        case 2:
          $engine .= " DOHC";
          break;
        case 3:
          $engine .= " OHV";
          break;
        case 4:
          $engine .= " SOHC";
          break;
      }
    }

    // Fuel Type
    if (preg_match('%\b(DIESEL|CNG|E85|FLEX)\b%i', $engine) == 0 && isset($result[self::V_ENG_FUEL_PRIMARY])) {
      switch ($result[self::V_ENG_FUEL_PRIMARY][self::VI]) {
        case 1:
          $engine .= " DIESEL";
          break;
        case 6:
          $engine .= " CNG";
          break;
        case 7:
          $engine .= " LNG";
          break;
        case 8:
          $engine .= " H2";
          break;
        case 9:
          $engine .= " LPG";
          break;
        case 10:
          $engine .= " (E85)";
          break;
        case 15:
          $engine .= " (FLEX)";
          break;
      }
    }

    // Fuel Injection Design
    if (preg_match('%\b(SGDI|MPFI|SFI)\b%i', $engine) == 0 && isset($result[self::V_ENG_FI])) {
      switch ($result[self::V_ENG_FI][self::VI]) {
        case 1:
          $engine .= " SGDI";
          break;
        case 2:
          $engine .= " LBGDI";
          break;
        case 3:
          $engine .= " MPFI";
          break;
        case 4:
          $engine .= " SFI";
          break;
        case 6:
          $engine .= " CRDI";
          break;
        case 7:
          $engine .= " UDI";
          break;
      }
    }

    // Turbo Presence
    if (preg_match('%\b(TURBO|TDI)\b%i', $engine) == 0 && isset($result[self::V_ENG_TURBO])) {
      switch ($result[self::V_ENG_TURBO][self::VI]) {
        case 1:
          $engine .= " TURBO";
          break;
      }
    }

    // Engine BHP From
    // i.e. 280BHP
    if (isset($result[self::V_ENG_BHP]) && is_numeric($result[self::V_ENG_BHP][self::V])) {
      $engine .= " ". $result[self::V_ENG_BHP][self::V] . "BHP";
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
