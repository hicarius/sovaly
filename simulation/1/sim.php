<!DOCTYPE html>
<html>
	<head>
		<link href="_assets/css/shared.css" rel="stylesheet" type="text/css"/>
		<link href="_assets/css/examples.css" rel="stylesheet" type="text/css"/>
		<script src="_assets/js/examples.js"></script>
		<script src="lib/jquery.min.js"></script>
		<script src="_assets/libs/preloadjs-NEXT.min.js"></script>
		<script src="_assets/libs/preloadjs-NEXT.min.js"></script>

		<script src="lib/easeljs-NEXT.combined.js"></script>
		<script>
			var stage, w, h, loader;
			var sky, hill, hill1, ground2, rail, ground;
			var horses = [], casaques = [], pants = [], numbers = [], horseFirst, lastVitesse = [], currentVitesse = [];
			var middleReach = false;
			var horseUp = [];
			var middleCanvas;
			var firstLine;
			var distance = [], distanceArray = [];
			var text ;
			var distImg;
			var multiplicateurVitesse = 2, ecartP = [false, 0];

			/**
			 * 0.2 : 812m/min
			 * 0.25 : 820m/min
			 * 0.3 : 829m/min 750m/min
			 *
			 **/

			var race = {
					"lenght": 1000,
					"type" : "galop",
					"horses": [
						{
							color: "Black",
							framerate : 14,
							vitesse : {0:0.3, 100:0.3, 200: 0.3, 300: 0.3, 400: 0.3, 500: 0.3, 600: 0.3, 700: 0.3, 800: 0.3, 900: 0.3, 1000: 0.3}
						},
						{
							color: "Grey",
							framerate : 18,
							vitesse : {0:0.3, 100:0.4, 200: 0.2, 300: 0.4, 400: 0.1, 500: 0.2, 600: 0.2, 700: 0.2, 800: 0.3, 900: 0.4, 1000: 0.4}
						},
						{
							color: "Bay",
							framerate : 13,
							vitesse : {0:0.1, 100:0.3, 200: 0.4, 300: 0.2, 400: 0.1, 500: 0.2, 600: 0.4, 700: 0.4, 800: 0.4, 900: 0.3, 1000: 0.3}
						}
					]
				}
				;

			$( document ).ready(function(){

				for(var j = 0; j <= race.lenght; j+=100){
					distanceArray.push(j);
				}

				init();
				chrono();
			});
		</script>
		<script src="mySim.js"></script>
	</head>
	<body>
		<canvas id="raceCanvas" width="960" height="470"></canvas>
		<form name="forsec">
			<input type="text" size="3" name="secb"> minute(s)
			<input type="text" size="3" name="seca"> secondes
			<input type="text" size="3" name="secc"> dixièmes


			<input type="button" value="RaZ" onclick="rasee()">
			<input type="button" value="Tempo" onclick="clearTimeout(compte); $('#distance7').val(distance)">
			//arrête temporairement la fonction chrono()
			<input type="text" id="distance7" value="" />
		</form>
	</body>
</html>
