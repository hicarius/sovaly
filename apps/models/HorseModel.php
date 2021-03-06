<?php
class HorseModel extends Model_Abstract
{

	private $_ramdomGenePref = null;

	public function getHorses()
	{
		$additionalColumns = "s1.name AS proprio, ";
		$additionalColumns .= "CONCAT_WS(' ', s2.firstname, s2.lastname) AS trainer, ";
		$additionalColumns .= "IF(h.eleveur_id=0, 'Inconnu', s3.name) AS eleveur, ";
		$additionalColumns .= "IF(h.father_id=0, 'Inconnu', h2.name) AS father, ";
		$additionalColumns .= "IF(h.mother_id=0, 'Inconnu', h3.name) AS mother, ";
		$additionalColumns .= "IF(h.status=0, 'Inactif', 'Actif') AS status_name, ";
		$additionalColumns .= "IF(h.is_system=0, 'Non', 'Oui') AS is_system_name, ";
		$additionalColumns .= "IF(h.type=0, 'Standard', IF(h.type=1, '- de 1 an', IF(h.type=2, 'Etalon', 'Poulini&egrave;re'))) AS type_name ";
		$joins =  " INNER JOIN stables s1 ON s1.id = h.proprio_id";
		$joins .=  " INNER JOIN stables s2 ON s2.id = h.trainer_id";
		$joins .=  " LEFT JOIN stables s3 ON s3.id = h.eleveur_id";
		$joins .=  " LEFT JOIN horses h2 ON h2.id = h.father_id";
		$joins .=  " LEFT JOIN horses h3 ON h3.id = h.mother_id";
		$query = "SELECT h.*, $additionalColumns FROM horses h $joins  ORDER BY id DESC";
		$stmt = Database::prepare($query);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function load($id, $setData = true)
	{
		$additionalColumns = "s1.name AS proprio, ";
		$additionalColumns .= "CONCAT_WS(' ', s2.firstname, s2.lastname) AS trainer, ";
		$additionalColumns .= "IF(h.eleveur_id=0, 'Inconnu', s3.name) AS eleveur, ";
		$additionalColumns .= "IF(h.father_id=0, 'Inconnu', h2.name) AS father, h2.status AS father_status, ";
		$additionalColumns .= "IF(h.mother_id=0, 'Inconnu', h3.name) AS mother, h3.status AS mother_status, ";
		$additionalColumns .= "IF(h.status=0, 'Inactif', 'Actif') AS status_name, ";
		$additionalColumns .= "IF(h.is_system=0, 'Non', 'Oui') AS is_system_name, ";
		$additionalColumns .= "( IF(h3.father_id=0, 'Inconnu', h4.name) ) AS father_mother, h3.father_id as father_mother_id, h4.status AS father_mother_status, ";
		$additionalColumns .= "h.type AS type_value, IF(h.type=0, IF(h.sexe='F', 'une femelle', 'un m&acirc;le'), IF(h.type=1, '- de 1 an', IF(h.type=2, 'un &eacute;talon', 'une poulini&egrave;re'))) AS type_name ";
		$joins =  " INNER JOIN stables s1 ON s1.id = h.proprio_id";
		$joins .=  " INNER JOIN stables s2 ON s2.id = h.trainer_id";
		$joins .=  " LEFT JOIN stables s3 ON s3.id = h.eleveur_id";
		$joins .=  " LEFT JOIN horses h2 ON h2.id = h.father_id";
		$joins .=  " LEFT JOIN horses h3 ON h3.id = h.mother_id";
		$joins .=  " LEFT JOIN horses h4 ON h4.id = h3.father_id";

		$query = "SELECT h.*, ht.training_trot,ht.training_galop,ht.training_endurance,ht.training_vitesse,ht.training_physique,ht.training_fatigue,
								hc.itr,
								hc.itr_year,
								hc.btr,
								hc.trot_base,
								hc.trot_current as trot_current,
								hc.trot_gene,
								hc.galop_base,
								hc.galop_current as galop_current,
								hc.galop_gene,
								hc.endurance_base,
								hc.endurance_current as endurance_current,
								hc.endurance_gene,
								hc.vitesse_base,
								hc.vitesse_current as vitesse_current,
								hc.vitesse_gene,
								hc.physique,
								hc.fatigue,
								$additionalColumns
					FROM horses h
					LEFT JOIN horses_caracteristique hc ON hc.horse_id = h.id
					LEFT JOIN horses_training ht ON ht.horse_id = h.id
					$joins
					WHERE h.id = :id";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetch();
		if ($result['id']>0) {
			if($setData) {
				$this->_data = $result;
				return $this;
			}else{
				return $result;
			}
		} else {
			return FALSE;
		}
	}

	public function loadByName($text, $additional, $setData = true)
	{
		$where =  'AND ';
		foreach($additional as $k => $column){
			$where .= "$k = $column ";
		}

		$query = "SELECT id, name FROM horses WHERE 1 $where AND lower(name) LIKE '%".strtolower($text)."%'";
		$stmt = Database::prepare($query);
		$stmt->execute();
		$result = $stmt->fetchAll();
		if (count($result)>0) {
			if($setData) {
				$this->_data = $result;
				return $this;
			}else{
				return $result;
			}
		} else {
			return 'no_data_found';
		}
	}

	public function create($data)
	{
		try{

			$query = "INSERT INTO horses (name, proprio_id, trainer_id, eleveur_id, father_id, mother_id, age, corde, sexe, gains, origine, status, is_system, is_qualified, type, robe)
 				  VALUES(:name, :proprio_id, :trainer_id, :eleveur_id, :father_id, :mother_id, :age, :corde, :sexe, :gains, :origine, :status, :is_system, :is_qualified, :type, :robe)";
			$stmt = Database::prepare($query);

			$stmt->bindParam(':name', $data['name']);

			//Set proprietaire/entraineur/eleveur to Stable system
			$stmt->bindParam(':proprio_id', $data['proprio_id']);
			$stmt->bindParam(':trainer_id', $data['trainer_id']);
			$stmt->bindParam(':eleveur_id', $data['eleveur_id']);
			if( $data['is_system'] == 1){
				$defaultStable = $this->_getDefaultStableForCreation($data['age']);
				$stmt->bindParam(':proprio_id', $defaultStable);
				$stmt->bindParam(':trainer_id', $defaultStable);
				$stmt->bindParam(':eleveur_id', $defaultStable);
			}

			$stmt->bindParam(':father_id', $data['father_id']);
			$stmt->bindParam(':mother_id', $data['mother_id']);
			$stmt->bindParam(':age', $data['age']);
			$stmt->bindParam(':corde', $data['corde']);
			$stmt->bindParam(':sexe', $data['sexe']);
			$stmt->bindParam(':gains', $data['gains']);
			$stmt->bindParam(':origine', $data['origine']);
			$stmt->bindParam(':status', $data['status']);
			$stmt->bindParam(':is_qualified', $data['is_qualified']);
			$stmt->bindParam(':type', $data['type']);
			$stmt->bindParam(':is_system', $data['is_system']);
			$stmt->bindParam(':robe', $data['robe']);

			$stmt->execute();

			$id = Database::lastInsertId('horses');

			//create training line
			$query = "INSERT INTO horses_training (horse_id)
 				  VALUES(:horse_id)";
			$stmt = Database::prepare($query);
			$stmt->bindParam(':horse_id', $id);
			$stmt->execute();

			//create gain line
			$query = "INSERT INTO gain_race_horse (horse_id)
 				  VALUES(:horse_id)";
			$stmt = Database::prepare($query);
			$stmt->bindParam(':horse_id', $id);
			$stmt->execute();


			$this->load($id);

			return $id;

		}catch (Exception $e){
			$this->addMessage($e->getMessage(), 'danger');
			return FALSE;
		}
	}

	public function update($data)
	{
		try{

			$query = "UPDATE horses
                      SET name = :name, proprio_id = :proprio_id, trainer_id = :trainer_id, eleveur_id = :eleveur_id, father_id = :father_id,
                                        mother_id = :mother_id, age = :age, corde = :corde, sexe = :sexe, gains = :gains,
                                        origine = :origine, robe = :robe, status = :status, is_system = :is_system
                     WHERE id = :id";
			$stmt = Database::prepare($query);

			$stmt->bindParam(':id', $data['id']);
			$stmt->bindParam(':name', $data['name']);
			$stmt->bindParam(':proprio_id', $data['proprio_id']);
			$stmt->bindParam(':trainer_id', $data['trainer_id']);
			$stmt->bindParam(':eleveur_id', $data['eleveur_id']);
			$stmt->bindParam(':father_id', $data['father_id']);
			$stmt->bindParam(':mother_id', $data['mother_id']);
			$stmt->bindParam(':age', $data['age']);
			$stmt->bindParam(':corde', $data['corde']);
			$stmt->bindParam(':sexe', $data['sexe']);
			$stmt->bindParam(':gains', $data['gains']);
			$stmt->bindParam(':origine', $data['origine']);
			$stmt->bindParam(':robe', $data['robe']);
			$stmt->bindParam(':status', $data['status']);
			$stmt->bindParam(':is_system', $data['is_system']);

			$stmt->execute();

			$this->load($data['id']);
			return $data['id'];
		}catch (Exception $e){
			$this->addMessage($e->getMessage(), 'danger');
			return FALSE;
		}
	}

	public function createPerformance($perfData)
	{
		$columns = '';
		$columnsValue =  '';
		foreach($perfData as $column => $value){
			$columns .= ", $column";
			$columnsValue .= ", '$value'";
		}
		$query = "INSERT INTO horses_caracteristique (horse_id $columns)
 				  VALUES(:horse_id $columnsValue)";
		$stmt = Database::prepare($query);

		$stmt->bindParam(':horse_id', $this->_data['id']);
		$stmt->execute();

		$this->load($this->_data['id']);
	}

	public function setQualityAndPrice()
	{
		$status	= $this->_data['status'];
		$type	= $this->_data['type'];
		$production = $this->getQualityProduction();
		$note = $this->getQuality();

		$evaluation_price = 0;
		$production_price = 0;
		if($type == 2 || $type == 3){
			$production_price =  $production['price'];
		}

		if($status != 0){
			$evaluation_price =   $note['price'];
		}
		$price = $evaluation_price + $production_price;

		if($this->_data['galop_base'] == 0){
			$specialization = 'T';
		}else{
			$specialization = 'G';
		}

		//save price and quality_production and ITR
		$query = "UPDATE horses
					SET evaluation_price = :evaluation_price, production_price = :production_price, price = :price, quality= :quality, quality_production = :quality_production, specialization=:specialization
					WHERE id = :id";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':production_price', $production_price);
		$stmt->bindParam(':evaluation_price', $evaluation_price);
		$stmt->bindParam(':price', $price);
		$stmt->bindParam(':quality', $note['quality']);
		$stmt->bindParam(':quality_production', $production['quality']);
		$stmt->bindParam(':specialization', $specialization);
		$stmt->bindParam(':id', $this->_data['id']);
		$stmt->execute();

		//ITR
		if($this->_data['type'] == 3){
			$itr = $this->getFatherITR();
		} else {
			$itr = $this->_data['itr'];
		}

		//BTR
		 $btr = ($this->_data['trot_gene'] + $this->_data['galop_gene'] + $this->_data['endurance_gene'] + $this->_data['vitesse_gene'] ) / 4;

		//save ITR et BTR
		$query = "UPDATE horses_caracteristique SET itr = :itr, btr = :btr WHERE horse_id = :horse_id";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':itr', $itr);
		$stmt->bindParam(':btr', $btr);
		$stmt->bindParam(':horse_id', $this->_data['id']);
		$stmt->execute();
	}

