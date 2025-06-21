<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';
class virtualMeter extends eqLogic {

	private static function getMeterValue(virtualMeterCmd $cmd) {
		$meter = cmd::byId(trim($cmd->getConfiguration('meter'), '#'));
		if (!is_object($meter)) {
			log::add(__CLASS__, 'warning', "Meter not found for command {$cmd->getHumanName()}");
			return null;
		}
		$meterValue = $meter->execCmd();
		if ($meterValue === null) {
			log::add(__CLASS__, 'warning', "No value for meter {$meter->getHumanName()} on {$cmd->getName()}");
			return null;
		}
		return floatval($meterValue);
	}

	public static function cron() {
		/** @var virtualMeter */
		foreach (self::byType(__CLASS__, true) as $eqLogic) {
			foreach ($eqLogic->getCmd('info') as $cmd) {
				if (is_null($meterValue = self::getMeterValue($cmd))) {
					continue;
				}

				$cmdIndex = $cmd->getCache('index', 0);
				if ($cmdIndex == 0) {
					$cmd->setCache('index', $meterValue);
					$cmdIndex = $meterValue;
				}
				$eqLogic->checkAndUpdateCmd($cmd, round($meterValue - $cmdIndex, 3));
			}
		}
	}

	public static function cronDaily() {
		/** @var virtualMeter */
		foreach (self::byType(__CLASS__, true) as $eqLogic) {
			foreach ($eqLogic->getCmd('info') as $cmd) {
				if (is_null($meterValue = self::getMeterValue($cmd))) {
					continue;
				}

				$date = new DateTime();
				$isFirstDay = $date->format('d') === '01';

				if ($cmd->getConfiguration('type', 'daily') === 'daily' || $isFirstDay) {
					$cmd->setCache('index', $meterValue);
					$eqLogic->checkAndUpdateCmd($cmd, 0);
				}
			}
		}
	}
}

class virtualMeterCmd extends cmd {

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
		/** @var virtualMeter */
		$eqLogic = $this->getEqLogic();
		log::add('virtualMeter', 'debug', "command: {$this->getLogicalId()} on {$eqLogic->getLogicalId()} : {$eqLogic->getName()}");
		switch ($this->getLogicalId()) {
		}
	}
}
