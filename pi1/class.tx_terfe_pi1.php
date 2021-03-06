<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2006 Robert Lemke (robert@typo3.org)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
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
 ***************************************************************/
/**
 * Plugin 'TER Frontend' for the 'ter_fe' extension.
 *
 * $Id$
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @author	Michael Scharkow <michael@underused.org
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class tx_terfe_pi1 extends tslib_pibase
 *   93:	 protected function init($conf)
 *  115:	 public function main($content,$conf)
 *  150:	 protected function renderListView_new()
 *  205:	 protected function renderListView_categories()
 *  244:	 protected function renderListView_popular()
 *  299:	 protected function renderListView_search()
 *  327:	 protected function renderListView_searchResult()
 *  372:	 protected function renderListView_compactList()
 *  447:	 protected function renderSingleView_extension ($extensionKey, $version)
 *  520:	 protected function renderSingleView_extensionDetails ($extensionRecord)
 *  581:	 protected function renderSingleView_feedbackForm ($extensionRecord)
 *  649:	 protected function renderListView_detailledExtensionRecord($extensionRecord)
 *  704:	 protected function renderListView_shortExtensionRecord($extensionRecord)
 *  738:	 protected function getUploadHistory($extensionKey, $lastuploaddate)
 *  767:	 protected function renderTopMenu()
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib . 'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('ter_fe') . 'class.tx_terfe_common.php');
require_once('class.tx_terfe_ratings.php');

if (t3lib_extMgm::isLoaded('ter_doc')) {
	require_once (t3lib_extMgm::extPath('ter_doc') . 'class.tx_terdoc_api.php');
}

/**
 * Plugin TER frontend
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage tx_terfe
 */
class tx_terfe_pi1 extends tslib_pibase
{

	public $prefixId = 'tx_terfe_pi1'; // Same as class name
	public $scriptRelPath = 'pi1/class.tx_terfe_pi1.php'; // Path to this script relative to the extension dir.
	public $extKey = 'ter_fe'; // The extension key.
	public $pi_checkCHash = TRUE; // Handle empty CHashes correctly

	protected $commonObj; // Instance of the common TER FE plugin code library
	protected $viewMode = ''; // View mode, one of the following: LATEST, CATEGORIES, FULLLIST

	protected $feedbackMailsCCAddress = ''; // Email address(es) which also receive the feedback emails
	protected $standardSelectionClause = 'state <> "obsolete" AND reviewstate > -1'; // Standard selection criteria for listing of extensions, reviewstate<0 are insecure extensions
	protected $tooFewReviewsMode = TRUE; // If set, by default unreviewed extensions appear in all modes but "unsupported". This is for the time when we yet don't have enough reviews
	protected $template;

	/**
	 * Initializes the plugin, only called from main()
	 *
	 * @param	array		$conf: The plugin configuration array
	 * @return	void
	 * @access	protected
	 */
	protected function init($conf)
	{
		global $TSFE;

		$this->conf = $conf;
		$this->pi_setPiVarDefaults(); // Set default piVars from TS
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin
		$this->pi_loadLL();

		$this->commonObj = new tx_terfe_common($this);
		$this->commonObj->repositoryDir = $this->conf['repositoryDirectory'];
		if (substr($this->commonObj->repositoryDir, -1, 1) != '/') $this->commonObj->repositoryDir .= '/';
		$this->commonObj->init();

		if (!$this->conf['templateFile']) {
			$this->conf['templateFile'] = 'EXT:' . $this->extKey . '/templates/template_pi1.html';
		}
		$this->template = $this->cObj->fileResource($this->conf['templateFile']);
	}

	/**
	 * The plugin's main function
	 *
	 * @param	string		$content: Content rendered so far (not used)
	 * @param	array		$conf: The plugin configuration array
	 * @return	string		The plugin's HTML output
	 * @access	public
	 */
	public function main($content, $conf)
	{
		$this->init($conf);

		if (!@is_dir($this->commonObj->repositoryDir)) return 'TER_FE Error: Repository directory (' . $this->commonObj->repositoryDir . ') does not exist!';

		if ($this->piVars['showExt']) {
			$subContent = $this->renderSingleView_extension($this->piVars['showExt'], $this->piVars['version']);
		} else {
			if (!$this->piVars['view']) $this->piVars['view'] = 'new';
			switch ($this->piVars['view']) {
				case 'new':
					$subContent = $this->renderListView_new();
					break;
				case 'categories':
					$subContent = $this->renderListView_categories();
					break;
				case 'popular':
					$subContent = $this->renderListView_popular();
					break;
				case 'fulllist':
					$subContent = $this->renderListView_compactList();
					break;
				case 'search':
					$subContent = $this->renderListView_search();
					break;
			}
		}

		// Put everything together:
		#$content = $this->getTopMenu(array('new', 'popular', 'fulllist', 'search')) . '<br />' . $subContent;
		$content = $this->renderTopMenu() . $subContent;

		return $this->pi_wrapInBaseClass($content);
	}