	/**
	 * @param null $itr si null, utilisable dans Generation
	 * @return array
	 */
	public function getQualityProduction($itr = null)
	{
		if( $itr == null ) {
			if ($this->_data['type'] == 3) {
				$itr = $this->getFatherITR();
			} else {
				$itr = $this->_data['itr'];
			}
		}

		switch($itr){
			case NULL:
				$qualityProduction = 0;
				break;
			case ($itr >= 170):
				$qualityProduction = 5;
				break;
			case ($itr > 170 && $itr >= 160):
				$qualityProduction = 4;
				break;
			case ($itr > 160 && $itr >= 150):
				$qualityProduction = 3;
				break;
			case ($itr > 150 && $itr >= 140):
				$qualityProduction = 2;
				break;
			case ($itr > 140 && $itr >= 120):
				$qualityProduction = 1;
				break;
			case ($itr < 120):
				$qualityProduction = 0;
				break;
			default:
				$qualityProduction = 0;
				break;
		}

		$configurateurQualityProd = array(0, 5000, 25000, 50000, 75000, 125000);
		$price = $configurateurQualityProd[$qualityProduction];

		return array('quality' => $qualityProduction, 'price' => $price);
	}

	/**
	 * @param null $horse si null, utilisable dans Generation
	 * @return array
	 */
	public function getQuality($horse = null)
	{
		$configsNote = array(0, 60, 65, 70, 75, 80, 85, 90, 95, 100, 105);
		if($horse == null){
			$totalPerfBase = $this->_data['trot_base'] + $this->_data['galop_base'] + $this->_data['endurance_base'] + $this->_data['vitesse_base'];
		}else{
			$totalPerfBase = $horse->perf->trot_base + $horse->perf->galop_base + $horse->perf->endurance_base + $horse->perf->vitesse_base;
		}


		foreach($configsNote as $k => $v){
			if(isset($configsNote[$k + 1])) {
				if ($configsNote[$k + 1] >= $totalPerfBase && $totalPerfBase > $configsNote[$k]) {
					$quality = $k+1;
					break;
				}
			}
		}

		$configurateurQuality = array(0, 1000, 2500, 5000, 10000, 15000, 35000, 75000, 100000, 250000, 500000);
		$price = $configurateurQuality[$quality];
		return array('quality' => $quality, 'price' => $price);
	}

