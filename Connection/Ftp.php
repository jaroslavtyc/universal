<?php
	class Ftp{
	
		protected $connection;
		protected $host;
		
		const MODE = FTP_BINARY;
	
		public function __construct($host, $user, $password, $port = 21, $timeout = 90){
			$this->host = $host;
			$this->host = $host;
			return $this->connect($user, $password, $port, $timeout);
		}
		
		private function connect($user, $password, $port, $timeout){
			if($this->connection = ftp_connect($this->host)){
				if(ftp_login($this->connection,$user,$password)){
					return true;
				}else{
					trigger_error("Přihlášení k $this->host se nezdařilo.",E_USER_NOTICE);
					return false;
				}
			}else{
				trigger_error("Připojení k $this->host se nezdařilo.",E_USER_NOTICE);
				return false;
			}
		}
		
		public function __destruct(){
			if(isset($this->connection) and $this->connection and (!ftp_close($this->connection))){
				trigger_error("Odpojení od $this->host nebylo bez chyb.",E_USER_NOTICE);
			}
		}
		
		public function cdUp(){
			if(ftp_cdup($this->connection)){
				return true;
			}else{
				trigger_error("Přechod u FTP $this->host do adresáře vyšší úrovně se nezdařil.",E_USER_NOTICE);
				return false;
			}
		}
		
		public function changeDir($directory){
			if(ftp_chdir($this->connection, $directory)){
				return true;
			}else{
				trigger_error("Přechod u FTP $this->host do adresáře $directory se nezdařil.",E_USER_NOTICE);
				return false;
			}
		}
		
		public function uploadFile($fileToUpload, $nameToSave, $mode = self::MODE){
			if(is_resource($fileToUpload)){//dostali jsme zdroj souboru, napriklad z fopen()
				$function = 'ftp_fput';
			}elseif(is_string($fileToUpload)){//soubor je udan cestou a naazvem
				$function = 'ftp_put';
			}else{//odkaz na soubor nebyl predan ve srozumitelnem formatu
				trigger_error("Neznámý datový typ (".gettype().") pro nahrání na FTP $this->host",E_USER_WARNING);
				return false;
			}
			if(call_user_func($function,$this->connection,$nameToSave,$fileToUpload,$mode)){
				return true;
			}else{
				trigger_error("Nezdařilo se nahrání souboru na FTP $this->host",E_USER_NOTICE);
				return true;
			}
		}
		
		public function isFileUpoaded($fileName, $directory = false, $strictSize = true,$strictDate = false){
			$uploadedFiles = $this->listOfFiles($directory);
			if(in_array(self::CIL."/$fileName",$uploadedFiles)){//soubor, ktery chceme nahrat, jiz na FTP je, proverime, zda je spravny
				$checkedFile = tmpfile();
				if(!ftp_fget($this->connection,$kontrolovanySoubor,self::CIL."/$fileName",self::MODE)){
					trigger_error("Na FTP serveru $this->host již soubor stejného jména je,nepodařilo se ho však stáhnout pro kontrolu",E_USER_WARNING);
					return false;
				}else{
					rewind($kontrolovanySoubor);
					$kontrolovanyObsah = '';
					$velikostKontrolovanehoSouboru = fstat($kontrolovanySoubor);
					$velikostKontrolovanehoSouboru = $velikostKontrolovanehoSouboru['size'];
					while($dalsiCast = fread($kontrolovanySoubor,$velikostKontrolovanehoSouboru)){//protoze fread nacte v jednom kroku maximalne 8192 bytu, nechame soubor nacitat do te doby, nez ho mame cely
						$kontrolovanyObsah .= $dalsiCast;
					}
					if($obsah == $kontrolovanyObsah){
						echo("Tento soubor $fileName je jiz nahran");
					}else{
						$this->nahraj($fileName,$obsah,false);
					}
				}
			}
		}

		public function listOfFiles($directory = false){
			if(!$directory){
				$directory = ftp_pwd ($this->connection);
			}
			return ftp_nlist($this->connection,$directory);
		}
	}
?>