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
	 * Extension provider using local files
	 */
	class Tx_TerFe2_Object_ObjectBuilder implements t3lib_Singleton {

		/**
		 * @var Tx_Extbase_Persistence_Mapper_DataMapper
		 */
		protected $dataMapper;

		/**
		 * @var Tx_Extbase_Persistence_Session
		 */
		protected $persistenceSession;

		/**
		 * @var array
		 */
		protected $objects;


		/**
		 * Injects the object storage
		 *
		 * @param Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper
		 * @return void
		 */
		public function injectDataMapper(Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper) {
			$this->dataMapper = $dataMapper;
		}


			/**
		 * Injects the persistence session
		 *
		 * @param Tx_Extbase_Persistence_Session $persistenceSession
		 * @return void
		 */
		public function injectPersistenceSession(Tx_Extbase_Persistence_Session $persistenceSession) {
			$this->persistenceSession = $persistenceSession;
		}


		/**
		 * Create an object from given class and attributes
		 * 
		 * @param string $className Name of the class
		 * @param string $identifier String to uniquely identify an object
		 * @param array $attributes Array of all class attributes
		 * @return void
		 */
		public function create($className, $identifier, array $attributes) {
			if (empty($className) || empty($identifier) || empty($attributes)) {
				throw new Exception('No valid params given to create an object');
			}

			if (!empty($this->objects[$className][$identifier])) {
				return;
			}

			$object = reset($this->dataMapper->map($className, array($attributes)));
			$this->objects[$className][$identifier] = $object;
			$this->persistenceSession->unregisterReconstitutedObject($object);
		}


		/**
		 * Return a stored object
		 * 
		 * @param string $className Name of the class
		 * @param string $identifier String to uniquely identify an object
		 * @return Tx_Extbase_DomainObject_DomainObjectInterface Stored object
		 */
		public function get($className, $identifier) {
			if (empty($className) || empty($identifier)) {
				throw new Exception('No valid params given to return an object');
			}

			if (!empty($this->objects[$className][$identifier])) {
				return $this->objects[$className][$identifier];
			}

			return NULL;
		}


		/**
		 * Returns all objects
		 * 
		 * @return array All objects
		 */
		public function getAll() {
			return $this->objects;
		}

	}
?>