	private function _getDefaultStableForCreation($age)
	{
		switch($age){
			case ($age <= 3): $id = DEF_STABLE_ID_YOUNG; break;
			case ($age == 4 OR $age == 5 OR $age == 6): $id = DEF_STABLE_ID_4_5_6_ANS; break;
			case ($age == 7 OR $age == 8 OR $age == 9): $id = DEF_STABLE_ID_7_8_9_ANS; break;
			case ($age == 10): $id = DEF_STABLE_ID_10_ANS; break;
			case ($age > 10): $id = DEF_STABLE_ID_OLD; break;
		}
		return $id;
	}


	public function getFatherITR()
	{
		$father = $this->load( $this->_data['father_id'], false);

		return $father['itr'];
	}

	/*************************************************************************************************************/
	/*******************************************GENERATION********************************************************/
	/*************************************************************************************************************/
	/**
	 * Utilisable dans CRON ou Generation automatique
	 * Génération du cheval en fonction du père et mère
	 * @param $etalonId
	 * @param $pouleId
	 * @return stdClass
	 */
	public function generate($etalonId, $pouleId)
	{
		$horse = new stdClass();

		//HORSE DATA
		//get father data
		$horse->father_id = $etalonId;
		$fatherData = $this->load($etalonId, false);
		//get father data of mother
		$horse->mother_id = $pouleId;
		$motherData = $this->load($pouleId, false);
		if($motherData['father_id']) {
			$fatherOfMotherData = $this->load($motherData['father_id'], false);
		}else{
			$fatherOfMotherData =  null;
		}

		//name
		$horse->name = 'NoName'. rand(100, 99999);
		//age
		$horse->age = rand(2,10);
		//proprietaire/entraineur/jockey ID en fonction de l'age
		$proprio_id = $this->_getDefaultStableForCreation($horse->age);
		$horse->proprio_id = $proprio_id;
		$horse->trainer_id = $proprio_id;
		$horse->eleveur_id = $proprio_id;
		//sexe
		$sexeShuffle = str_shuffle('FM');
		$horse->sexe = $sexeShuffle[0];
		//corde
		if($fatherOfMotherData ==  null){
			$cordeShuffle = str_shuffle("{$fatherData['corde']}{$motherData['corde']}");
		}else{
			$cordeShuffle = str_shuffle("{$fatherData['corde']}{$fatherOfMotherData['corde']}{$motherData['corde']}");
		}
		$horse->corde = $cordeShuffle[0];
		//Robe
		if($fatherOfMotherData ==  null){
			$horse->robe = Commons::array_random( array($fatherData['robe'], $motherData['robe']) );
		}else{
			$horse->robe = Commons::array_random( array($fatherData['robe'], $fatherOfMotherData['robe'], $motherData['robe']) );
		}

		//gains
		$horse->gains = 0;
		//origine
		$horse->origine = $fatherData['origine'];
		//status 0=inactif, 1=actif
		$horse->status = 1;
		//type 0=standard, 1=-1an, 2=etalon, 3=poulinières
		$horse->type = 0;
		//qualifié
		$horse->is_qualified = 0;
		//système
		$horse->is_system = 0;

		//HORSE PERFORMANCE DATA
		$horse->perf = new stdClass();
		//ITR
		if($horse->sexe == 'F'){
			$horse->perf->itr = $fatherData['itr'];
		}else{
			$horse->perf->itr = 1;
		}

		//Qualité de réproduction
		$QReprodutcion = $this->getQualityProduction($horse->perf->itr);
		$horse->quality_production = $QReprodutcion['quality'];
		$horse->production_price = 0;

		//Qualité d'évaluation BTR --> 33% père, 33% père de mère, 33% random
		$this->getBasePerf($horse, 'trot', $fatherData, $fatherOfMotherData, $motherData);
		$this->getBasePerf($horse, 'galop', $fatherData, $fatherOfMotherData, $motherData);
		$this->getBasePerf($horse, 'endurance', $fatherData, $fatherOfMotherData, $motherData);
		$this->getBasePerf($horse, 'vitesse', $fatherData, $fatherOfMotherData, $motherData);

		$QEvaluation = $this->getQuality($horse);
		$horse->quality = $QEvaluation['quality'];
		$horse->evaluation_price = $QEvaluation['price'];
		$this->performPerfBase($horse);

		$horse->price = $horse->production_price + $horse->evaluation_price;

		//moyenne des BTR de ses parents
		$this->getGenePerf($horse, 'trot', $fatherData, $fatherOfMotherData, $motherData);
		$this->getGenePerf($horse, 'galop', $fatherData, $fatherOfMotherData, $motherData);
		$this->getGenePerf($horse, 'endurance', $fatherData, $fatherOfMotherData, $motherData);
		$this->getGenePerf($horse, 'vitesse', $fatherData, $fatherOfMotherData, $motherData);

		//base * multiplier selon l'age
		$this->getCurrentPerf($horse, 'trot', $fatherData, $fatherOfMotherData, $motherData);
		$this->getCurrentPerf($horse, 'galop', $fatherData, $fatherOfMotherData, $motherData);
		$this->getCurrentPerf($horse, 'endurance', $fatherData, $fatherOfMotherData, $motherData);
		$this->getCurrentPerf($horse, 'vitesse', $fatherData, $fatherOfMotherData, $motherData);

		//BTR
		if( $horse->perf->galop_base == 0){
			$horse->perf->btr = ($horse->perf->trot_gene + $horse->perf->endurance_gene + $horse->perf->vitesse_gene ) / 3;
		}elseif( $horse->perf->trot_base == 0){
			$horse->perf->btr = ($horse->perf->galop_gene + $horse->perf->endurance_gene + $horse->perf->vitesse_gene ) / 3;
		}else{
			$horse->perf->btr = ($horse->perf->trot_gene + $horse->perf->galop_gene + $horse->perf->endurance_gene + $horse->perf->vitesse_gene ) / 4;
		}

		//Specialization
		if($horse->perf->galop_base == 0){
			$horse->specialization = 'T';
		}else{
			$horse->specialization = 'G';
		}

		//Physique
		$horse->perf->physique = 100;

		//Creation du cheval
		$dataHorse = (array)$horse;
		$columns = '';
		$columnsValue =  '';
		foreach($dataHorse as $column => $value){
			if($column != 'perf') {
				$columns .= ",$column";
				$columnsValue .= ",'$value'";
			}
		}
		$query = "INSERT INTO horses (id $columns)
 				  VALUES('' $columnsValue)";
		$stmt = Database::prepare($query);
		$stmt->execute();
		$horseId = Database::lastInsertId('horses');

		//Création performance
		$columns = '';
		$columnsValue =  '';
		foreach((array)$dataHorse['perf'] as $column => $value){
			$columns .= ", $column";
			$columnsValue .= ", '$value'";
		}
		$query = "INSERT INTO horses_caracteristique (horse_id $columns)
 				  VALUES(:horse_id $columnsValue)";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $horseId, PDO::PARAM_INT);
		$stmt->execute();

		//create training line
		if($horse->perf->galop_base == 0){
			$training_trot = 2;
			$training_galop = 0;
		}else{
			$training_trot = 0;
			$training_galop = 2;
		}
		$query = "INSERT INTO horses_training (horse_id, training_trot, training_galop, training_endurance, training_vitesse, training_physique )
 				  VALUES(:horse_id,{$training_trot},{$training_galop},2,2,2)";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $horseId, PDO::PARAM_INT);
		$stmt->execute();

		//create gain line
		$query = "INSERT INTO gain_race_horse (horse_id)
 				  VALUES(:horse_id)";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $horseId, PDO::PARAM_INT);
		$stmt->execute();

		return $horse;
	}

