<?php
class RaceModel extends Model_Abstract
{
    public function getRaces($date=null)
    {
        $additionalColumns = "rc.title AS category_name, rg.group_name, rp.title AS piste_name, rh.title AS hippodrome_name";
        $additionalColumns .= ", rt.title AS type_name, rt.code";
        $joins =  " INNER JOIN race_category rc ON rc.id = r.category_id";
        $joins .=  " INNER JOIN race_group rg ON rg.id = r.group_id";
        $joins .=  " INNER JOIN race_hippodrome rh ON rh.id = r.hippodrome_id";
        $joins .=  " INNER JOIN race_type rt ON rt.id = r.type_id";
        $joins .=  " INNER JOIN race_piste rp ON rp.id = r.piste_id";

        $where = '';
        if($date != null){
            $where = " WHERE r.race_date LIKE '$date%'";
        }

        $query = "SELECT r.*, $additionalColumns FROM races r  $joins $where GROUP BY r.id ORDER BY r.meeting, r.race_number ASC";

        $stmt = Database::prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function load($id, $setData = true)
    {
        $additionalColumns = "rc.title AS category_name, rg.group_name, rp.title AS piste_name, rh.title AS hippodrome_name";
        $additionalColumns .= ", rt.title AS type_name, rp.title AS piste_name, rt.code";
        $joins =  " INNER JOIN race_category rc ON rc.id = r.category_id";
        $joins .=  " INNER JOIN race_group rg ON rg.id = r.group_id";
        $joins .=  " INNER JOIN race_hippodrome rh ON rh.id = r.hippodrome_id";
        $joins .=  " INNER JOIN race_type rt ON rt.id = r.type_id";
        $joins .=  " INNER JOIN race_piste rp ON rp.id = r.piste_id";
        $query = "SELECT r.*, $additionalColumns FROM races r  $joins WHERE r.id = :id GROUP BY r.id";
        $stmt = Database::prepare($query);
        $stmt->bindParam(':id', $id);
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

    public function create($data)
    {
        try{

            $query = "INSERT INTO races (name, category_id, group_id, type_id, piste_id, hippodrome_id, meeting, race_number, lenght, corde, race_date, price, recul_gain, recul_meter, max_gain, age_min, age_max, victory_price, status, created_at )
 				  VALUES(:name, :category_id, :group_id, :type_id, :piste_id, :hippodrome_id, :meeting, :race_number, :lenght, :corde, :race_date, :price, :recul_gain, :recul_meter, :max_gain, :age_min, :age_max, sexe = :sexe, :victory_price, :status, :created_at)";
            $stmt = Database::prepare($query);

            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':group_id', $data['group_id']);
            $stmt->bindParam(':type_id', $data['type_id']);
            $stmt->bindParam(':piste_id', $data['piste_id']);
            $stmt->bindParam(':hippodrome_id', $data['hippodrome_id']);
            $stmt->bindParam(':meeting', $data['meeting']);
            $stmt->bindParam(':race_number', $data['race_number']);
            $stmt->bindParam(':lenght', $data['lenght']);
            $stmt->bindParam(':corde', $data['corde']);
            $stmt->bindParam(':race_date', $data['race_date']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':recul_gain', $data['recul_gain']);
            $stmt->bindParam(':recul_meter', $data['recul_meter']);
            $stmt->bindParam(':max_gain', $data['max_gain']);
            $stmt->bindParam(':age_min', $data['age_min']);
            $stmt->bindParam(':age_max', $data['age_max']);
            $stmt->bindParam(':sexe', $data['sexe']);
            $stmt->bindParam(':victory_price', $data['victory_price']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':created_at', date('Y-m-d H:i:s'));

            $stmt->execute();

            return Database::lastInsertId('races');
        }catch (Exception $e){
            $this->addMessage($e->getMessage(), 'danger');
            return FALSE;
        }
    }


    public function update($data)
    {
        try{

            $query = "UPDATE races
                      SET name = :name, category_id = :category_id, group_id = :group_id, type_id = :type_id, piste_id = :piste_id,
                                        hippodrome_id = :hippodrome_id, meeting = :meeting, race_number = :race_number,
                                        lenght = :lenght, corde = :corde, race_date = :race_date, sexe = :sexe,
                                        price = :price, recul_gain = :recul_gain, recul_meter = :recul_meter, max_gain = :max_gain,
                                        age_min = :age_min, age_max = :age_max, victory_price = :victory_price, status = :status
                     WHERE id = :id";
            $stmt = Database::prepare($query);

            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':group_id', $data['group_id']);
            $stmt->bindParam(':type_id', $data['type_id']);
            $stmt->bindParam(':piste_id', $data['piste_id']);
            $stmt->bindParam(':hippodrome_id', $data['hippodrome_id']);
            $stmt->bindParam(':meeting', $data['meeting']);
            $stmt->bindParam(':race_number', $data['race_number']);
            $stmt->bindParam(':lenght', $data['lenght']);
            $stmt->bindParam(':corde', $data['corde']);
            $stmt->bindParam(':race_date', $data['race_date']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':recul_gain', $data['recul_gain']);
            $stmt->bindParam(':recul_meter', $data['recul_meter']);
            $stmt->bindParam(':max_gain', $data['max_gain']);
            $stmt->bindParam(':age_min', $data['age_min']);
            $stmt->bindParam(':sexe', $data['sexe']);
            $stmt->bindParam(':age_max', $data['age_max']);
            $stmt->bindParam(':victory_price', $data['victory_price']);
            $stmt->bindParam(':status', $data['status']);

            $stmt->execute();

            return $data['id'];
        }catch (Exception $e){
            $this->addMessage($e->getMessage(), 'danger');
            return FALSE;
        }
    }

