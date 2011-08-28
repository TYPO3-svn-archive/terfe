<?php
	$extensionClassesPath = t3lib_extMgm::extPath('ter_fe2', 'Classes/');

	return array(
		'tx_terfe2_domain_model_author'                                    => $extensionClassesPath . 'Domain/Model/Author.php',
		'tx_terfe2_domain_model_category'                                  => $extensionClassesPath . 'Domain/Model/Category.php',
		'tx_terfe2_domain_model_experience'                                => $extensionClassesPath . 'Domain/Model/Experience.php',
		'tx_terfe2_domain_model_extension'                                 => $extensionClassesPath . 'Domain/Model/Extension.php',
		'tx_terfe2_domain_model_extensionmanagercacheentry'                => $extensionClassesPath . 'Domain/Model/ExtensionManagerCacheEntry.php',
		'tx_terfe2_domain_model_media'                                     => $extensionClassesPath . 'Domain/Model/Media.php',
		'tx_terfe2_domain_model_relation'                                  => $extensionClassesPath . 'Domain/Model/Relation.php',
		'tx_terfe2_domain_model_tag'                                       => $extensionClassesPath . 'Domain/Model/Tag.php',
		'tx_terfe2_domain_model_version'                                   => $extensionClassesPath . 'Domain/Model/Version.php',
		'tx_terfe2_domain_repository_abstractrepository'                   => $extensionClassesPath . 'Domain/Repository/AbstractRepository.php',
		'tx_terfe2_domain_repository_categoryrepository'                   => $extensionClassesPath . 'Domain/Repository/CategoryRepository.php',
		'tx_terfe2_domain_repository_extensionmanagercacheentryrepository' => $extensionClassesPath . 'Domain/Repository/ExtensionManagerCacheEntryRepository.php',
		'tx_terfe2_domain_repository_extensionrepository'                  => $extensionClassesPath . 'Domain/Repository/ExtensionRepository.php',
		'tx_terfe2_domain_repository_tagrepository'                        => $extensionClassesPath . 'Domain/Repository/TagRepository.php',
		'tx_terfe2_extensionprovider_abstractprovider'                     => $extensionClassesPath . 'ExtensionProvider/AbstractProvider.php',
		'tx_terfe2_extensionprovider_extensionmanagerprovider'             => $extensionClassesPath . 'ExtensionProvider/ExtensionManagerProvider.php',
		'tx_terfe2_extensionprovider_providerinterface'                    => $extensionClassesPath . 'ExtensionProvider/ProviderInterface.php',
		'tx_terfe2_extensionprovider_providermanager'                      => $extensionClassesPath . 'ExtensionProvider/ProviderManager.php',
		'tx_terfe2_persistence_abstractpersistence'                        => $extensionClassesPath . 'Persistence/AbstractPersistence.php',
		'tx_terfe2_persistence_persistenceinterface'                       => $extensionClassesPath . 'Persistence/PersistenceInterface.php',
		'tx_terfe2_persistence_registry'                                   => $extensionClassesPath . 'Persistence/Registry.php',
		'tx_terfe2_persistence_session'                                    => $extensionClassesPath . 'Persistence/Session.php',
		'tx_terfe2_task_updateextensionlisttask'                           => $extensionClassesPath . 'Task/UpdateExtensionListTask.php',
		'tx_terfe2_task_updateextensionlisttaskadditionalfieldprovider'    => $extensionClassesPath . 'Task/UpdateExtensionListTaskAdditionalFieldProvider.php',
		'tx_terfe2_utility_archive'                                        => $extensionClassesPath . 'Utility/Archive.php',
		'tx_terfe2_utility_files'                                          => $extensionClassesPath . 'Utility/Files.php',
		'tx_terfe2_utility_soap'                                           => $extensionClassesPath . 'Utility/Soap.php',
		'tx_terfe2_utility_typoscript'                                     => $extensionClassesPath . 'Utility/TypoScript.php',
	);
?>