	/**
	 * BTR --> 33% père, 33% père de mère, 33% random
	 * si gene = 100%, 33% du 100%
	 * @param $horse
	 */
	public function getBasePerf(&$horse, $perfKey ,$fatherData, $fatherOfMotherData, $motherData)
	{
		//vitesse entre 20 et 45
		//min 20, moyenne 25, max 30
		if($perfKey == 'vitesse'){
			$perfMax = 45;
		} else {
			$perfMax = 30;
		}

		//BASE
		//father
		$fatherGenetic = $fatherData[$perfKey . '_base'] * $fatherData[$perfKey . '_gene'] / 100 ;
		$parFather = $fatherGenetic * 33 / 100 ;

		//mother
		//$motherGenetic = $motherData[$perfKey . '_base'] * $motherData[$perfKey . '_gene'] / 100 ;
		//$parMother = $motherGenetic * 10 / 100 ;

		//father of mother
		if($fatherOfMotherData == null){
			$parFatherOfMother = $parFather;
		}else{
			$fatherOfMotherGenetic = $fatherOfMotherData[$perfKey . '_base'] * $fatherOfMotherData[$perfKey . '_gene'] / 100 ;
			$parFatherOfMother = $fatherOfMotherGenetic * 33 / 100 ;
		}



		//random
		$btrParent = ($fatherData['btr']+$fatherOfMotherData['btr'])/2; //moyenne Btr de leur parent
		$totalPar = ($perfMax - ($parFather + $parFatherOfMother ));	//perf max - perf acquis  en fonction du btr de leur parent
		//if($this->_ramdomGenePref ==  null){
			$_ramdomGenePref = rand(0, $totalPar);
		//}

		if($parFather == 0) {
			$parRandom = 0;
		}else{
			$parRandom = $_ramdomGenePref;
		}

		$totalParCode = $parFather + $parFatherOfMother + $parRandom;
		$perfCode = "{$perfKey}_base";
		if($totalParCode > $perfMax){
			$horse->perf->$perfCode = $perfMax;
		}else{
			$horse->perf->$perfCode = $totalParCode;
		}
	}

