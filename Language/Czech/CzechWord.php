<?php
namespace universal;
class CzechWord extends Word {

	const CASE_NOMINATIVE = 'nominative';		const CASE_FIRST = self::CASE_NOMINATIVE;			const CASE_1 = self::CASE_NOMINATIVE;		
	const CASE_GENITIVE = 'genitive';			const CASE_SECOND = self::CASE_GENITIVE;			const CASE_2 = self::CASE_GENITIVE;
	const CASE_DATIVE = 'dative';					const CASE_THIRD = self::CASE_DATIVE;				const CASE_3 = self::CASE_DATIVE;
	const CASE_ACCUSATIVE = 'accusative';		const CASE_FOURTH = self::CASE_ACCUSATIVE;		const CASE_4 = self::CASE_ACCUSATIVE;
	const CASE_VOCATIVE = 'vocative';			const CASE_FIFTH = self::CASE_VOCATIVE;			const CASE_5 = self::CASE_VOCATIVE;
	const CASE_LOCAL = 'local';					const CASE_SIXTH = self::CASE_LOCAL;				const CASE_6 = self::CASE_LOCAL;
	const CASE_INSTRUMENTAL = 'instrumental';	const CASE_SEVENTH = self::CASE_INSTRUMENTAL;	const CASE_7 = self::CASE_INSTRUMENTAL;
	
	public function __construct($content)
	{
		parent::__construct($content, new CzechLanguage);
	}

}