	/**
	 * Renders a list of extensions which have been recently uploaded to
	 * the repository
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderListView_new()
	{
		global $TYPO3_DB, $TSFE;

		$numberOfDays = 20;
		$tableRows = array();

		$subpart = $this->cObj->getSubpart($this->template, '###LISTVIEW###');

		// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
		if (t3lib_extMgm::isLoaded('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion('_all', '');
		}

		$res = $TYPO3_DB->exec_SELECTquery(
			'e.*,rating,votes',
			'tx_terfe_extensions as e LEFT JOIN tx_terfe_ratingscache USING(extensionkey,version)',
			$this->standardSelectionClause . ' AND lastuploaddate > ' . (time() - ($numberOfDays * 24 * 3600)),
			'',
			'lastuploaddate DESC',
			'30'
		);
		$alreadyRenderedExtensionKeys = array();
		if ($res) {
			while ($extensionRecord = $TYPO3_DB->sql_fetch_assoc($res)) {
				if (!t3lib_div::inArray($alreadyRenderedExtensionKeys, $extensionRecord['extensionkey'])) {
					$tableRows[] = $this->renderListView_detailledExtensionRecord($extensionRecord);
					$alreadyRenderedExtensionKeys[] = $extensionRecord['extensionkey'];
				}
			}
		}

		$markerArray = array(
			'###ACTION###' => $this->pi_getPageLink($TSFE->id),
			'###SEARCHBUTTONTEXT###' => $this->pi_getLL('listview_search_searchbutton', '', TRUE),
			'###SEARCHMESSAGE###' => sprintf($this->pi_getLL('listview_new_introduction', '', TRUE), $numberOfDays),
			'###SEARCHRESULTS###' => implode(PHP_EOL, $tableRows),
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Renders a list of extensions grouped by categories
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderListView_categories()
	{
		global $TYPO3_DB;

		$numberOfDays = 50;
		$tableRows = array();

		$subpart = $this->cObj->getSubpart($this->template, '###CATEGORYVIEW###');

		$res = $TYPO3_DB->exec_SELECTquery(
			'*',
			'tx_terfe_extensions',
			$this->standardSelectionClause . ' AND lastuploaddate > ' . (time() - ($numberOfDays * 24 * 3600)),
			'',
			'lastuploaddate DESC',
			''
		);
		$alreadyRenderedExtensionKeys = array();
		if ($res) {
			while ($extensionRecord = $TYPO3_DB->sql_fetch_assoc($res)) {
				if (!t3lib_div::inArray($alreadyRenderedExtensionKeys, $extensionRecord['extensionkey'])) {
					$tableRows[] = $this->renderListView_detailledExtensionRecord($extensionRecord);
					$alreadyRenderedExtensionKeys[] = $extensionRecord['extensionkey'];
				}
			}
		}

		$markerArray = array(
			'###HEADER###' => htmlspecialchars(sprintf($this->pi_getLL('renderview_new_introduction', ''), $numberOfDays)),
			'###EXTENSIONLIST###' => implode('', $tableRows)
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Renders a list of extensions sorted by popularity
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderListView_popular()
	{
		global $TYPO3_DB;

		$tableRows = array();
		$subpart = $this->cObj->getSubpart($this->template, '###POPULARVIEW###');


		// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
		if (t3lib_extMgm::isLoaded('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$GLOBALS['TSFE']->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion('_all', '');
		}

		$res = $TYPO3_DB->exec_SELECTquery(
			'DISTINCT extensionkey',
			'tx_terfe_extensions',
			'reviewstate > -1',
			'',
			'extensiondownloadcounter DESC',
			'60'
		);
		if ($res) {
			$counter = 0;
			while ($counter < 20 AND $extensionKeyRow = $TYPO3_DB->sql_fetch_assoc($res)) {
				$version = $this->commonObj->db_getLatestVersionNumberOfExtension($extensionKeyRow['extensionkey'], $this->tooFewReviewsMode);

				$res2 = $TYPO3_DB->exec_SELECTquery(
					'e.*,rating,votes',
					'tx_terfe_extensions as e LEFT JOIN tx_terfe_ratingscache USING(extensionkey,version)',
					'e.extensionkey=' . $TYPO3_DB->fullQuoteStr($extensionKeyRow['extensionkey'], 'tx_terfe_extensions') . ' AND ' .
					'e.version=' . $TYPO3_DB->fullQuoteStr($version, 'tx_terfe_extensions')
				);
				if (!$res2) return 'Extension ' . htmlspecialchars($extensionKeyRow['extensionkey']) . ' not found!';
				$extensionRecord = $TYPO3_DB->sql_fetch_assoc($res2);
				if ($extensionRecord['category'] != 'doc' && $extensionRecord['state'] != 'obsolete') {
					$tableRows[] = $this->renderListView_detailledExtensionRecord($extensionRecord);
					$counter++;
				}
			}
		}

		$markerArray = array(
			'###HEADER###' => $this->pi_getLL('listview_popular_introduction', '', TRUE),
			'###EXTENSIONLIST###' => implode('', $tableRows)
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Renders a list of extensions based on a search
	 *
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderListView_search()
	{
		global $TYPO3_DB, $TSFE;

		$subpart = $this->cObj->getSubpart($this->template, '###LISTVIEW###');

		$searchResult = strlen(trim($this->piVars['sword'])) > 2 ? $this->renderListView_searchResult() : '';

		$markerArray = array(
			'###ACTION###' => $this->pi_getPageLink($TSFE->id),
			'###SEARCHBUTTONTEXT###' =>$this->pi_getLL('listview_search_searchbutton', '', 1),
			'###SEARCHRESULTS###' => $searchResult,
			'###SEARCHMESSAGE###' => 'Search for "' . htmlspecialchars($this->piVars['sword']) . '"',
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Renders the actual search result
	 *
	 * @return	string		HTML table with search result
	 * @access	protected
	 * @see renderListView_search
	 */
	protected function renderListView_searchResult() {
		$markerArray = array(
			'###RESULTS###' => $this->pi_getLL('listview_search_noresult', '', 1),
		);

			// Get WHERE statement
		$where = $GLOBALS['TYPO3_DB']->searchQuery(
			explode(' ', $this->piVars['sword']),
			array('extensionkey', 'title', 'authorname', 'description'),
			'e1'
		);

			// Get latest version from each appropriate extension
		$result = $GLOBALS['TYPO3_DB']->sql_query('
			SELECT e1.*, r.rating, r.votes
			FROM tx_terfe_extensions AS e1
			INNER JOIN (SELECT e2.extensionkey, MAX(e2.version) AS version FROM tx_terfe_extensions e2 GROUP BY e2.extensionkey) AS lastVersion
			ON e1.extensionkey = lastVersion.extensionkey AND e1.version = lastVersion.version
			LEFT JOIN tx_terfe_ratingscache AS r ON (r.extensionkey = e1.extensionkey AND r.version = e1.version)
			WHERE ' . $where . ' AND e1.reviewstate > -1
			ORDER BY e1.extensiondownloadcounter DESC, e1.lastuploaddate DESC
		');

			// Build table rows
		if (!empty($result) && $GLOBALS['TYPO3_DB']->sql_num_rows($result)) {
			$tableRows = array();
			while($extension = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
				$tableRows[] = $this->renderListView_detailledExtensionRecord($extension);
			}
			if (!empty($tableRows)) {
				$markerArray['###RESULTS###'] = implode('', $tableRows);
			}
		}

		$subpart = $this->cObj->getSubpart($this->template, '###SEARCHRESULTSROWS###');
		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());

		return $content;
	}