	/**
	 * BTR --> (moyenne des BTR de ses parents
	 * @param $horse
	 */
	public function getGenePerf(&$horse, $perfKey ,$fatherData, $fatherOfMotherData, $motherData)
	{
		$btrCode = "{$perfKey}_gene";
		if($fatherOfMotherData == null){
			$horse->perf->$btrCode = ($fatherData[$perfKey . '_gene'] + $fatherData[$perfKey . '_gene'] + $motherData[$perfKey . '_gene']) / 3;
		}else{
			$horse->perf->$btrCode = ($fatherData[$perfKey . '_gene'] + $fatherOfMotherData[$perfKey . '_gene'] + $motherData[$perfKey . '_gene']) / 3;
		}
	}

	/**
	 * BTR --> (moyenne des BTR de ses parents
	 * @param $horse
	 */
	public function getCurrentPerf(&$horse, $perfKey)
	{
		$perfBase = "{$perfKey}_base";
		$perfCurrent = "{$perfKey}_current";

		//CURRENT
		if($horse->perf->trot_base == 0){ //galopeur
			$vitesse = 3.33;
			$autres = 5;
		}else{ //trotteur
			$vitesse = 1;
			$autres = 5;
		}

		$multiplier = $horse->age - 1;
		if($perfKey == 'vitesse'){
			$additionnal = $multiplier * $vitesse;
		} else {
			$additionnal = $multiplier * $autres;
		}

		if($horse->age > 7){
			$percent = ($additionnal * 5) / 100;
			$additionnal -= ($multiplier*$percent);
		}

		if($horse->perf->$perfBase == 0){
			$horse->perf->$perfCurrent = 0;
		}else{
			$horse->perf->$perfCurrent = $horse->perf->$perfBase + $additionnal;
		}
	}

