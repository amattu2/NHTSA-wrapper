<?php
// Files
require("classes/nhtsa.class.php");

// Perform raw decode
echo "<h1>Perform raw decode</h1>", "<pre>";
$decode = amattu\NHTSA::decodeVIN("2B3KA43R86H389824");
print_r($decode);
echo "</pre>";

// Pretty decode + parse (Year, Make, Model, Trim, Engine)
echo "<h1>Pretty parse a raw decode</h1>", "<pre>";
print_r(amattu\NHTSA::parseDecode($decode));
echo "</pre>";

// Fetch recalls
echo "<h1>Perform raw recall request</h1>", "<pre>";
$recalls = amattu\NHTSA::getRecalls(2015, "Ford", "Mustang");
print_r($recalls);
echo "</pre>";

// Pretty parse recalls
echo "<h1>Pretty parse raw recall request</h1>", "<pre>";
print_r(amattu\NHTSA::parseRecalls($recalls));
echo "</pre>";
?>