	/**
	 * Renders a compact table list of extensions
	 *
	 * @param	string		$category: category
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderListView_compactList()
	{
		global $TYPO3_DB, $TSFE;

		$sorting = $this->piVars['sorting'];
		$sortingConditions = array(
			'by_title' => '',
			'by_extkey' => 'e.extensionkey ASC,',
			'by_state' => 'state DESC,',
			'by_update' => 'lastuploaddate DESC,',
			'by_rating' => 'rating DESC, votes DESC,'
		);

		$tableRows = array();
		$subpart = $this->cObj->getSubpart($this->template, '###COMPACTLISTVIEW###');

		// get char from piVars
		$char = $this->piVars['char'];
		if ($char == '') {
			$char = '0'; // 0-9
		}
		$charWhere = $char == '0' ? ' AND ASCII(LOWER(SUBSTRING(LTRIM(title),1,1))) < 97'
				: ' AND ASCII(LOWER(SUBSTRING(LTRIM(title),1,1)))=' . ord($char);


		$res = $TYPO3_DB->exec_SELECTquery(
			'ASCII(LOWER(SUBSTRING(LTRIM(title),1,1))) firstchar,e.extensionkey,title,e.version,state,lastuploaddate,rating,votes',
			'tx_terfe_extensions as e LEFT JOIN tx_terfe_ratingscache USING(extensionkey,version)',
			$this->standardSelectionClause . ' AND title != "[REMOVED]" AND title != ""' . $charWhere,
			'',
			$sortingConditions[$sorting] . 'title ASC, lastuploaddate DESC',
			''
		);

		$alreadyRenderedExtensionKeys = array();

		if ($res) {

			// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
			if (t3lib_extMgm::isLoaded('ter_doc')) {
				$terDocAPIObj = tx_terdoc_api::getInstance();
				$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion('_all', '');
			}

			while ($extensionRecord = $TYPO3_DB->sql_fetch_assoc($res)) {
				if (!t3lib_div::inArray($alreadyRenderedExtensionKeys, $extensionRecord['extensionkey'])) {
					$tableRows[] = $this->renderListView_shortExtensionRecord($extensionRecord);
					$alreadyRenderedExtensionKeys[] = $extensionRecord['extensionkey'];
				}
			}
		}

		$charMenu = '';
		for ($i = 96; $i < 123; $i++) {
			$c = $i == 96 ? '[0-9]' : strtoupper(chr($i));
			$piVar = $i == 96 ? '0' : chr($i);

			if ($char == $piVar) { // ACT
				$style = 'padding:0 3px;font-size:150%;font-weight:bold;';
			} else {
				$style = 'padding:0 3px;';
			}

			$charMenu .= '<span style="' . $style . '">' . $this->pi_linkTP_keepPIvars($c, array('char' => $piVar), 1) . '</span>';
		}


		$markerArray = array(
			'###HEADER###' => $this->pi_getLL('listview_fulllist_introduction', '', 1),
			'###CHARMENU###' => $charMenu,
			'###INTRODUCTION###' => $this->pi_getLL('listview_fulllist_introduction', '', 1),
			'###TITLE_HEAD###' => $this->pi_linkTP_keepPIvars($this->commonObj->getLL('extension_title', '', 1), array('sorting' => 'by_title'), 1),
			'###KEY_HEAD###' => $this->pi_linkTP_keepPIvars($this->commonObj->getLL('extension_extensionkey', '', 1), array('sorting' => 'by_extkey'), 1),
			'###DOC_HEAD###' => $this->commonObj->getLL('extension_documentation', '', 1),
			'###STATE_HEAD###' => $this->pi_linkTP_keepPIvars($this->commonObj->getLL('extension_state', '', 1), array('sorting' => 'by_state'), 1),
			'###RATING_HEAD###' => $this->pi_linkTP_keepPIvars($this->commonObj->getLL('extension_rating', '', 1), array('sorting' => 'by_rating'), 1),
			'###LASTUPDATE_HEAD###' => $this->pi_linkTP_keepPIvars($this->commonObj->getLL('extension_lastuploaddate', '', 1), array('sorting' => 'by_update'), 1),
			'###RESULTS###' => implode('', $tableRows),
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}


	/**
	 * Renders the single view for (the latest version of) an extension including several sub views.
	 *
	 * @param	string		$extensionKey: The extension key of the extension to render
	 * @param	string		$version: Version number of the extension or an empty string for displaying the most recent version
	 * @return	string		HTML output
	 */
	protected function renderSingleView_extension($extensionKey, $version = 'current')
	{
		global $TYPO3_DB, $TSFE;

		if (!strlen($version) || $version == 'current') {
			$version = $this->commonObj->db_getLatestVersionNumberOfExtension($extensionKey, $this->tooFewReviewsMode);
		} else {
			$this->no_cache = 1;
		}

		// Fetch the extension record:
		$res = $TYPO3_DB->exec_SELECTquery(
			'e.*, rating, votes',
			'tx_terfe_extensions as e LEFT JOIN tx_terfe_ratingscache USING(extensionkey,version)',
			'e.extensionkey=' . $TYPO3_DB->fullQuoteStr($extensionKey, 'tx_terfe_extensions') . ' AND ' .
			'e.version=' . $TYPO3_DB->fullQuoteStr($version, 'tx_terfe_extensions')
		);
		if (!$res) return 'DB error while looking up extension ' . htmlspecialchars($extensionKey) . '!';
		$extensionRecord = $TYPO3_DB->sql_fetch_assoc($res);
		if (!$extensionRecord || $extensionRecord['reviewstate'] == -1) return 'Extension ' . htmlspecialchars($extensionKey) . ' not found!';

		// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
		if (t3lib_extMgm::isLoaded('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion($extensionRecord['extensionkey'], $extensionRecord['version']);
		}

		// Prepare the top menu items:
		if (!$this->piVars['extView']) {
			$this->piVars['extView'] = 'info';
		}
		$menuItems = array('info', 'rating', 'feedback'); // 'rating' enabled

		// Render the top menu
		$topMenu = '';
		$subpart = $this->cObj->getSubpart($this->template, '###TOPMENUROW###');
		foreach ($menuItems as $itemKey) {
			$itemActive = ($this->piVars['extView'] == $itemKey);
			$link = $this->pi_linkTP_keepPIvars($this->pi_getLL('extensioninfo_views_' . $itemKey, '', 1), array('showExt' => $extensionKey, 'extView' => $itemKey, 'viewFile' => ''), 1);
			$markerArray = array(
				'###CLS###' => $itemActive ? 'class="submenu-button-active"' : 'class="submenu-button"',
				'###LINK###' => $link,
			);
			$topMenu .= $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());;

		}

		$subpart = $this->cObj->getSubpart($this->template, '###SUBCONTENTROW###');
		$markerArray  = array(
			'###RECORD###' => $this->renderListView_detailledExtensionRecord($extensionRecord),

		);

		// Render content of the currently selected view:
		switch ($this->piVars['extView']) {
			case 'feedback' :
				$markerArray['###ADD###'] = '<li class="feedbackform">' . $this->renderSingleView_feedbackForm($extensionRecord) . '</li>';
				break;

			case 'rating':
				$rating = new tx_terfe_ratings($extensionRecord, $this);
				$rating->process_rating();

				$markerArray['###ADD###'] = $rating->renderSingleView_rating();

				break;
			case 'info':
			default:
				$markerArray['###ADD###'] = $this->renderSingleView_extensionDetails($extensionRecord);
		}

		$subContent = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());