	public function performPerfBase(&$horse)
	{
		$vitesseArray = array(36, 37, 38, 39, 40, 41, 42, 43, 44, 45);

		if($horse->perf->vitesse_base < $vitesseArray[$horse->quality-1]){
			$add = $vitesseArray[$horse->quality-1] - $horse->perf->vitesse_base;
			$horse->perf->vitesse_base = $vitesseArray[$horse->quality-1];
			if($horse->perf->galop_base == 0){
				($horse->perf->trot_base > $horse->perf->endurance_base) ? $horse->perf->trot_base -= $add : $horse->perf->endurance_base -= $add;
			}else{
				($horse->perf->galop_base > $horse->perf->endurance_base) ? $horse->perf->trot_base -= $add : $horse->perf->endurance_base -= $add;
			}
		}
	}

	/***********************************************************************************************************/
	/*******************************************FIN GENERATION**************************************************/
	/***********************************************************************************************************/

	//GETTER

	public function isOwner($stable_id)
	{
		return ($this->_data['proprio_id'] == $stable_id);
	}

	public function getName($horse_name, $horse_id, $actif = 1)
	{
		$horse = $this->load($horse_id, false);
		$cssInactif = '';
		if($actif == 0){
			$cssInactif = 'inactif';
		}

		$type = '';
		if($horse['type'] == 2){
			$type = " <i class='fa fa-mars icon-etalon'></i>";
		}elseif($horse['type'] == 3){
			$type = " <i class='fa fa-venus icon-pouliniere'></i>";
		}

		if(is_null($horse_name) || $horse_name == '') {
			$html = 'Inconnu';
		}else{
			if ($horse_name == 'Inconnu') {
				$html = $horse_name;
			} else {
				$html = '<a href="javascript:void(0)" rel="' . $horse_id . '" class="horse-name ' . $cssInactif . '">' . $horse_name . '</a>';
			}
		}
		return $html . ' ' . $type;
	}

