<?php
namespace universal;

require_once(dirname(__FILE__).'/ExceptionTracking.php');

abstract class ExceptionCatching {

	const ERROR_LEVEL = E_ALL;
	const ERROR_DESCRIPTION_REPLACEMENT = 'not closer specified error';

	private $exceptionName;
	private $messages  = array();//uloziste pro messages o vyjimkach - muze jich byt vice v pripade, ze nevypisujeme kazdou okamzite, ale az vice najednou
	private $exceptionsTracking;
	protected $end;

	public function __construct($exceptionName, $tracking = ExceptionTracking::TRACK_ALL_FILES_EXCEPT_FROM_UNIVERSAL, $end = true){//v uvodu nastavujeme zakladni parametry nazev/typ vyjimky, napr. Varovani, Poznamka a pod., dale intenzitu tracking vyjimky (vice ve tride ExceptionTracking) a nakonec zda pri vyskytu vyjimky ma byt ukoncen beh programu
		$this->exceptionName = $exceptionName;
		$this->exceptionsTracking = new ExceptionTracking($tracking);
		$this->end = $end;
	}

	public function __destruct(){
		$this->reportExceptions(false);//pokud jsme behem existence ChytaniVyjimek nachytali vyjimky, ktere jsme ale jeste nevypsali, vypiseme je nyni, pri likvidaci tridy, ovsem bez ukonceni programu (mensi zlo nez za kazdou cenu se drzet bezneho nastaveni vlastnosti end)
	}

	public function catchOnce($errorMessage, $scriptnameWithError = null, $lineWithError = null, $end = null){
		$this->catching($errorMessage, $scriptnameWithError = null, $lineWithError = null);//ulozime chybu do sezamu chyb
		$this->reportExceptions($end);//zobrazime chycenou vyjimku
	}

	public function catching($errorMessage, $scriptnameWithError = false, $lineWithError = false){//u nedokonave chyby se neda nastavit end - pokud je chyba natolik zavazna, ze vyzaduje ukonceni programu, neni rozumne nechat bezet program dal a pozadovat jeho ukonceni az na konci sberu chyb
		$message = array();//uloziste pro strukturu a content messages\
		$errorMessage = $this->getExceptionReport($errorMessage);//ziskame provereny content messages
		$message['content'] = $errorMessage;
		if(!($message['tracking'] = $this->exceptionsTracking->trackout())){//jestlize tracking chyby bylo z nejakeho duvodu neuspesne
			$message['tracking'] = ExceptionTracking::getFootprint($scriptnameWithError,$lineWithError);//pouzijeme udaje ziskane z trigger_error a predane do teto tridy
		}
		$this->messages[] = $message;
	}

	private function reportExceptions($end = NULL){//zobrazi vsechny vyjimky, ktere nasbiral a pripadne ukonci beh programu
		if(sizeof($this->messages) > 0){//jestli mame co rict
			$end = $this->getCheckedEnd($end);
			$description = '';
			$start = TRUE;
			foreach($this->messages as $messageTier=>$message){//krom prave chycene chyby zpracujeme i pripadne predesle chyby, ktere z nejakeho duvodu vypsany jeste nebyly
				$description .= $message['content'];
				$description .= $message['tracking'];
				unset($this->messages[$messageTier]);//zrusime jiz pouzitou zpravu, aby nas nepletla pri volani reportExceptions v destructoru
				$start = FALSE;
			}
			echo $description;
			//throw new Exception($description, self::ERROR_LEVEL);
			if($end){
				exit;
			}
		}
	}

	private function getCheckedEnd($end){
		if($end !== null){//pokud uzivatel zadal jinou podminku
			return $end;//vratime ji
		}else{
			return $this->end;//jinak vratime prednastavenou hodnotu
		}
	}

	private function getExceptionReport($errorMessage){
		if(!is_string($errorMessage) or ($errorMessage == '')){//neni-li vyjimka popsana
			$errorMessage = SELF::ERROR_DESCRIPTION_REPLACEMENT;
		}
		return "<br>\n$this->exceptionName: $errorMessage";
	}

}