		$subpart = $this->cObj->getSubpart($this->template, '###SINGLEVIEW###');
		$markerArray = array(
			'###TITLE###' => htmlspecialchars($extensionRecord['title']),
			'###TOPMENU###' => $topMenu,
			'###SUBCONTENT###' => $subContent,
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Renders the details sub view of an extension single view
	 *
	 * @param	array		$extensionRecord: The extension record
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderSingleView_extensionDetails($extensionRecord)
	{
		global $TSFE;

		$extensionRecord = $this->commonObj->db_prepareExtensionRecordForOutput($extensionRecord);
		$extDetailsRow = $this->commonObj->db_getExtensionDetails($extensionRecord['extensionkey'], $extensionRecord['version']);

		// Set the magic "reg1" so we can clear the cache for this manual if a new one is uploaded:
		if (t3lib_extMgm::isLoaded('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$TSFE->page_cache_reg1 = $terDocAPIObj->createAndGetCacheUidForExtensionVersion($extensionRecord['extensionkey'], $extensionRecord['version']);
		}

		// Compile detail rows information:
		$detailRows = '';
		$downloadUrl = $this->commonObj->getExtensionVersionPathAndBaseName($extensionRecord['extensionkey'], $extensionRecord['version']) . '.t3x';
		$t3xDownloadURL = substr($downloadUrl, strlen(PATH_site));

		$markerArray = array(
			'###DEP_LABEL###' => $this->commonObj->getLL('extension_dependencies', '', 1),
			'###REVDEP_LABEL###' => $this->commonObj->getLL('extension_reversedependencies', '', 1),
			'###FILES_LABEL###' => $this->commonObj->getLL('extension_files', '', 1),
			'###HISTORY_LABEL###' => $this->commonObj->getLL('extension_history', '', 1),
			'###DOWNLOAD_LABEL###' => $this->commonObj->getLL('extension_download_extension', '', 1),
			'###DEP###' => $this->commonObj->getRenderedDependencies($extensionRecord['dependencies']),
			'###REVDEP###' => $this->commonObj->getRenderedReverseDependencies($extensionRecord['extensionkey'], $extensionRecord['version']),
			'###FILES###' => $this->commonObj->getRenderedListOfFiles($extDetailsRow),
			'###HISTORY###' => $this->getUploadHistory($extensionRecord['extensionkey'], $extensionRecord['lastuploaddate_raw']),
			'###DOWNLOADURL###' => $downloadUrl,
			'###DOWNLOAD###' => $this->commonObj->getLL('extensionfiles_downloadcompressedt3x', '', 1),
		);

		$subpart = $this->cObj->getSubpart($this->template, '###EXTENSIONDETAILS###');
		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Renders the feedback sub view of an extension single view
	 *
	 * @param	array		$extensionRecord: The extension record
	 * @return	string		HTML output
	 * @access	protected
	 */
	protected function renderSingleView_feedbackForm($extensionRecord)
	{
		global $TSFE;

		$TSFE->no_cache = 1;

		$extDetailsRow = $this->commonObj->db_getExtensionDetails($extensionRecord['extensionkey'], $extensionRecord['version']);
		$authorName = htmlspecialchars($extensionRecord['authorname']);
		$defaultMessage = 'Hi ' . $extensionRecord['authorname'] . ',' . chr(10) . chr(10) . '...' . chr(10) . chr(10) . 'Best regards' . chr(10) . $TSFE->fe_user->user['name'] . ' (' . $TSFE->fe_user->user['username'] . ')';

		if (is_array($this->piVars['DATA']) && trim($this->piVars['DATA']['comment']) && trim($this->piVars['DATA']['sender_email']) && strcmp(trim(ereg_replace('[[:space:]]', '', $defaultMessage)), trim(ereg_replace('[[:space:]]', '', $this->piVars['DATA']['comment'])))) {

			session_start();
			$captchaString = $_SESSION['tx_captcha_string'];
			$_SESSION['tx_captcha_string'] = '';

			$subpart = $this->cObj->getSubpart($this->template, '###FEEDBACK_SENDED###');
			$markerArray = array(
				'###HEADER###' => $this->pi_getLL('extensioninfo_feedback_emailsent', '', 1),
				'###INTRODUCTION###' => ''
			);

			if (t3lib_div::validEmail($this->piVars['DATA']['sender_email'])) {


				if ($captchaString == $this->piVars['DATA']['captcha']) {
					$message = 'TER feedback - ' . $extensionRecord['extensionkey'] . chr(10) . trim($this->piVars['DATA']['comment']);
					$this->cObj->sendNotifyEmail($message, $extensionRecord['authoremail'], $this->feedbackMailsCCAddress, $this->piVars['DATA']['sender_email'], $this->piVars['DATA']['sender_name']);

					$markerArray['###MESSAGE###'] = htmlspecialchars(sprintf($this->pi_getLL('extensioninfo_feedback_emailsent_details'), $extensionRecord['authoremail']));
				} else {
					$markerArray['###MESSAGE###'] = $this->pi_getLL('extensioninfo_feedback_invalidcaptcha', '', 1) . '</p>';
				}
			} else {
				$markerArray['###MESSAGE###'] = htmlspecialchars(sprintf($this->pi_getLL('extensioninfo_feedback_invalidemailaddress'), $this->piVars['DATA']['sender_email']));
			}
		} else {
			$subpart = $this->cObj->getSubpart($this->template, $TSFE->loginUser ?  '###FEEDBACKFORM_LOGGEDIN###' : '###FEEDBACKFORM###');
			$markerArray = array(
				'###HEADER###' => $this->pi_getLL('extensioninfo_feedback_feedbacktotheauthor', '', 1),
				'###MESSAGE###' => htmlspecialchars(sprintf($this->pi_getLL('extensioninfo_feedback_introduction'), $authorName)),
				'###INTRODUCTION###' => $this->pi_getLL('extensioninfo_feedback_moreintroduction', '', 1),
				'###ACTION###' => t3lib_div::getIndpEnv('REQUEST_URI'),
				'###YOURNAME_LABEL###' => $this->pi_getLL('extensioninfo_feedback_yourname', '', 1),
				'###PREFIX###' => $this->prefixId,
				'###YOURNAME###' => htmlspecialchars($TSFE->fe_user->user['name'] . ' (' . $TSFE->fe_user->user['username'] . ')'),
				'###YOUREMAIL_LABEL###' => $this->pi_getLL('extensioninfo_feedback_youremailaddress', '', 1),
				'###YOUREMAIL###' => htmlspecialchars($TSFE->fe_user->user['email']),
				'###YOURCOMMENT_LABEL###' => $this->pi_getLL('extensioninfo_feedback_yourcomment', '', 1),
				'###DEFAULT_MESSAGE###' => trim($defaultMessage),
				'###SUBMIT###' => $this->pi_getLL('extensioninfo_feedback_sendfeedback', '', 1),
			);
		}
		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Render the detailled extension info row for listing of categories, news etc.
	 *
	 * @param	array		$extensionRecord: Database record from tx_terfe_extensions
	 * @return	string		Two HTML table rows wrapped in <tr>
	 */
	protected function renderListView_detailledExtensionRecord($extensionRecord)
	{
		global $TSFE;

		$subpart = $this->cObj->getSubpart($this->template, '###EXTENSIONRECORD###');

		if (t3lib_extMgm::isLoaded('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$documentationLink = $terDocAPIObj->getDocumentationLink($extensionRecord['extensionkey'], $extensionRecord['version']);
		} else {
			$documentationLink = $this->commonObj->getLL('general_terdocnotinstalled', '', 1);
		}
		$extensionRecord = $this->commonObj->db_prepareExtensionRecordForOutput($extensionRecord);
		$extensionRecord['reviewstate_label'] = $extensionRecord['reviewstate_raw'] ? 'reviewed' : 'unreviewed';

		$markerArray = array(
			'###ICON###' => $this->commonObj->getIcon_extension($extensionRecord['extensionkey'], $extensionRecord['version']),
			'###TITLE###' => $this->commonObj->getLL('extension_title', '', 1),
			'###DETAILLINK###' => $this->pi_linkTP_keepPIvars($extensionRecord['title'], array('view' => 'view', 'showExt' => $extensionRecord['extensionkey'], 'version' => 'current'), 1, 1),
			'###EXTENSIONKEY_LABEL###' => $this->commonObj->getLL('extension_extensionkey', '', 1),
			'###EXTENSIONKEY###' => htmlspecialchars($extensionRecord['extensionkey']),
			'###EXTENSIONSTATE_LABEL###' => $this->commonObj->getLL('extension_state', '', 1),
			'###EXTENSIONSTATE_CLS###' => htmlspecialchars(strtolower($extensionRecord['state_raw'])),
			'###EXTENSIONSTATE###' => $extensionRecord['state_raw'],
			'###AUTHORNAME_LABEL###' => $this->commonObj->getLL('extension_authorname', '', 1),
			'###AUTHORNAME###' => htmlspecialchars($extensionRecord['authorname']),
			'###CATEGORY_LABEL###' => $this->commonObj->getLL('extension_category', '', 1),
			'###CATEGORY###' => htmlspecialchars($extensionRecord['category']),
			'###VERSION_LABEL###' => $this->commonObj->getLL('extension_version', '', 1),
			'###VERSION###' => htmlspecialchars($extensionRecord['version']),
			'###DOC_LABEL###' => $this->commonObj->getLL('extension_documentation', '', 1),
			'###DOC_LINK###' => $documentationLink,
			'###DOWNLOADCOUNT_LABEL###' => $this->commonObj->getLL('extension_downloads', '', 1),
			'###DOWNLOADCOUNT###' => htmlspecialchars($extensionRecord['versiondownloadcounter']),
			'###RATING_LABEL###' => $this->commonObj->getLL('extension_rating', '', 1),
			'###RATING###' => $extensionRecord['rating']
					? $extensionRecord['rating'] . ' ' . $this->pi_linkTP_keepPIvars('(' . $extensionRecord['votes'] . ' votes)', array('view' => 'view', 'showExt' => $extensionRecord['extensionkey'], 'version' => $extensionRecord['version'], 'extView' => 'rating'), 1, 1)
					: 'none',
			'###LASTUPLOAD_LABEL###' => $this->commonObj->getLL('extension_lastuploaddate', '', 1),
			'###LASTUPLOAD###' => htmlspecialchars($extensionRecord['lastuploaddate']),
			'###UPLOADCOMMENT_LABEL###' => $this->commonObj->getLL('extension_uploadcomment', '', 1),
			'###UPLOADCOMMENT###' => htmlspecialchars($extensionRecord['uploadcomment']),
			'###DESCRIPTION_LABEL###' => $this->commonObj->getLL('extension_description', '', 1),
			'###DESCRIPTION###' => htmlspecialchars($extensionRecord['description']),
		);


		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Render a short extension info row for the full listing of extensions
	 *
	 * @param	array		$extensionRecord: Database record from tx_terfe_extensions
	 * @return	string		Two HTML table rows wrapped in <tr>
	 */
	protected function renderListView_shortExtensionRecord($extensionRecord)
	{
		global $TSFE;

		if (t3lib_extMgm::isLoaded('ter_doc')) {
			$terDocAPIObj = tx_terdoc_api::getInstance();
			$documentationLink = $terDocAPIObj->getDocumentationLink($extensionRecord['extensionkey'], $extensionRecord['version']);
		} else {
			$documentationLink = '';
		}

		$extensionRecord = $this->commonObj->db_prepareExtensionRecordForOutput($extensionRecord);
		$rowClass = $this->oddRow ? 'class="even"' : '';
		$this->oddRow = 1 - $this->oddRow;

		$subpart = $this->cObj->getSubpart($this->template, '###SHORTEXTENSIONRECORD###');
		$markerArray = array(
			'###ROWCLASS###' => $rowClass,
			'###LINK###' => $this->pi_linkTP_keepPIvars($extensionRecord['title'], array('view' => 'view', 'showExt' => $extensionRecord['extensionkey'], 'version' => $extensionRecord['version']), 1, 1),
			'###KEY###' => htmlspecialchars($extensionRecord['extensionkey']),
			'###DOCLINK###' => $documentationLink,
			'###CLS_STATE###' => strtolower($extensionRecord['state']),
			'###STATE###' => $extensionRecord['state'],
			'###RATING###' => $extensionRecord['rating'] ? $extensionRecord['rating'] : 'none',
			'###LASTZPLOAD###' => $extensionRecord['lastuploaddate'],
		);

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}

	/**
	 * Returns a list of recent version numbers and upload comments, max 5 items
	 *
	 * @param	string		$extensionKey: Extension Key
	 * @param	int		$lastuploaddate: Timestamp of last upload date
	 * @return	mixed		List of upload comments in reverse order or FALSE if an error ocurred
	 * @access	protected
	 */
	protected function getUploadHistory($extensionKey, $lastuploaddate)
	{
		global $TYPO3_DB;

		$res = $TYPO3_DB->exec_SELECTquery(
			'version,uploadcomment',
			'tx_terfe_extensions',
			'extensionkey=' . $TYPO3_DB->fullQuoteStr($extensionKey, 'tx_terfe_extensions') . ' AND lastuploaddate < "' . intval($lastuploaddate) . '"',
			'',
			'lastuploaddate DESC',
			'5'
		);

		if ($res) {
			$subpart = $this->cObj->getSubpart($this->template, '###UPLOADHISTORY###');
			$subpartRow = $this->cObj->getSubpart($subpart, '###ROW###');
			$row = '';
			$markerArray = $subpartArray = array();
			while ($result = $TYPO3_DB->sql_fetch_assoc($res)) {
				if ($result['uploadcomment']) {
					$markerArray['###ITEM###'] =  $result['version'] . ': ' . htmlspecialchars($result['uploadcomment']);
					$row .= $this->cObj->substituteMarkerArrayCached($subpartRow, $markerArray, array(), array());
				}
			}
			$subpartArray['###ROW###'] = $row;
			return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, array());
		} else return FALSE;
	}

	/**
	 * Renders the top tab menu which allows for selection of the different views.
	 *
	 * @return	string		HTML output, enclosed in a DIV
	 * @access	protected
	 */
	protected function renderTopMenu()
	{

		// Prepare the top menu items:
		$menuItems = array('new', 'popular', 'fulllist', 'search'); # FIXME: disabled: categories
		$subpart = $this->cObj->getSubpart($this->template, '###TOPMAINMENU###');
		$markerArray = array(
			'###EXTRELPATH###' => t3lib_extMgm::siteRelPath('ter_fe'),
			'###ACTIVE_CLS_NEW###' => '',
			'###ACTIVE_CLS_POPULAR###' => '',
			'###ACTIVE_CLS_FULLLIST###' => '',
			'###ACTIVE_CLS_SEARCH###' => '',
		);

		// Render the top menu
		$counter = 0;
		foreach ($menuItems as $itemKey) {
			if ($this->piVars['view'] == $itemKey) {
				$activeItemsArr[$counter] = TRUE;
				$markerArray['###ACTIVE_CLS_' . strtoupper($itemKey) . '###'] = ' class="active"';
			}
			$counter++;
		}



		$counter = 0;
		$topMenuItems = '';
		foreach ($menuItems as $itemKey) {
			$this->pi_linkTP('', array('tx_terfe_pi1[view]' => $itemKey), 1);
			$link = '<a href="' . $this->cObj->lastTypoLinkUrl . '" ' . ($activeItemsArr[$counter] ? 'class="active"'
					: '') . '>' . $this->pi_getLL('views_' . $itemKey, '', 1) . '</a>';

			$markerArray['###' . strtoupper($itemKey) . '###'] = $link;
			if ($activeItemsArr[$counter]) {
				if ($counter > 0) {
					$topMenuItems .= '<div><img src="' . t3lib_extMgm::siteRelPath('ter_fe') . 'res/terfe-tabnav-act-left.gif" alt="" /></div>';
				}
				$topMenuItems .= $link . '
					<div><img src="' . t3lib_extMgm::siteRelPath('ter_fe') . 'res/terfe-tabnav-act-right.gif" alt="" /></div>
				';
			} else {
				if ($counter > 0 && !$activeItemsArr[$counter - 1]) {
					$topMenuItems .= '<div><img src="' . t3lib_extMgm::siteRelPath('ter_fe') . 'res/terfe-tabnav-right.gif" alt="" /></div>';
				}
				$topMenuItems .= $link;
			}

			$counter++;
		}

		$topMenu = '
			<div class="terfe-tabnav">
				<div><img src="' . t3lib_extMgm::siteRelPath('ter_fe') . 'res/terfe-tabnav-' . ($activeItemsArr[0]
				? 'act-' : '') . 'start.gif" alt="" /></div>
				' . $topMenuItems . '
				<div><img src="' . t3lib_extMgm::siteRelPath('ter_fe') . 'res/terfe-tabnav-end.gif" alt="" /></div>
			</div>
		';
		$markerArray['###OLDMENU###'] = $topMenu;

		$content = $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, array(), array());
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_fe/pi1/class.tx_terfe_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ter_fe/pi1/class.tx_terfe_pi1.php']);
}

?>