	/**
	 * Donnée du père de mère
	 * @return $this|bool|HorseModel|mixed|null
	 */
	public function getFatherOfMother()
	{
		$motherData = $this->load($this->_data['mother_id'], false);
		if($motherData['father_id']) {
			$fatherOfMotherData = $this->load($motherData['father_id'], false);
		}else{
			$fatherOfMotherData =  null;
		}
		return $fatherOfMotherData;
	}

	/**
	 * Spécialisation Trot ou Galop en fonction de specialization
	 * @return string
	 */
	public function getSpecialite()
	{
		if($this->_data['specialization'] == 'T'){
			return 'Trotteur';
		} else {
			return 'Galopeur';
		}
	}

	/**
	 * Indice global en 05 étoiles
	 * @return string
	 */
	public function getIndice($indice = null)
	{
		$html = '';
		if($indice == null){
			$qa = $this->_data['quality']/2;
		}else{
			$qa = $indice/2;
		}
		 for( $i=1; $i<=5; $i++){
		if( $qa>=1) {
				$html .= '<span class="fa fa-star horse-note"></span>';
			}elseif($qa == 0.5){
			$html .= '<span class="fa fa-star-half-o horse-note"></span>';
			}else{
				$html .= '<span class="fa fa-star-o horse-note"></span>';
			} $qa--;
		}
		return $html;
	}


	/**
	 * Indice de réproduction en 05 étoiles
	 * @return string
	 */
	public function getIndiceReproduction()
	{
		$html = '';
		$qa = $this->_data['quality_production'];
		if($this->_data['type'] == 0){
			$qa = 0;
		}
		for( $i=1; $i<=5; $i++){
		if( $qa>=1) {
			$html .= '<span class="fa fa-star horse-note"></span>';
		}else{
			$html .= '<span class="fa fa-star-o horse-note"></span>';
		} $qa--;
	}
		return $html;
	}

	/**
	 * Statistique des nombres de course, victoire et placé
	 * @return string
	 */
	public function getResultats()
	{
		$courue = 0;
		$victoire = 0;
		$place = 0;

		$query = "SELECT * FROM gain_race_horse WHERE horse_id = :horse_id";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $this->_data['id'], PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetch();
		if (isset($result['horse_id'])>0) {
			$courue = $result['carrer_race'];
			$victoire = $result['carrer_win'];
			$place = $result['carrer_placed'];
		}

		return "{$courue}C - {$victoire}V - {$place}P";
	}

