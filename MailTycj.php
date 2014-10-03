<?php
	class MailTycj{
	
		private $connection;
		private $host;
		private $port;
		private $user;
		private $pass;
		private $folder;
		private $ssl;
	
		public function __construct($host,$port,$user,$pass,$folder="INBOX",$ssl=false){
			$this->host = $host;
			$this->port = $port;
			$this->user = $user;
			$this->pass = $pass;
			$this->folder = $folder;
			$this->ssl = ($ssl) ? '' : 'novalidate-cert';
			$this->connection = $this->login();
		}
		
		public function __destruct(){
			if(!imap_close($this->connection)){
				trigger_error("Ukončení s mailovou schránkou se nepodařilo bezchybně.",E_USER_NOTICE);
			}
		}
		
		public static function send($komu, $predmet, $obsah, $dalsiHeadery = null/*(From, Cc, Bcc*/, $dalsiParametry = null){
			if(ini_get('sendmail_from') == ''){
				if(strpos($dalsiHeadery,'From:') === false){
					trigger_error("Není uvedeno, od koho mail pochází (From). Mail nelze odeslat.",E_USER_NOTICE);
					return false;
				}
			}
			preg_match_all("#From: .+(\r\n|$)#",$dalsiHeadery,$from);
			if(!isset($from[0][0]) or empty($from[0][0])){
				trigger_error("V hlavice mailu je chybne uvedeno, od koho mail pochází. Mail nelze odeslat.",E_USER_NOTICE);
				return false;
			}
			if(!empty($_SERVER['WINDIR'])){//Pokud PHP komunikuje se SMTP serverem naprimo, tak pripadne tecky primo na zacatku vet jsou odstraney. Musime je proto zdvojnasobit.
				$obsah = str_replace("\n.","\n..",$obsah);
			}
			foreach((array)$komu as $adresat){
				if(!mail($adresat, $predmet, $obsah, $dalsiHeadery, $dalsiParametry)){
					trigger_error("Mail pro $adresat obsahuje chybu, nelze ho odeslat",E_USER_NOTICE);
				}//return true, pokud mail dostal zelenou k odeslani, false, pokud obsahuje chybu
			}
		}
		
		function orderSortedBy($by,$descending,$returnUid = false){
			if($returnUid){
				$returnUid = SE_UID;
			}else{
				$returnUid = 0;
			}
			return imap_sort($this->connection,$by,(int)$descending,$returnUid);
		}
		
		function orderSortedByArrival($descending = true,$returnUid = false){
			return $this->orderSortedBy(SORTARRIVAL,$descending,$returnUid);
		}

		private function login(){
			return imap_open("{"."$this->host:$this->port/pop3/$this->ssl"."}$this->folder",$this->user,$this->pass);
		}

		function pop3_stat(){
			$check = imap_mailboxmsginfo($this->connection);
			return (array)$check;
		}
		
		public function headerInfo($numberOfMessages = false){
			if($numberOfMessages === false){
				$numberOfMessages = $this->numberOfMessages();
			}
			return imap_headerinfo($this->connection,$numberOfMessages,0,0);
		}
		
		function stat($option = SA_ALL){
			$stat = imap_status($this->connection,"{"."$this->host:$this->port/pop3/$this->ssl"."}INBOX",$option);
			return $stat;
		}
		
		public function numberOfMessages(){
			$number = $this->stat(SA_MESSAGES);
			return $number->messages;
		}
		
		public function numberOfNewMessages(){
			$number = $this->stat(SA_RECENT);
			return $number->recent;
		}
		
		public function numberOfUnseenMessages(){
			$number = $this->stat(SA_UNSEEN);
			return $number->unseen;
		}
		
		public function nextUid(){
			$uid = $this->stat(SA_UIDNEXT);
			return $uid->uidnext;
		}
		
		public function uidValidity(){
			$uidValidity = $this->stat(SA_UIDVALIDITY);
			return $uidValidity->uidvalidity;
		}

		function pop3_list($message = false){
			if($message){
				$range=$message;
			}else{
				$range = "1:".$this->numberOfMessages();
			}
			$response = imap_fetch_overview($this->connection,$range);
			$result = array();
			foreach($response as $msg){
				$result[$msg->msgno] = (array)$msg;
			}
			return $result;
		}
		
		function getByUid($uid){
			return imap_fetchheader($this->connection,$uid,FT_UID);
		}

		function pop3_retr($connection,$message){
			return(imap_fetchheader($connection,$message,FT_PREFETCHTEXT));
		}

		function pop3_dele($connection,$message){
			return(imap_delete($connection,$message));
		}
		
		function getStructure($numberOfMessage,$itsUid = false){
			if($itsUid){
				$itsUid = FT_UID;
			}else{
				$itsUid = 0;
			}
			return imap_fetchstructure($this->connection,$numberOfMessage,$itsUid);
		}

		function mail_parse_headers($headers){
			$headers=preg_replace('/\r\n\s+/m', '',$headers);
			preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers, $matches);
			foreach ($matches[1] as $key =>$value) $result[$value]=$matches[2][$key];
			return($result);
		}

		function mail_mime_to_array($numberOfMessage,$itsUid = false,$parse_headers=false){
			$mail = imap_fetchstructure($this->connection,$numberOfMessage,$itsUid);
			$mail = $this->mail_get_parts($numberOfMessage,$itsUid,$mail,0);
			if ($parse_headers) $mail[0]["parsed"]=mail_parse_headers($mail[0]["data"]);
			return($mail);
		}

		function mail_get_parts($numberOfMessage,$itsUid,$part,$prefix){
			$attachments=array();
			$attachments[$prefix]=$this->mail_decode_part($numberOfMessage,$itsUid,$part,$prefix);
			if (isset($part->parts)){ // multipart
				$prefix = ($prefix == "0")?"":"$prefix.";
				foreach ($part->parts as $number=>$subpart)
					$attachments=array_merge($attachments, $this->mail_get_parts($numberOfMessage,$itsUid,$subpart,$prefix.($number+1)));
			}
			return $attachments;
		}

		function mail_decode_part($numberOfMessage,$itsUid,$part,$prefix){
			$attachment = array();
			if($part->ifdparameters) {
				foreach($part->dparameters as $object) {
					$attachment[strtolower($object->attribute)]=$object->value;
					if(strtolower($object->attribute) == 'FILENAME') {
						$attachment['is_attachment'] = true;
						$attachment['filename'] = $object->value;
					}
				}
			}

			if($part->ifparameters) {
				foreach($part->parameters as $object) {
					$attachment[strtolower($object->attribute)]=$object->value;
					if(strtolower($object->attribute) == 'name') {
						$attachment['is_attachment'] = true;
						$attachment['name'] = $object->value;
					}
				}
			}

			$attachment['data'] = imap_fetchbody($this->connection, $numberOfMessage, $prefix, $itsUid);
			if($part->encoding == 3) { // 3 = BASE64
				$attachment['data'] = base64_decode($attachment['data']);
			}
			elseif($part->encoding == 4) { // 4 = QUOTED-PRINTABLE
				$attachment['data'] = quoted_printable_decode($attachment['data']);
			}
			return($attachment);
		}
	}
?>