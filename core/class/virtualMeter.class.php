<?php

require_once __DIR__ . '/../../../../core/php/core.inc.php';
class virtualMeter extends eqLogic {

	public static function cron() {
		foreach (self::byType(__CLASS__, true) as $eqLogic) {
			/** @var virtualMeterCmd */
			foreach ($eqLogic->getCmd('info') as $cmd) {
				if ($cmd->getConfiguration('type', 'manual') === 'manual') {
					// Skip manual commands, they are updated on demand
					continue;
				}
				if (is_null($meterValue = $cmd->getMeterValue())) {
					continue;
				}

				$cmd->updateConso($meterValue);
			}
		}
	}

	public static function cronDaily() {
		foreach (self::byType(__CLASS__, true) as $eqLogic) {
			/** @var virtualMeterCmd */
			foreach ($eqLogic->getCmd('info') as $cmd) {
				$meterType = $cmd->getConfiguration('type', 'manual');
				if ($meterType === 'manual') {
					// Skip manual commands, they are updated on demand
					continue;
				}

				$date = new DateTime();
				$isFirstDay = $date->format('d') === '01';

				if ($meterType === 'daily' || ($isFirstDay && $meterType === 'monthly')) {
					$cmd->setCurrentIndexInCache();
					$eqLogic->checkAndUpdateCmd($cmd, 0);
				}
			}
		}
	}
}

class virtualMeterCmd extends cmd {

	public function getMeterValue() {
		$meter = cmd::byId(trim($this->getConfiguration('meter'), '#'));
		if (!is_object($meter)) {
			log::add('virtualMeter', 'warning', "Meter not found for command {$this->getHumanName()}");
			return null;
		}
		$meterValue = $meter->execCmd();
		if ($meterValue === null) {
			log::add('virtualMeter', 'warning', "No value for meter {$meter->getHumanName()} on {$this->getName()}");
			return null;
		}
		log::add('virtualMeter', 'debug', "Meter value for command {$this->getHumanName()} is {$meterValue}");
		return floatval($meterValue);
	}

	public function setCurrentIndexInCache() {
		if ($this->getType() !== 'info') {
			return;
		}
		if (is_null($meterValue = self::getMeterValue($this))) {
			return;
		}
		$this->setCache('index', $meterValue);
	}

	private function removeActionCmd() {
		$eqLogic = $this->getEqLogic();
		if (!is_object($eqLogic)) {
			return;
		}
		$cmdStart = $eqLogic->getCmd('action', "start_{$this->getId()}");
		if (is_object($cmdStart)) {
			$cmdStart->remove();
		}
		$cmdStop = $eqLogic->getCmd('action', "stop_{$this->getId()}");
		if (is_object($cmdStop)) {
			$cmdStop->remove();
		}
	}

	public function dontRemoveCmd() {
		return $this->getType() === 'action';
	}

	public function preSave() {
		if ($this->getType() !== 'info') {
			return;
		}
		if (empty($this->getConfiguration('meter'))) {
			throw new Exception(__('La commande de mesure est obligatoire', __CLASS__));
		}
		if (empty($this->getConfiguration('type'))) {
			$this->setConfiguration('type', 'manual');
		}
	}

	public function postSave() {
		if ($this->getType() !== 'info') {
			return;
		}
		if ($this->getConfiguration('type') === 'manual') {
			$eqLogic = $this->getEqLogic();
			$cmdStart = $eqLogic->getCmd('action', "start_{$this->getId()}");
			if (!is_object($cmdStart)) {
				$cmdStart = new virtualMeterCmd();
				$cmdStart->setEqLogic_id($eqLogic->getId());
				$cmdStart->setType('action');
				$cmdStart->setSubType('other');
				$cmdStart->setLogicalId("start_{$this->getId()}");
				$cmdStart->setName(__('Démarrer', __CLASS__) . ' ' . $this->getName());
				$cmdStart->setConfiguration('meter', $this->getConfiguration('meter'));
				$cmdStart->save();
			}
			$cmdStop = $eqLogic->getCmd('action', "stop_{$this->getId()}");
			if (!is_object($cmdStop)) {
				$cmdStop = new virtualMeterCmd();
				$cmdStop->setEqLogic_id($eqLogic->getId());
				$cmdStop->setType('action');
				$cmdStop->setSubType('other');
				$cmdStop->setLogicalId("stop_{$this->getId()}");
				$cmdStop->setName(__('Arrêter', __CLASS__) . ' ' . $this->getName());
				$cmdStop->setConfiguration('meter', $this->getConfiguration('meter'));
				$cmdStop->save();
			}
		} else {
			$this->removeActionCmd();
		}
	}

	public function preRemove() {
		if ($this->getType() !== 'info') {
			return;
		}
		$this->removeActionCmd();
	}

	public function updateConso(int $meterValue) {
		$eqLogic = $this->getEqLogic();

		$cmdIndex = $this->getCache('index', 0);
		if ($cmdIndex == 0) {
			$this->setCache('index', $meterValue);
			$cmdIndex = $meterValue;
		}
		log::add('virtualMeter', 'debug', "Updating consumption for command {$this->getHumanName()} on {$eqLogic->getName()} with meter value {$meterValue} and index {$cmdIndex}");
		$eqLogic->checkAndUpdateCmd($this, round($meterValue - $cmdIndex, 3));
	}

	public function execute($_options = array()) {
		$eqLogic = $this->getEqLogic();
		log::add('virtualMeter', 'debug', "command: {$this->getLogicalId()} on {$eqLogic->getName()}");

		if (substr($this->getLogicalId(), 0, 6) === 'start_') {
			/** @var virtualMeterCmd */
			$infoCmd = virtualMeterCmd::byId(substr($this->getLogicalId(), 6));
			$infoCmd->setCurrentIndexInCache();
			$eqLogic->checkAndUpdateCmd($infoCmd, 0);
		} elseif (substr($this->getLogicalId(), 0, 5) === 'stop_') {
			/** @var virtualMeterCmd */
			$infoCmd = virtualMeterCmd::byId(substr($this->getLogicalId(), 5));
			if (!is_object($infoCmd)) {
				log::add('virtualMeter', 'error', "Info command not found for stop command {$this->getLogicalId()} on {$eqLogic->getName()}");
				return;
			}
			if (is_null($meterValue = $infoCmd->getMeterValue())) {
				return;
			}
			$infoCmd->updateConso($meterValue);
		} else {
			log::add('virtualMeter', 'error', "Unknown command: {$this->getLogicalId()} on {$eqLogic->getName()}");
		}
	}
}
