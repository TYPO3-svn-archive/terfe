<?php
	/*******************************************************************
	 *  Copyright notice
	 *
	 *  (c) 2011 Kai Vogel <kai.vogel@speedprogs.de>, Speedprogs.de
	 *
	 *  All rights reserved
	 *
	 *  This script is part of the TYPO3 project. The TYPO3 project is
	 *  free software; you can redistribute it and/or modify
	 *  it under the terms of the GNU General Public License as
	 *  published by the Free Software Foundation; either version 2 of
	 *  the License, or (at your option) any later version.
	 *
	 *  The GNU General Public License can be found at
	 *  http://www.gnu.org/copyleft/gpl.html.
	 *
	 *  This script is distributed in the hope that it will be useful,
	 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
	 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 *  GNU General Public License for more details.
	 *
	 *  This copyright notice MUST APPEAR in all copies of the script!
	 ******************************************************************/

	/**
	 * A Filesystem Extension Provider for the Scheduler Task
	 *
	 * @version $Id$
	 * @copyright Copyright belongs to the respective authors
	 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
	 */
	class Tx_TerFe2_ExtensionProvider_FileProvider extends Tx_TerFe2_ExtensionProvider_AbstractExtensionProvider {

		/**
		 * Returns all Extension information for the Scheduler Task
		 *
		 * @param integer $lastUpdate Last update of the extension list
		 * @return array Extension information
		 */
		public function getUpdateInfo($lastUpdate) {
			$extPath = (!empty($this->configuration['extensionRootPath']) ? $this->configuration['extensionRootPath'] : 'fileadmin/ter/');
			$files = Tx_TerFe2_Utility_Files::getFiles($extPath, 't3x', (int) $lastUpdate, TRUE);
			if (empty($files)) {
				return array();
			}

			// Generate Extension information
			$updateInfoArray = array();
			foreach ($files as $fileName) {
				$extInfo = $this->getExtensionInfo($fileName);
				if (!empty($extInfo)) {
					$updateInfoArray[] = $extInfo;
					continue;
				}
			}

			return $updateInfoArray;
		}


		/**
		 * Generates an array with all Extension information
		 *
		 * @param string $fileName Filename of the relating t3x file
		 * @return array Extension information
		 */
		protected function getExtensionInfo($fileName) {
			if (empty($fileName)) {
				return array();
			}

			// Unpack file and get extension details
			$extContent = Tx_TerFe2_Utility_Files::unpackT3xFile($fileName);
			unset($extContent['FILES']);

			$extInfo = array(
				'extKey'            => $extContent['extKey'],
				'forgeLink'         => '',
				'hudsonLink'        => '',
				'title'             => $extContent['EM_CONF']['title'],
				'description'       => $extContent['EM_CONF']['description'],
				'fileHash'          => Tx_TerFe2_Utility_Files::getFileHash($fileName),
				'author'            => $extContent['EM_CONF']['author'],
				'authorEmail'       => $extContent['EM_CONF']['author_email'],   // Missing in version object
				'authorCompany'     => $extContent['EM_CONF']['author_company'], // Missing in version object
				'versionNumber'     => t3lib_div::int_from_ver($extContent['EM_CONF']['version']),
				'versionString'     => $extContent['EM_CONF']['version'],
				'uploadComment'     => '',
				'state'             => $extContent['EM_CONF']['state'],
				'emCategory'        => $extContent['EM_CONF']['category'],
				'loadOrder'         => $extContent['EM_CONF']['loadOrder'],
				'priority'          => $extContent['EM_CONF']['priority'],
				'shy'               => $extContent['EM_CONF']['shy'],
				'internal'          => $extContent['EM_CONF']['internal'],
				'module'            => $extContent['EM_CONF']['module'],
				'doNotLoadInFe'     => $extContent['EM_CONF']['doNotLoadInFE'],
				'uploadfolder'      => (bool) $extContent['EM_CONF']['uploadfolder'],
				'createDirs'        => $extContent['EM_CONF']['createDirs'],
				'modifyTables'      => $extContent['EM_CONF']['modify_tables'],
				'clearCacheOnLoad'  => (bool) $extContent['EM_CONF']['clearcacheonload'],
				'lockType'          => $extContent['EM_CONF']['lockType'],
				'cglCompliance'     => $extContent['EM_CONF']['CGLcompliance'],
				'cglComplianceNote' => $extContent['EM_CONF']['CGLcompliance_note'],
				'softwareRelation'  => array(), // dependencies, conflicts, suggests, TYPO3_version, PHP_version
			);

			// Add TYPO3 version requirement
			if (!empty($extContent['EM_CONF']['PHP_version'])) {
				$extInfo['softwareRelation'][] = array(
					'relationType'  => 'dependancy',
					'relationKey'   => 'typo3',
					'softwareType'  => 'system',
					'versionRange'  => $extContent['EM_CONF']['TYPO3_version'],
				);
			}

			// Add PHP version requirement
			if (!empty($extContent['EM_CONF']['PHP_version'])) {
				$extInfo['softwareRelation'][] = array(
					'relationType'  => 'dependancy',
					'relationKey'   => 'php',
					'softwareType'  => 'system',
					'versionRange'  => $extContent['EM_CONF']['PHP_version'],
				);
			}

			return $extInfo;
		}


		/**
		 * Returns URL to a file via extKey, version and fileType
		 * 
		 * @param string $extKey Extension key
		 * @param string $versionString Version string
		 * @param string $fileType File type
		 * @return string URL to file
		 */
		public function getUrlToFile($extKey, $versionString, $fileType) {
			// Get fileName
			$fileName = Tx_TerFe2_Utility_Files::getT3xRelPath($extKey, $versionString, $fileType);

			// Get path to local Extension directory
			$extensionRootPath = 'fileadmin/ter/';
			if (!empty($this->configuration['extensionRootPath'])) {
				$extensionRootPath = rtrim($this->configuration['extensionRootPath'], '/ ') . '/';
			}

			// Check if file exists and is readable
			if (!Tx_TerFe2_Utility_Files::fileExists(PATH_site . $extensionRootPath . $fileName)) {
				return '';
			}

			return t3lib_div::locationHeaderUrl($extensionRootPath . $fileName);
		}

	}
?>