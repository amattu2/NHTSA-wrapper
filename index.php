<?php
/*
 * Produced: Thu Mar 09 2023
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2023 Alec M.
 * License: License GNU Affero General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use amattu2\NHTSA;

// Files
require("src/NHTSA.php");

// Optional VIN $_GET input
$vin = isset($_GET['vin']) && !empty($_GET['vin']) ? $_GET['vin'] : "2B3KA43R86H389824";

// Perform raw decode
echo "<h1>Perform raw decode</h1>", "<pre>";
$decode = NHTSA::decodeVIN($vin);
print_r($decode);
echo "</pre>";

// Pretty decode + parse (Year, Make, Model, Trim, Engine)
echo "<h1>Pretty parse a raw decode</h1>", "<pre>";
print_r(NHTSA::parseDecode($decode));
echo "</pre>";

// Fetch recalls
//echo "<h1>Perform raw recall request</h1>", "<pre>";
//$recalls = amattu\NHTSA::getRecalls(2015, "Ford", "Mustang");
//print_r($recalls);
//echo "</pre>";

// Pretty parse recalls
//echo "<h1>Pretty parse raw recall request</h1>", "<pre>";
//print_r(amattu\NHTSA::parseRecalls($recalls));
//echo "</pre>";
