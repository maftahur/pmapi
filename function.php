<?php
/**
 * This file will be used for both processing the API calls for output and download the output file.
 */
ob_start();
session_start();
$response = array();

$passowrd = "@!9X0@!";

$ean_input_file = "EANs.csv";
$ean_output_file = "EANs_OUTPUT.csv";

if (isset($_POST['action'])) {
	$action = $_POST['action'];
	if ($action == 'pcheck') {
		if (isset($_POST['pcode'])) {
			$pcode = $_POST['pcode'];
			if ($pcode == $passowrd) {
				$_SESSION['password'] = true;
				$response = array("success" => true, "message" => "successful");
			} else {
				$response = array("success" => false, "message" => "Pass code doesn't match");
			}
		} else {
			$response = array("success" => false, "message" => "Pass code is not provided");
		}
	} else if ($action == "process") {
		if (isset($_SESSION['password'])) {
			move_uploaded_file($_FILES["file"]["tmp_name"], $ean_input_file);
			//Then call the API with the user input file.
			if (file_exists($ean_input_file)) {

				set_time_limit(0);

				require_once "CsvImporter.php";

				$eans = array();
				$csvImporter = new CsvImporter($ean_input_file, false, ",");
				while ($rows = $csvImporter -> get(10000)) {
					foreach ($rows as $row) {
						$eans[] = $row[0];
					}
				}
				//We can include maximum 100 EANs API Call. So call the api for every 100 EANs.
				//Also it is better to get result for 100 EANs and write them in output file.

				$i = 0;
				$eans_string = "";
				$file_no = 0;
				$eans_data = array();
				$processed_eans = array();

				$total_eans = count($eans);
				$count = 0;

				//Simple creates the file if not exists or clear the content.
				$handle = fopen($ean_output_file, "w");
				fclose($handle);
				$is_first = true;

				foreach ($eans as $ean) {
					if ($eans_string != "") {
						$eans_string .= ",";
					}

					$eans_string .= $ean;
					$processed_eans[] = $ean;
					$i++;
					$count++;
					if ($i == 100 || $count == $total_eans) {

						$i = 0;
						$url = "http://ws.priceminister.com/listing_ws?action=listing&login=&version=2014-11-04&scope=&kw=&nav=&refs={$eans_string}&productids=&nbproductsperpage=&pagenumber=";
						$xml = file_get_contents("{$url}");
						//We have used the eans string so reset the value.
						$eans_string = "";
						$file_no++;
						//file_put_contents("response{$file_no}.xml", $xml);
						if ($xml) {
							//Now parse the XML and consturct data to be exported.
							$doc = new DOMDocument();
							$doc -> loadXML($xml);
							$products = $doc -> getElementsByTagName("product");
							$eans_data = array();
							foreach ($products as $product) {
								$refs = "";
								$product_id = "";
								$url = "";
								$img_url = "";

								//Reference
								$reference_tags = $product -> getElementsByTagName("barcode");
								if ($reference_tags -> length > 0) {
									$refs = $reference_tags -> item(0) -> nodeValue;
								}

								//Product ID
								$product_id_tags = $product -> getElementsByTagName("productid");
								if ($product_id_tags -> length > 0) {
									$product_id = $product_id_tags -> item(0) -> nodeValue;
								}

								//Url and Image Url
								$url_tags = $product -> getElementsByTagName("url");
								if ($url_tags -> length > 0) {
									//First url is the 'url' and the url inside "image" tag is the url for image.
									$url = $url_tags -> item(0) -> nodeValue;
									if ($url_tags -> length > 1) {
										$img_url = $url_tags -> item(1) -> nodeValue;
										if (strpos($img_url, "/photo/") === false) {
											$img_url = "";
										}
									}
								}

								$eans_data = array_merge($eans_data, array("id{$refs}" => array("product_id" => $product_id, "url" => $url, "img_url" => $img_url)));

							}//End of $product loop

							//Write the data in output file.
							$handle = fopen($ean_output_file, "a");
							$csv = "";

							foreach ($processed_eans as $processed_ean) {
								if ($is_first == false) {
									$csv .= PHP_EOL;
								}
								if ($is_first == true) {
									$is_first = false;
								}
								if (array_key_exists("id{$processed_ean}", $eans_data)) {
									/*if ($csv != "") {
									 $csv .= "\n";
									 }*/

									$csv .= $processed_ean . "," . $eans_data["id{$processed_ean}"]['product_id'] . "," . $eans_data["id{$processed_ean}"]['url'] . "," . $eans_data["id{$processed_ean}"]['img_url'];

								} else {
									/*if ($csv != "") {
									 $csv .= "\n";
									 }*/

									$csv .= $processed_ean . ",,,";
								}
							}

							fwrite($handle, $csv);
							fclose($handle);

						}//End of if($xml)
						else {
							$handle = fopen($ean_output_file, "a");
							$csv = "";
							foreach ($processed_eans as $processed_ean) {
								if ($csv != "") {
									$csv .= PHP_EOL;
								}

								$csv .= $processed_ean . ",,,";
							}
							fwrite($handle, $csv);
							fclose($handle);
						}

						//Reset the $processed_eans
						$processed_eans = array();

					}//End of if($i==100)

					/*if ($file_no == 5) {
					 break;
					 }*/
				}//End of foreach loop.

				//echo "fileno: {$file_no}";
				$response = array("success" => true, "message" => "The output file generated. Download it!");
			} else {
				$response = array("success" => false, "message" => "Failed to upload file. So no output file is generated");
			}
		} else {
			$response = array("success" => false, "message" => "You haven't sent your pass code!");
		}
	} else {
		$response = array("success" => false, "message" => "Invalid request type");
	}
} else if (isset($_GET['action'])) {
	$action = $_GET['action'];
	if ($action == "download") {
		if (isset($_SESSION['password'])) {
			//Start downloading process
			if (file_exists($ean_output_file)) {
				// output headers so that the file is downloaded rather than displayed
				header('Content-Type: text/csv;');
				header('Content-Disposition: attachment; filename=' . $ean_output_file);

				$csv = file_get_contents($ean_output_file);

				//output the content.
				echo $csv;
				exit ;
			} else {
				//$response = array("success" => false, "message" => "Couldn't find the output file.");
				echo "The file is not found!";
				exit ;
			}
		} else {
			echo "Password is not set!";
			exit ;
		}

	} else {
		echo "<h1>Invalid request.";
		exit ;
	}
}

echo json_encode($response);
?>