    public function generate()
    {
        // R = [1, 2, 3] choisir 03 Hippodromes(au hasard)
        // C = [1, 2, 3, 4, 5, 6] créer 3 à 6 course pour chaque R
        // ET retourne CODE pour choisir TYPE(au hasard)
        // ET choisir PISTE(au hasard) en fonction de TYPE
        //

        //Choisir un hippodrome au hasard
        $hippodromes = Apps::getModel('Race_Hippodrome')->getHippodromes();
        $selectedHippodromeKeys = array_rand($hippodromes, 3);

        $date = date('Y-m-d', strtotime());
        foreach($selectedHippodromeKeys as $key => $selectedHippodromeKey) {
            $reunion = $key + 1;
            $selectedHippodrome = $hippodromes[$selectedHippodromeKey];

            //Choisir au hasard le nombre de course par réunion
            $maxCourse = mt_rand(3,6);
            for($course = 1; $course <= $maxCourse; $course++){
                //new race
                $race = new stdClass();
                $race->created_at = date('Y-m-d H:i:s');
                $race->meeting = $reunion;
                $race->race_number = $course;
                $race->hippodrome_id = $selectedHippodrome['id'];

                //On chosit ah hasard le CODE de l'hippodrome pour chosir le TYPE
                $hippodromeCodes = explode(',', $selectedHippodrome['code']);
                $hippodromeCodeKey = array_rand($hippodromeCodes);
                $type = Apps::getModel('Race_Type')->getTypeByCode($hippodromeCodes[$hippodromeCodeKey]);
                $race->type_id = $type['id'];

                //On choisit PISTE en fonction de TYPE
                $pistes = Apps::getModel('Race_Piste')->getPisteByCode($hippodromeCodes[$hippodromeCodeKey]);
                $selectedPisteKey = array_rand($pistes);
                $race->piste_id = $pistes[$selectedPisteKey]['id'];

                //On choisit CORDE
                $cordes = str_shuffle('DG');
                $race->corde = $cordes[0];

                Debugger::dump($race);
            }
        }

        $category = Apps::getModel('Race_Category')->getCategories();
        $selectedCategory = array_rand($category);
        $selectedCategory0 = shuffle($category);


        die;
    }

