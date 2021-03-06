<?php
?>
<!DOCTYPE >
<html>
	<head>
		<title>Implementation of PriceMinister API</title>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.66.0-2013.10.09/jquery.blockUI.min.js"></script>
		<style>
			body {
				background: #fcfffc;
			}
			.container {
				width: 400px;
				height: 100px;
				margin: 50px auto auto auto;
				background: #f0fff0;
				padding: 10px;
				border-radius: 5px;
			}
			.label_col {
				width: 45%;
				padding: 5px;
				float: left;
			}
			.field {
				width: 45%;
				padding: 5px;
				float: left;
			}
		</style>
		<script>
			$(document).ready(function() {

				$("#submit").click(function() {
					var message = "";
					var pcode = $("#pcode").val();
					if (pcode == '') {
						message = "Enter pass code.";
					}
					$("#download_panel").hide();
					var file = $("#ean_file").val();
					if (file == '') {
						if (message != "") {
							message += "\n";
						}
						message += "Select an input CSV file.";
					}
					if (message != "") {
						alert(message);
						return;
					} else {

						$.blockUI({
							css : {
								border : 'none',
								padding : '15px',
								backgroundColor : '#000',
								'-webkit-border-radius' : '10px',
								'-moz-border-radius' : '10px',
								opacity : .5,
								color : '#fff',
								borderRadius : '10px'
							},
							message : "The operation may take few minutes." + "<br>" + "Please wait..."
						});

						$.ajax({
							url : 'function.php',
							type : 'post',
							dataType : 'json',
							data : {
								pcode : pcode,
								action : 'pcheck'
							},
							success : function(response) {
								if (response.success == true) {
									var formData = new FormData();
									formData.append('file', $('#ean_file')[0].files[0]);
									formData.append('action', 'process');
									//debugger;
									$.ajax({
										url : 'function.php',
										type : 'post',
										dataType : 'json',
										processData : false, // tell jQuery not to process the data
										contentType : false, // tell jQuery not to set contentType
										data : formData,
										success : function(response) {
											$.unblockUI();
											if (response.success == true) {
												$("#download_panel").show();
												alert("The output file is generated successfully. Now you can download it.");
											} else {
												$("#download_panel").hide();
												alert(response.messgae);
											}
										},
										error : function(xHR, error, errorText) {
											$.unblockUI();
											alert("Error occurred! The error is: " + errorText);
										}
									});
								} else {
									$.unblockUI()
									alert(response.message);
								}
							},
							error : function(xHr, error, errorText) {
								$.unblockUI()
								alert('Ajac call error. The error is: ' + errorText);
							}
						});

					}
				});
			});
		</script>
	</head>
	<body>
		<header>
			<h1 style="margin-left:auto;margin-right:auto; width:600px;">Implementation of PriceMinister.com API</h1>
		</header>
		<div class="container">
			<form id="form">
				<div class="label_col">
					<label for="pcode">Pass Code: </label>
				</div>
				<div class="field">
					<input type="password" id="pcode" name="pcode">
				</div>
				<div class="label_col">
					<label for="ean_file">Upload EAN CSV File: </label>
				</div>
				<div class="field">
					<input type="file" id="ean_file" name="ean_file" placeholder="Upload CSV file..." value="Upload" required="" accept=".csv">
				</div>

				<input type="hidden" name="action" value="process">
			</form>

			<div class="label_col">

			</div>
			<div class="field">
				<input type="button" class="btn" value="Submit" id="submit">
			</div>
			<div style="clear: both;"></div>
			<div id="download_panel" style="display: none;">
				<div class="label_col">
					<label for="download">Download the output file: </label>
				</div>
				<div class="field">
					<a href="function.php?action=download" id="download">Download</a>
				</div>
			</div>
		</div>
	</body>
</html>
<?php
?>