# Introduction

This is a simple VIN decoder wrapper for the [NHTSA (United States Department of Transportation) VIN decoder API](https://vpic.nhtsa.dot.gov/api/). Additionally, it includes the NHTSA vehicle recall API. See usage section below or the example `index.php` file.

# Usage

## Import

Install with composer

```bash
require amattu2/nhtsa-wrapper
```

```PHP
require "vendor/autoload.php";
```

## VIN Decode

### Raw Decode

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
$decode = amattu2\NHTSA\Client::decodeVIN("VIN_NUMBER");
```

Success return result

```txt
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

### Pretty Decode

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
$decode = amattu2\NHTSA\Client::decodeVIN("VIN_NUMBER");
$pretty_decode = amattu2\NHTSA\Client::parseDecode($decode);
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

## Recalls

### Raw Recalls

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
$recalls = amattu2\NHTSA\Client::getRecalls(2015, "Ford", "Mustang");
```

Success return result

```txt
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
  ...
)
```

### Pretty Recalls

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
$recalls = amattu2\NHTSA\Client::getRecalls(2015, "Ford", "Mustang");
$pretty_recalls = amattu2\NHTSA\Client::parseRecalls($recalls);
```

Success return result

```txt
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
  ...
)
```

## getModelsForMakeByYear

PHPDoc

```PHP
/**
 * Get all available models for a make by year
 *
 * @note This is a free-text match. Recommend using `getModelsForMakeIdByYear`
 * @param int $year The model year
 * @param string $make The make
 */
```

Usage

```PHP
$models = amattu2\NHTSA\Client::getModelsForMakeByYear(2015, "Ford");
```

Success return result

```txt
Array
(
  [0] => Array
  (
    [Make_ID] => 441
    [Make_Name] => FORD
    [Model_ID] => 2021
    [Model_Name] => C-MAX
  )
  ...
)
```

## getModelsForMakeIdByYear

PHPDoc

```PHP
/**
 * Get all available models for a make by year
 *
 * @param int $year The model year
 * @param int $make_id The make ID
 */
```

Usage

```PHP
$models = amattu2\NHTSA\Client::getModelsForMakeIdByYear(2015, 441);
```

Success return result

```txt
Array
(
  [0] => Array
  (
    [Make_ID] => 441
    [Make_Name] => FORD
    [Model_ID] => 2021
    [Model_Name] => C-MAX
  )
  ...
)
```

## getModelsForMake

PHPDoc

```PHP
/**
 * Get all available models for a make
 *
 * @param string $make The make
 */
```

Usage

```PHP
$models = amattu2\NHTSA\Client::getModelsForMake("Ford");
```

Success return result

```txt
Array
(
  [0] => Array
  (
    [Make_ID] => 441
    [Make_Name] => FORD
    [Model_ID] => 2021
    [Model_Name] => C-MAX
  )
  ...
)
```

## getModelsForMakeId

PHPDoc

```PHP
/**
 * Get all available models for a make
 *
 * @param int $make_id The make ID
 */
```

Usage

```PHP
$models = amattu2\NHTSA\Client::getModelsForMakeId(441);
```

Success return result

```txt
Array
(
  [0] => Array
  (
    [Make_ID] => 441
    [Make_Name] => FORD
    [Model_ID] => 2021
    [Model_Name] => C-MAX
  )
  ...
)
```

# Requirements & Dependencies

- PHP 7.4+
- cURL Extension
