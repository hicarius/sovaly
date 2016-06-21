<?php
class SimulationModel extends Model_Abstract
{
    protected $_race;

    public function setRace($oRace)
    {
        $this->_race = $oRace;
        return $this;
    }

    public function run()
    {
        function sortResult($a, $b)
        {
            if ($a['race_time'] == $b['race_time']) {
                return 0;
            }
            return ($a['race_time'] < $b['race_time']) ? -1 : 1;
        }

        $horses = Apps::getModel('Race')->getHorsesEngaged($this->_race['id']);

        $result = array();
        foreach($horses as $horse) {
            $result[] = $this->simulateHorse($horse['horse_id']);
        }

        usort($result, 'sortResult');

        return $result;
    }


    public function convertSecondeEnMinute($secondes)
    {

        $min = floor($secondes / 60);
        $sec = str_pad(number_format( ((($secondes / 60) - $min) * 60), 2), 5, '0', STR_PAD_LEFT);
        return "$min' $sec";
    }

    public function getReductionKilometric($seconds, $lenght)
    {
        return $seconds * 1000 / $lenght;
    }

    public function getReductionFatigue($endurance)
    {
        return abs(($endurance*4/50) - 6);
    }

    public function raVitesseByAptitude($aptitude)
    {
        $x = 1 - ($aptitude * 0.04);
        if($aptitude < 25){
            $x =  -$x;
        }elseif($aptitude > 25){
            $x =  -1 * $x;
        }else{
            $x =  0;
        }

        return $x;
    }


    public function simulateHorse($horseId)
    {
        $isDisqualified = false;
        $oHorse = Apps::getModel('Horse')->load($horseId);
        $code = $this->_race['code'];
        $lenght = 2000;//$this->_race->getData('lenght');

        $fatigue = $oHorse->getData('fatigue');

        //vitesse moyenne en km/h
        $vitesse = $oHorse->getData('vitesse_current');

        /**
         * Influence sur fatigue et vitesse
         */
        $endurance = $oHorse->getData('endurance_current');
        $reductionFatigue = $this->getReductionFatigue($endurance);

        /*
         * Physique, si 100 aucune effet.
         * Si physique < 100
         */
        $physique = $oHorse->getData('physique');
        if( $physique <= 100 ){
            $reductionPhysique = (100-$physique) / 100;
            $vitesse -= $reductionPhysique;
        }

        /*
         * aptitude à galoper ou trotter
         * Aptitude, influance sur vitesse
         */
        if( in_array($code, array('a', 'm')) ){
            $aptitude = $oHorse->getData('trot_current');
        }else{
            $aptitude = $oHorse->getData('galop_current');
        }
        $vitesse += $this->raVitesseByAptitude($aptitude);


        //si trot monté, le vitesse finale est -1
        if($code == 'm'){
            $vitesse -=0.3;
        }

        //Préférence corde
        if($oHorse->getData('corde') != $this->_race['corde'] ){
            $vitesse -=0.3;
        }

        //vitesse en 100m / seconde
        $vitesse = (60*100) / (($vitesse*1000)/60);

        //on découpe le distance en tranche de 100m
        $vitesseArray = array();
        $resultat_string = '';
        for($distance = 100; $distance <= $lenght; $distance+=100){

            $fatigue += $reductionFatigue;
            if($fatigue > 130 ){
                $vitesseArray[] = 0;
                $isDisqualified = true;
                $resultat_string .= "|$distance,0";
                break;
            }

            if($fatigue >= 100){
                $vitesseArray[] = $vitesse + 2;
                $time = array_sum($vitesseArray);
                $resultat_string .= "|$distance,$time";
            }else{
                $vitesseArray[] = $vitesse;
                $time = array_sum($vitesseArray);
                $resultat_string .= "|$distance,$time";
            }
        }

        $timeTotal = array_sum($vitesseArray); //en seconds
        $timeTotalMinute = $this->convertSecondeEnMinute($timeTotal);

        //Reduction kilometrique
        $rk = $this->convertSecondeEnMinute($this->getReductionKilometric($timeTotal, $lenght));

        $timeTotal = ($isDisqualified) ? 0 : $timeTotal;

        return array(
            'horse_id' => $horseId,
            'race_time' => $timeTotal,
            'rk' => $rk,
            'chrono' => $timeTotalMinute,
            'resultat_string' => $resultat_string,
            'physique' => $physique-20,
            'fatigue' => $fatigue
        );
    }
}