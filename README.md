# Introduction
This is a simple VIN decoder wrapper for the NHTSA (United States Department of Transportation) VIN decoder API. Additionally, it includes the NHTSA vehicle recall API. See usage section below or the example `index.php` file.

# Usage
### Import
```PHP
require("classes/nhtsa.class.php");
```

### VIN Decode
#### Raw Decode
PHPDoc
```PHP
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
```

Usage
```PHP
$decode = amattu\NHTSA::decodeVIN("VIN_NUMBER");
```

Success return result
```
Array
(
  [Error Text] =>
  [Make] =>
  [Manufacturer Name] =>
  [Model] =>
  [Model Year] =>
  [Plant City] =>
  [Series] =>
  [Trim] =>
  [Vehicle Type] =>
  [Plant Country] =>
  ...
)
```

#### Pretty Decode
PHPDoc
```PHP
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
```

Usage
```PHP
$decode = amattu\NHTSA::decodeVIN("VIN_NUMBER");
$pretty_decode = amattu\NHTSA::parseDecode($decode);
```
Success return result (Example)
```PHP
Array
(
  [Model_Year] => 2011
  [Make] => MERCEDES-BENZ
  [Model] => M-CLASS
  [Trim] =>  4-MATIC
  [Engine] => 3.5L 6-CYL (3,500CC)
)
```

### Recalls
#### Raw Recalls
PHPDoc
```PHP
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
```

Usage
```PHP
$recalls = amattu\NHTSA::getRecalls(2015, "Ford", "Mustang");
```

Success return result
```
Array
(
  [0] => Array
    (
        [Manufacturer] =>
        [NHTSACampaignNumber] =>
        [ReportReceivedDate] =>
        [Component] =>
        [Summary] =>
        [Conequence] =>
        [Remedy] =>
        [Notes] =>
        [ModelYear] =>
        [Make] =>
        [Model] =>
    )
  )
)
```

#### Pretty Recalls
PHPDoc
```PHP
/**
 * Parse a raw recall result
 *
 * @param array raw recall result
 * @return ?array parsed recall result
 * @throws TypeError
 * @author Alec M. <https://amattu.com>
 * @date 2021-04-04T18:16:26-040
*/
```

Usage
```PHP
$recalls = amattu\NHTSA::getRecalls(2015, "Ford", "Mustang");
$pretty_recalls = amattu\NHTSA::parseRecalls($recalls);
```

Success return result
```
Array
(
  [2] => Array
  (
    [Campaign_Number] =>
    [Component] => Array
    (
      [0] => FUEL SYSTEM, GASOLINE
      [1] => STORAGE
      [2] => TANK ASSEMBLY /* The last element is the actual component */
    )
    [Date] => 2015-06-02
    [Description] =>
    [Remedy] =>
  )
)
```
# Notes
N/A

# Requirements & Dependencies
PHP 7.0 +