	/**
	 * Les 05 derniers résulats en musique
	 * @param $sexe
	 * @param null $horseId
	 * @return string
	 */
	public function get5LastPerfs($sexe, $horseId = null)
	{
		$html = '';
		$query = "SELECT r.category_id, h.sexe, rp.status, rp.rang, rt.code FROM race_participant rp";
		$query .= " INNER JOIN races r ON r.id = rp.race_id";
		$query .= " INNER JOIN race_type rt ON rt.id = r.type_id";
		$query .= " INNER JOIN horses h ON h.id = rp.horse_id";
		$query .= " WHERE rp.horse_id = :horse_id AND r.status = 0 ORDER BY rp.id DESC LIMIT 5";
		$stmt = Database::prepare($query);
		if($horseId == null){
			$stmt->bindParam(':horse_id', $this->_data['id'], PDO::PARAM_INT);
		}else{
			$stmt->bindParam(':horse_id', $horseId, PDO::PARAM_INT);
		}

		$stmt->execute();
		$results = $stmt->fetchAll();
		if (count($results)>0) {
			foreach($results as $item) {
				if(in_array($item['category_id'], array(3,4))) {
					$html .= 'Q' . $item['code'];
				}elseif ($item['status'] == 0) {
					$html .= 'D' . $item['code'];
				} elseif ($item['rang'] > 9) {
					$html .= '0' . $item['code'];
				}else{
					$html .= $item['rang'] . $item['code'];
				}
			}
		}else{
			$html = 'Inedit';
			if($sexe == 'F')
				$html .= 'e';
		}
		return $html;
	}

	/**
	 * Nombres des courses faite par le cheval
	 * @return array
	 */
	public function getRacesDone()
	{
		$query = "SELECT rp.*,
						If(rp.status=0, 'D', IF(rp.rang>9, '0', rp.rang)) as place,
						r.name, r.race_date,
						IF(r.corde='G', 'Gauche', 'Droite') as corde,
						r.lenght, r.category_id,
						rc.title as category,
						rh.title as hippo,
						rt.code
					FROM race_participant rp
					INNER JOIN races r ON r.id = rp.race_id AND r.status = 0
					LEFT JOIN race_hippodrome rh ON rh.id = r.hippodrome_id
					LEFT JOIN race_type rt ON rt.id = r.type_id
					LEFT JOIN race_category rc ON rc.id = r.category_id
					WHERE rp.horse_id = :horse_id
					ORDER BY r.race_date DESC
					";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $this->_data['id'], PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getProgenitures()
	{
		$query = "SELECT
						h.id,h.name,h.age, h.sexe, h.quality, h.status,
						IF(h.father_id=0, 'Inconnu', h2.name) AS father, h.father_id, h2.status AS father_status,
						IF(h.mother_id=0, 'Inconnu', h3.name) AS mother, h.mother_id, h3.status AS mother_status,
						(IF(h3.father_id=0, 'Inconnu', h4.name) ) AS father_mother, h3.father_id as father_mother_id,  h4.status AS father_mother_status
					FROM horses h
					LEFT JOIN horses h2 ON h2.id = h.father_id
					LEFT JOIN horses h3 ON h3.id = h.mother_id
					LEFT JOIN horses h4 ON h4.id = h3.father_id
					WHERE h.father_id = :horse_id OR h.mother_id = :horse_id
					ORDER BY h.age DESC
					";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $this->_data['id'], PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function isEngagedInRace()
	{
		//Test dans race_participant table
		$query = "SELECT * FROM race_participant rp LEFT JOIN races r ON r.id = rp.race_id WHERE rp.horse_id = :horse_id AND r.status = 2";
		$stmt = Database::prepare($query);
		$stmt->bindParam(':horse_id', $this->_data['id'], PDO::PARAM_INT);
		$stmt->execute();
		$result = $stmt->fetch();
		if($result['id']==0){
			//Test dans race_participant_tmp table
			$query = "SELECT * FROM race_participant_tmp WHERE horse_id = :horse_id";
			$stmt = Database::prepare($query);
			$stmt->bindParam(':horse_id', $this->_data['id'], PDO::PARAM_INT);
			$stmt->execute();
			$result = $stmt->fetch();
			if($result['id'] == 0){
				return false;
			}
		}

		return $result;
	}
}