    /////////////////////////////////////////////////////////////////////
    ///////////////////GETTER/////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////
    /**
     * @param null $raceId
     * @return array
     */
    public function getHorsesEngaged($raceId = null)
    {
        $additionalColumns = ", h.name, h.age, h.sexe, h.id as horse_id";
        $additionalColumns .= ", CONCAT_WS(' ', s2.firstname, s2.lastname) AS entraineur  ";
        $additionalColumns .= ", CONCAT_WS(' ', s3.firstname, s3.lastname) AS jockey ";
        $joins =  " INNER JOIN horses h ON h.id = rp.horse_id";
        $joins .=  " INNER JOIN stables s2 ON s2.id = h.trainer_id";
        $joins .=  " LEFT JOIN stables s3 ON s3.id = rp.jockey_id";

        $query = "SELECT rp.* $additionalColumns FROM race_participant rp $joins";
        $query .= " WHERE rp.race_id = :race_id";
        $stmt = Database::prepare($query);

        if($raceId==null){
            $stmt->bindParam(':race_id', $this->_data['id']);
        }else{
            $stmt->bindParam(':race_id',  $raceId);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }


    public function getMeetingsAndRaceNumber($races)
    {
        $meetings = array();
        foreach($races as $race){
            if(!isset($meetings[ $race['meeting'] ])) {
                $meetings[$race['meeting']] = array('name' => $race['hippodrome_name']);
            }
            $meetings[$race['meeting']]['course'][$race['race_number']] = $race['race_number'];
        }

        return $meetings;
    }

    public function getAgeMinMax($race)
    {
        $html = '';
        if( $race['age_min'] == $race['age_max']){
            $html .= $race['age_min'] . " ans";
        }else{
            if($race['age_max'] == 0){
                $html .= $race['age_min'] . " ans et plus";
            } else {
                $html .= $race['age_min'] . " ans à " .$race['age_max'] . " ans";
            }
        }
        return $html;
    }

    public function getGainsMax($race)
    {
        $html = '';
        if( in_array($race['category_id'], array(3,4))){
            return $html;
        }
        if( $race['max_gain'] != -1 ){
            $html .= " qui ont gagnés <b>".number_format($race['max_gain'], 0, '.', ' ')." ".Text::__('turfoos')." max</b>.";
        }
        return $html;
    }

    public function getRacePrize($race)
    {
        $html = '';
        if($race['victory_price']){
            $data = explode('|', $race['victory_price']);
            $html .= "<b>Gain :</b> ".number_format($data[0], 0, '.', ' ')." (".number_format($data[1], 0, '.', ' ').", ".number_format($data[2], 0, '.', ' ').",
            ".number_format($data[3], 0, '.', ' ').", ".number_format($data[4], 0, '.', ' ').", ".number_format($data[5], 0, '.', ' ').")";
        }
        return $html;
    }

    public function getRacePrizeAjax($race)
    {
        $html = '';
        if($race['victory_price']){
            $data = explode('|', $race['victory_price']);
            $html .= number_format($data[0], 0, '.', ' ');
        }
        return $html;
    }

    public function getCorde($race)
    {
        if($race['corde'] == 'D'){
            return 'Droite';
        }else{
            return 'Gauche';
        }
    }

    public function getName($race_name, $race_id, $isTemp = false)
    {
        if($isTemp){
            return '<a href="javascript:void(0)" rel="' . $race_id . '|1" class="race-name">' . $race_name . '</a>';
        }else{
            return '<a href="javascript:void(0)" rel="' . $race_id . '" class="race-name">' . $race_name . '</a>';
        }

    }

    public function getSexe($race)
    {
        if($race['sexe'] == 'F') {
            return 'Femelle';
        }elseif($race['sexe'] == 'F'){
            return 'Mâle';
        }else{
            return 'Tous';
        }
    }
}