<!DOCTYPE html>
<html>
<head>
	<title>Sizmeksify</title>
	<script src="../global/jquery.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../global/pure-min.css">
	<link rel="stylesheet" type="text/css" href="../global/style.css">
	<link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
<div class="container">
	<img id="logo" src="img/logo.png">
	<form class="pure-form" id="sizmeksify">
		<div class="pure-g">
			<span class="pure-u-6-24 title bold">HTML5 Workspace<br/>( ZIP-file )</span>
			<input class="pure-u-18-24" type="file" name="zip" id="zip" accept=".zip" required>
		</div>
		<div class="pure-g">
			<div class="pure-u-7-24">
				<div class="pure-g">
					<label class="pure-u-1-5">Width: </label>
					<input class="pure-u-3-5"  type="number" name="width" required>
					<label class="pure-u-1-5">px.</label>
				</div>
			</div>
			<div class="pure-u-7-24">
				<div class="pure-g">
					<label class="pure-u-1-5">Height: </label>
					<input class="pure-u-3-5" type="number" name="height" required>
					<label class="pure-u-1-5">px.</label>
				</div>
			</div>
			<div class="pure-u-10-24">
				<div class="pure-g">
					<label class="pure-u-1-5">Format: </label>
					<select class="pure-u-4-5" name="format">
						<option value="standard">HTML5 Standard Banner</option>
						<option value="rich">HTML5 Rich Media Banner</option>
					</select>
				</div>
			</div>
		</div>
		<div class="pure-g">
			<button class="pure-button pure-button-primary">Validate Workspace</button>
		</div>
	</form>
	<div id="file_info">
		<h2 id="file_name"></h2>
		<div class="progress-bar">
			<span class="bold size"></span>
			<div class="bar"></div>
		</div>
	</div>
	<div id="errors_container" class="alerts-container">
		<div class="title">Errors</div>
		<div id="errors"></div>
	</div>
	<div id="checks_container" class="alerts-container">
		<div class="title">Checks ( <span id="checks_num">0</span> / <span id="total_checks">4</span> ) </div>
		<div id="checks"></div>
	</div>
</div>
<script src="js/script.js"></script>
</body>
</html>
